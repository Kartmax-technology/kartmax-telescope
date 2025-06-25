<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class S3DailyStatsService
{
    protected $disk;
    protected $directory;

    public function __construct()
    {
        $this->disk = config('telescope.storage.s3.disk', 's3');
        $this->directory = trim(config('telescope.storage.s3.directory', 'telescope'), '/');
    }

    protected function getStatsPath($date)
    {
        return $this->directory . '/stats/' . $date . '.json';
    }

    /**
     * Increment the counter for a specific entry type
     *
     * @param string $type The type of entry (request, job, exception, etc.)
     * @return void
     */
    public function increment($type)
    {
        $date = Carbon::now()->toDateString();
        $path = $this->getStatsPath($date);
        $stats = [
            'date' => $date,
            'request' => 0,
            'jobs' => 0,
            'exception' => 0,
            'mail' => 0,
            'queries' => 0,
        ];
        if (Storage::disk($this->disk)->exists($path)) {
            $stats = json_decode(Storage::disk($this->disk)->get($path), true);
        }
        if (isset($stats[$type])) {
            $stats[$type]++;
        }
        Storage::disk($this->disk)->put($path, json_encode($stats));
    }

    /**
     * Get stats for a specific date or date range
     *
     * @param string|null $date The date to get stats for (Y-m-d format)
     * @param string|null $endDate Optional end date for range
     * @return array
     */
    public function getStats($date = null)
    {
        $date = $date ?: Carbon::now()->toDateString();
        $path = $this->getStatsPath($date);
        if (Storage::disk($this->disk)->exists($path)) {
            return json_decode(Storage::disk($this->disk)->get($path), true);
        }
        return [
            'date' => $date,
            'request' => 0,
            'jobs' => 0,
            'exception' => 0,
            'mail' => 0,
            'queries' => 0,
        ];
    }

    /**
     * Get stats for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function getStatsRange($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $end->diffInDays($start);

        $stats = [];
        for ($i = 0; $i <= $days; $i++) {
            $currentDate = $start->copy()->addDays($i)->format('Y-m-d');
            $stats[$currentDate] = $this->getStats($currentDate);
        }

        return $stats;
    }

    /**
     * Get empty stats structure for a date
     *
     * @param string $date
     * @return array
     */
    protected function getEmptyStats($date)
    {
        return [
            'date' => $date,
            'request' => 0,
            'jobs' => 0,
            'exception' => 0,
            'mail' => 0,
            'queries' => 0,
        ];
    }
} 