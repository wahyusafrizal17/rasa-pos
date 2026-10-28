<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('shifts:rotate')
    ->everyMinute()
    ->withoutOverlapping(5);

Schedule::command('zoho:sync-catalog --no-interaction --trigger=schedule')
    ->timezone('Asia/Jakarta')
    ->twiceDaily(0, 12)
    ->withoutOverlapping(45)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/zoho-sync.log'));
