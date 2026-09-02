<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\TableStatus;
use App\Services\DiscountService;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use App\Services\TableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class LoyaltyDiscountTableTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_percentage_discount_respects_minimum_and_maximum(): void
    {
        $service = app(DiscountService::class);

        $this->assertEquals(0, $service->calculate($this->weekdayPromo, 40000));
        $this->assertEquals(10000, $service->calculate($this->weekdayPromo, 100000));
        $this->assertEquals(20000, $service->calculate($this->weekdayPromo, 300000));
    }

    public function test_loyalty_redeem_value_uses_configured_rate(): void
    {
        $service = app(LoyaltyService::class);

        $this->assertEquals(10000, $service->redeemValue(100));
        $this->assertEquals(15000, $service->redeemValue(150));
    }

    public function test_table_transfer_moves_open_order(): void
    {
        $this->actingAsAtOutlet($this->cashier);

        $order = app(OrderService::class)->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'order_type' => OrderType::DineIn->value,
            'guest_count' => 2,
        ]);

        $this->assertSame($this->tableA->id, $order->table_id);
        $this->assertSame(TableStatus::Occupied, $this->tableA->fresh()->status);

        app(TableService::class)->transfer($this->tableA->id, $this->tableB->id);

        $this->assertSame($this->tableB->id, $order->fresh()->table_id);
        $this->assertSame(TableStatus::Available, $this->tableA->fresh()->status);
        $this->assertSame(TableStatus::Occupied, $this->tableB->fresh()->status);
    }
}
