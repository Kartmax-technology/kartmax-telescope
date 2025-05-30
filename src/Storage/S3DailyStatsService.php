<?php

namespace Laravel\Telescope\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

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

    public function increment($type)
    {
        $date = Carbon::now()->toDateString();
        $path = $this->getStatsPath($date);
        $stats = [
            'date' => $date,
            'requests' => 0,
            'jobs' => 0,
            'exceptions' => 0,
            'mails' => 0,
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

    public function getStats($date = null)
    {
        $date = $date ?: Carbon::now()->toDateString();
        $path = $this->getStatsPath($date);
        if (Storage::disk($this->disk)->exists($path)) {
            return json_decode(Storage::disk($this->disk)->get($path), true);
        }
        return [
            'date' => $date,
            'requests' => 0,
            'jobs' => 0,
            'exceptions' => 0,
            'mails' => 0,
            'queries' => 0,
        ];
    }
} 