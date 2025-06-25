<?php

namespace Laravel\Telescope\Storage;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Telescope\Contracts\ClearableRepository;
use Laravel\Telescope\Contracts\EntriesRepository as Contract;
use Laravel\Telescope\Contracts\PrunableRepository;
use Laravel\Telescope\Contracts\TerminableRepository;
use Laravel\Telescope\EntryResult;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\EntryUpdate;
use Laravel\Telescope\Storage\EntryQueryOptions;
use Carbon\Carbon;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Laravel\Telescope\Storage\S3DailyStatsService;

class S3EntriesRepository implements Contract, ClearableRepository, PrunableRepository, TerminableRepository
{
    protected $disk;
    protected $directory;
    protected $monitoredTags;
    protected $monitoredTagsFile = 'monitored-tags.json';
    protected $s3Client;
    protected $statsService;

    public function __construct(string $disk, string $directory, ?S3DailyStatsService $statsService = null)
    {
        $this->disk = $disk;
        $this->directory = trim($directory, '/');
        $this->monitoredTagsFile = $this->directory . '/' . $this->monitoredTagsFile;
        $this->statsService = $statsService ?? app(S3DailyStatsService::class);
        
        // Initialize standard AWS S3 client
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.' . $disk . '.region'),
            'credentials' => [
                'key'    => config('filesystems.disks.' . $disk . '.key'),
                'secret' => config('filesystems.disks.' . $disk . '.secret'),
            ]
        ]);
    }

    protected function entryPath($type, $batchId, $uuid)
    {
        $date = now()->format('Y-m-d');
        return "{$this->directory}/{$type}/{$date}/{$batchId}/{$uuid}.json";
    }

    public function store(Collection $entries)
    {
        if ($entries->isEmpty()) {
            return;
        }

        foreach ($entries as $entry) {
            $filePath = $this->entryPath($entry->type, $entry->batchId, $entry->uuid);
            
            // Build complete entry data
            $entryData = [
                'uuid' => $entry->uuid,
                'batch_id' => $entry->batchId,
                'type' => $entry->type,
                'family_hash' => $entry->familyHash,
                'content' => $entry->content,
                'created_at' => $entry->recordedAt->toISOString(),
                'tags' => $entry->tags ?: [],
            ];
            
            try {
                // Use standard S3 putObject
                $this->s3Client->putObject([
                    'Bucket' => config('filesystems.disks.' . $this->disk . '.bucket'),
                    'Key' => $filePath,
                    'Body' => json_encode($entryData, JSON_PRETTY_PRINT),
                    'ContentType' => 'application/json',
                ]);

                // Increment stats for this entry type
                if ($this->statsService) {
                    $this->statsService->increment($entry->type);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the application
                Log::warning('Failed to store Telescope entry', [
                    'error' => $e->getMessage(),
                    'entry_type' => $entry->type,
                    'uuid' => $entry->uuid
                ]);
            }
        }
    }

    public function get($type, EntryQueryOptions $options)
    {
        $results = collect();
        $daysToCheck = 10; // Default to last 5 days
        
        // If specific batch_id is provided, optimize by looking in specific date folders
        if ($options->batchId) {
            $daysToCheck = 5;
        }

        // Get the date folders to check
        $datesToCheck = collect(range(0, $daysToCheck - 1))
            ->map(fn($daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'));

        // Build the base path
        $basePath = $type ? "{$this->directory}/{$type}" : $this->directory;

        foreach ($datesToCheck as $date) {
            // If we have enough results, break early
            if ($results->count() >= $options->limit) {
                break;
            }

            $datePath = $type ? "{$basePath}/{$date}" : $date;

            try {
                // Optimize path if we have a batch_id
                if ($options->batchId) {
                    $batchPath = "{$datePath}/{$options->batchId}";
                    if (!Storage::disk($this->disk)->exists($batchPath)) {
                        continue;
                    }
                    $files = Storage::disk($this->disk)->allFiles($batchPath);
                } else {
                    // List files for this date
                    $files = Storage::disk($this->disk)->allFiles($datePath);
                }

                // Process files for this date
                foreach ($files as $file) {
                    if (!str_ends_with($file, '.json')) {
                        continue;
                    }

                    if ($results->count() >= $options->limit) {
                        break;
                    }

                    $data = json_decode(Storage::disk($this->disk)->get($file), true);
                    
                    if ($this->matchesOptions($data, $options)) {
                        $results->push($this->toEntryResult($data, $file));
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve Telescope entries', [
                    'date' => $date,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $results->sortByDesc(function($entry) {
            return $entry->sequence ?? ($entry->createdAt ? $entry->createdAt->timestamp : 0);
        })->take($options->limit)->values();
    }

    public function find($id): EntryResult
    {
        // Look in the last 5 days
        $datesToCheck = collect(range(0, 4))
            ->map(fn($daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'));

        foreach ($datesToCheck as $date) {
            $files = Storage::disk($this->disk)->allFiles($this->directory);
            foreach ($files as $file) {
                if (str_ends_with($file, "/{$id}.json")) {
                    try {
                        $data = json_decode(Storage::disk($this->disk)->get($file), true);
                        return $this->toEntryResult($data, $file);
                    } catch (\Exception $e) {
                        Log::warning("Failed to read Telescope entry: {$id}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        abort(404, 'Entry not found');
    }

    public function update(Collection $updates)
    {
        return null; // S3 implementation doesn't support updates
    }

    public function loadMonitoredTags()
    {
        if (Storage::disk($this->disk)->exists($this->monitoredTagsFile)) {
            $tags = json_decode(Storage::disk($this->disk)->get($this->monitoredTagsFile), true);
            $this->monitoredTags = is_array($tags) ? $tags : [];
        } else {
            $this->monitoredTags = [];
        }
    }

    public function monitoring()
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }
        return $this->monitoredTags;
    }

    public function monitor(array $tags)
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }
        $this->monitoredTags = array_unique(array_merge($this->monitoredTags, $tags));
        Storage::disk($this->disk)->put($this->monitoredTagsFile, json_encode(array_values($this->monitoredTags)));
    }

    public function stopMonitoring(array $tags)
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }
        $this->monitoredTags = array_values(array_diff($this->monitoredTags, $tags));
        Storage::disk($this->disk)->put($this->monitoredTagsFile, json_encode($this->monitoredTags));
    }

    public function isMonitoring(array $tags)
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }
        return !empty(array_intersect($tags, $this->monitoredTags));
    }

    public function prune(DateTimeInterface $before)
    {
        $deleted = 0;
        $datesToCheck = collect(range(0, 30)) // Check up to 30 days back
            ->map(fn($daysAgo) => now()->subDays($daysAgo)->format('Y-m-d'));

        foreach ($datesToCheck as $date) {
            $dateTimestamp = Carbon::parse($date)->timestamp;
            if ($dateTimestamp < $before->getTimestamp()) {
                $path = "{$this->directory}/{$date}";
                if (Storage::disk($this->disk)->exists($path)) {
                    Storage::disk($this->disk)->deleteDirectory($path);
                    $deleted++;
                }
            }
        }
        return $deleted;
    }

    public function clear()
    {
        Storage::disk($this->disk)->deleteDirectory($this->directory);
    }

    public function terminate()
    {
        $this->monitoredTags = null;
    }

    protected function matchesOptions($data, EntryQueryOptions $options)
    {
        if ($options->batchId && ($data['batch_id'] ?? null) !== $options->batchId) return false;
        if ($options->tag && (!isset($data['tags']) || !in_array($options->tag, $data['tags']))) return false;
        if ($options->familyHash && ($data['family_hash'] ?? null) !== $options->familyHash) return false;
        if ($options->beforeSequence && ($data['sequence'] ?? null) >= $options->beforeSequence) return false;
        if ($options->uuids && !in_array($data['uuid'] ?? null, $options->uuids)) return false;
        return true;
    }

    protected function toEntryResult($data, $file)
    {
        return new EntryResult(
            $data['uuid'] ?? null,
            $data['sequence'] ?? null,
            $data['batch_id'] ?? null,
            $data['type'] ?? null,
            $data['family_hash'] ?? null,
            $data['content'] ?? [],
            Carbon::parse($data['created_at'] ?? now()),
            $data['tags'] ?? []
        );
    }
} 