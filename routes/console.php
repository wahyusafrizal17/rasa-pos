<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('zoho:sync-catalog --no-interaction')
    ->everyThirtyMinutes()
    ->withoutOverlapping(45)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/zoho-sync.log'));
