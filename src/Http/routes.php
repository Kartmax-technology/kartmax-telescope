<?php

use Illuminate\Support\Facades\Route;
use Laravel\Telescope\Http\Controllers\HomeController;


// Mail entries...
Route::post('/telescope-api/mail', 'MailController@index');
Route::get('/telescope-api/mail/{telescopeEntryId}', 'MailController@show')->middleware('cache.control:14400');
Route::get('/telescope-api/mail/{telescopeEntryId}/preview', 'MailHtmlController@show')->middleware('cache.control:14400');
Route::get('/telescope-api/mail/{telescopeEntryId}/download', 'MailEmlController@show')->middleware('cache.control:14400');

// Exception entries...
Route::post('/telescope-api/exceptions', 'ExceptionController@index');
Route::get('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@show')->middleware('cache.control:14400');
Route::put('/telescope-api/exceptions/{telescopeEntryId}', 'ExceptionController@update');

// Dump entries...
Route::post('/telescope-api/dumps', 'DumpController@index');

// Log entries...
Route::post('/telescope-api/logs', 'LogController@index');
Route::get('/telescope-api/logs/{telescopeEntryId}', 'LogController@show')->middleware('cache.control:14400');

// Notifications entries...
Route::post('/telescope-api/notifications', 'NotificationsController@index');
Route::get('/telescope-api/notifications/{telescopeEntryId}', 'NotificationsController@show')->middleware('cache.control:14400');

// Queue entries...
Route::post('/telescope-api/jobs', 'QueueController@index');
Route::get('/telescope-api/jobs/{telescopeEntryId}', 'QueueController@show')->middleware('cache.control:14400');

// Queue Batches entries...
Route::post('/telescope-api/batches', 'QueueBatchesController@index');
Route::get('/telescope-api/batches/{telescopeEntryId}', 'QueueBatchesController@show')->middleware('cache.control:14400');

// Events entries...
Route::post('/telescope-api/events', 'EventsController@index');
Route::get('/telescope-api/events/{telescopeEntryId}', 'EventsController@show')->middleware('cache.control:14400');

// Gates entries...
Route::post('/telescope-api/gates', 'GatesController@index');
Route::get('/telescope-api/gates/{telescopeEntryId}', 'GatesController@show')->middleware('cache.control:14400');

// Cache entries...
Route::post('/telescope-api/cache', 'CacheController@index');
Route::get('/telescope-api/cache/{telescopeEntryId}', 'CacheController@show')->middleware('cache.control:14400');

// Queries entries...
Route::post('/telescope-api/queries', 'QueriesController@index');
Route::get('/telescope-api/queries/{telescopeEntryId}', 'QueriesController@show')->middleware('cache.control:14400');

// Eloquent entries...
Route::post('/telescope-api/models', 'ModelsController@index');
Route::get('/telescope-api/models/{telescopeEntryId}', 'ModelsController@show')->middleware('cache.control:14400');

// Requests entries...
Route::post('/telescope-api/requests', 'RequestsController@index');
Route::get('/telescope-api/requests/{telescopeEntryId}', 'RequestsController@show')->middleware('cache.control:14400');

// View entries...
Route::post('/telescope-api/views', 'ViewsController@index');
Route::get('/telescope-api/views/{telescopeEntryId}', 'ViewsController@show')->middleware('cache.control:14400');

// Artisan Commands entries...
Route::post('/telescope-api/commands', 'CommandsController@index');
Route::get('/telescope-api/commands/{telescopeEntryId}', 'CommandsController@show')->middleware('cache.control:14400');

// Scheduled Commands entries...
Route::post('/telescope-api/schedule', 'ScheduleController@index');
Route::get('/telescope-api/schedule/{telescopeEntryId}', 'ScheduleController@show')->middleware('cache.control:14400');

// Redis Commands entries...
Route::post('/telescope-api/redis', 'RedisController@index');
Route::get('/telescope-api/redis/{telescopeEntryId}', 'RedisController@show')->middleware('cache.control:14400');

// Client Requests entries...
Route::post('/telescope-api/client-requests', 'ClientRequestController@index');
Route::get('/telescope-api/client-requests/{telescopeEntryId}', 'ClientRequestController@show')->middleware('cache.control:14400');

// Monitored Tags...
Route::get('/telescope-api/monitored-tags', 'MonitoredTagController@index')->middleware('cache.control:120');
Route::post('/telescope-api/monitored-tags/', 'MonitoredTagController@store');
Route::post('/telescope-api/monitored-tags/delete', 'MonitoredTagController@destroy');

// Toggle Recording...
Route::post('/telescope-api/toggle-recording', 'RecordingController@toggle');

// Clear Entries...
Route::delete('/telescope-api/entries', 'EntriesController@destroy');

Route::get('/telescope-api/home-stats', 'HomeController@stats')->middleware('cache.control:300');


Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('telescope');
