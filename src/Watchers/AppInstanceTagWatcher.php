<?php

namespace Laravel\Telescope\Watchers;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;

class AppInstanceTagWatcher extends Watcher
{
    public function register($app)
    {
        Telescope::recording(function (IncomingEntry $entry) use ($app) {
            $dynamicTagKey = config('telescope.custom_dynamic_tag_key', env('TELESCOPE_DYNAMIC_TAG_KEY', 'site_token'));
            if ($app->bound($dynamicTagKey)) {
                $siteToken = $app->make($dynamicTagKey);
                if (!in_array($siteToken, $entry->tags)) {
                    $entry->tags[] = $siteToken;
                }
            }
        });
    }
} 