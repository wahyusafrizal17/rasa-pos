<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ZohoScheduleTest extends TestCase
{
    public function test_catalog_sync_runs_every_thirty_minutes(): void
    {
        $events = collect(app(Schedule::class)->events());

        $this->assertTrue(
            $events->contains(fn ($event) => str_contains((string) $event->command, 'zoho:sync-catalog')
                && $event->expression === '*/30 * * * *'),
            'zoho:sync-catalog should be scheduled every 30 minutes'
        );
    }
}
