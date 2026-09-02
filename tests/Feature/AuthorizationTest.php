<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_cashier_cannot_access_settings(): void
    {
        $this->actingAsAtOutlet($this->cashier)
            ->get(route('settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAsAtOutlet($this->admin)
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_kitchen_cannot_access_pos_without_permission(): void
    {
        $this->assertFalse($this->kitchen->hasPermission('pos.access'));

        $this->actingAsAtOutlet($this->kitchen)
            ->get(route('pos.index'))
            ->assertForbidden();
    }
}
