<?php

namespace Laravel\Telescope\Tests\Watchers;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Tests\FeatureTestCase;
use Laravel\Telescope\Watchers\CacheWatcher;

class CacheWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('telescope.watchers', [
            CacheWatcher::class => [
                'enabled' => true,
                'hidden' => [
                    'my-hidden-value-key',
                ],
                'ignore' => [
                    'laravel:pulse:*',
                    'ignored-key',
                ],
            ],
        ]);
    }

    public function test_cache_watcher_registers_missed_entries()
    {
        $this->app->get(Repository::class)->get('empty-key');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('missed', $entry->content['type']);
        $this->assertSame('empty-key', $entry->content['key']);
    }

    public function test_cache_watcher_registers_store_entries()
    {
        $this->app->get(Repository::class)->put('my-key', 'laravel', 1);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('set', $entry->content['type']);
        $this->assertSame('my-key', $entry->content['key']);
        $this->assertSame('laravel', $entry->content['value']);
    }

    public function test_cache_watcher_registers_hit_entries()
    {
        $repository = $this->app->get(Repository::class);

        Telescope::withoutRecording(function () use ($repository) {
            $repository->put('telescope', 'laravel', 1);
        });

        $repository->get('telescope');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('hit', $entry->content['type']);
        $this->assertSame('telescope', $entry->content['key']);
        $this->assertSame('laravel', $entry->content['value']);
    }

    public function test_cache_watcher_registers_forget_entries()
    {
        $repository = $this->app->get(Repository::class);

        Telescope::withoutRecording(function () use ($repository) {
            $repository->put('outdated', 'value', 1);
        });

        $repository->forget('outdated');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('forget', $entry->content['type']);
        $this->assertSame('outdated', $entry->content['key']);
    }

    public function test_cache_watcher_hides_hidden_values_when_set()
    {
        $this->app->get(Repository::class)->put('my-hidden-value-key', 'laravel', 1);

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('set', $entry->content['type']);
        $this->assertSame('my-hidden-value-key', $entry->content['key']);
        $this->assertSame('********', $entry->content['value']);
    }

    public function test_cache_watcher_hides_hidden_values_when_retrieved()
    {
        $repository = $this->app->get(Repository::class);

        Telescope::withoutRecording(function () use ($repository) {
            $repository->put('my-hidden-value-key', 'laravel', 1);
        });

        $repository->get('my-hidden-value-key');

        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('hit', $entry->content['type']);
        $this->assertSame('my-hidden-value-key', $entry->content['key']);
        $this->assertSame('********', $entry->content['value']);
    }

    public function test_cache_watcher_skips_recording_ignored_cache_keys()
    {
        $this->app->get(Repository::class)->put('ignored-key', 'laravel');
        $this->app->get(Repository::class)->put('laravel:pulse:restart', 'laravel');
        $this->app->get(Repository::class)->put('my-key', 'laravel');

        $count = $this->loadTelescopeEntries()->count();
        $entry = $this->loadTelescopeEntries()->first();

        $this->assertSame(1, $count);

        $this->assertSame(EntryType::CACHE, $entry->type);
        $this->assertSame('set', $entry->content['type']);
        $this->assertSame('my-key', $entry->content['key']);
        $this->assertSame('laravel', $entry->content['value']);
    }
}
