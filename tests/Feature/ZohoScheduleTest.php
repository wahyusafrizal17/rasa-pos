<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ZohoScheduleTest extends TestCase
{
    public function test_catalog_sync_runs_twice_daily_jakarta(): void
    {
        $events = collect(app(Schedule::class)->events());
        $event = $events->first(fn ($event) => str_contains((string) $event->command, 'zoho:sync-catalog'));

        $this->assertNotNull($event);
        $this->assertSame('0 0,12 * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
    }
}
