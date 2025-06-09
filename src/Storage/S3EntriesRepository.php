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
use Laravel\Telescope\Storage\S3DailyStatsService;
use AsyncAws\S3\S3Client;
use AsyncAws\Core\Result;

class S3EntriesRepository implements Contract, ClearableRepository, PrunableRepository, TerminableRepository
{
    protected $disk;
    protected $directory;
    protected $monitoredTags;
    protected $monitoredTagsFile = 'monitored-tags.json';
    protected $statsService;
    protected $s3Client;

    public function __construct(string $disk, string $directory, ?S3DailyStatsService $statsService = null)
    {
        $this->disk = $disk;
        $this->directory = trim($directory, '/');
        $this->monitoredTagsFile = $this->directory . '/' . $this->monitoredTagsFile;
        $this->statsService = $statsService ?? app(S3DailyStatsService::class);
        
        // Initialize AsyncAws S3 client
        $this->s3Client = new S3Client([
            'region' => config('filesystems.disks.' . $disk . '.region'),
            'accessKeyId' => config('filesystems.disks.' . $disk . '.key'),
            'accessKeySecret' => config('filesystems.disks.' . $disk . '.secret'),
        ]);
    }

    protected function entryPath($type, $batchId, $uuid)
    {
        return "{$this->directory}/{$type}/{$batchId}/{$uuid}.json";
    }

    public function find($id): EntryResult
    {
        // Scan all types and batches for the given uuid
        $files = Storage::disk($this->disk)->allFiles($this->directory);
        foreach ($files as $file) {
            if (str_ends_with($file, "/{$id}.json")) {
                $data = json_decode(Storage::disk($this->disk)->get($file), true);
                return $this->toEntryResult($data, $file);
            }
        }
        abort(404, 'Entry not found');
    }

    public function get($type, EntryQueryOptions $options)
    {
        $path = $type ? "{$this->directory}/{$type}" : $this->directory;
        $files = Storage::disk($this->disk)->allFiles($path);
        $results = collect();
        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) continue;
            $data = json_decode(Storage::disk($this->disk)->get($file), true);
            if ($this->matchesOptions($data, $options)) {
                $results->push($this->toEntryResult($data, $file));
            }
        }
        // Sort by sequence if present, otherwise by created_at
        return $results->sortByDesc(function($entry) {
            return $entry->sequence ?? ($entry->createdAt ? $entry->createdAt->timestamp : 0);
        })->take($options->limit)->values();
    }

    public function store(Collection $entries)
    {
        $promises = [];
        
        foreach ($entries as $entry) {
            $filePath = $this->entryPath($entry->type, $entry->batchId, $entry->uuid);
            $content = json_encode($entry->toArray());
            
            // Create async upload promise - remove getResult() since putObject() already returns a result
            $promises[] = $this->s3Client->putObject([
                'Bucket' => config('filesystems.disks.' . $this->disk . '.bucket'),
                'Key' => $filePath,
                'Body' => $content,
            ]);
            
            // Only increment stats if statsService is available
            if ($this->statsService) {
                $this->statsService->increment($entry->type);
            }
        }
        
        // Wait for all uploads to complete
        Result::wait($promises);
    }

    public function update(Collection $updates)
    {
        // Not implemented for S3 (optional, depending on use-case)
        return null;
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

    public function isMonitoring(array $tags)
    {
        if ($this->monitoredTags === null) {
            $this->loadMonitoredTags();
        }
        return !empty(array_intersect($tags, $this->monitoredTags));
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

    public function prune(DateTimeInterface $before)
    {
        $files = Storage::disk($this->disk)->allFiles($this->directory);
        $deleted = 0;
        foreach ($files as $file) {
            if (!str_ends_with($file, '.json')) continue;
            $data = json_decode(Storage::disk($this->disk)->get($file), true);
            $createdAt = Carbon::parse($data['created_at'] ?? null);
            if ($createdAt->lt($before)) {
                Storage::disk($this->disk)->delete($file);
                $deleted++;
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