<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\OrderType;
use App\Enums\TableStatus;
use App\Models\Discount;
use App\Services\DiscountService;
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

    public function test_pos_discount_recalculates_when_items_change(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);

        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);

        $applied = $this->postJson(route('pos.discount', $order), [
            'discount_id' => $this->weekdayPromo->id,
        ])->assertOk();
        $this->assertEquals(0, (float) $applied->json('discount_amount'));

        $updated = $this->postJson(route('pos.items.store', $order), [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 1,
        ])->assertOk();
        $this->assertEquals(7000, (float) $updated->json('discount_amount'));

        $this->get(route('pos.index'))
            ->assertOk()
            ->assertSee('x-text="discountLine()"', false)
            ->assertSee('Promo Weekday 10%');
    }

    public function test_percentage_discount_respects_minimum_and_maximum(): void
    {
        $service = app(DiscountService::class);

        $this->assertEquals(0, $service->calculate($this->weekdayPromo, 40000));
        $this->assertEquals(10000, $service->calculate($this->weekdayPromo, 100000));
        $this->assertEquals(20000, $service->calculate($this->weekdayPromo, 300000));
    }

    public function test_bogo_discounts_cheapest_eligible_units(): void
    {
        $promo = Discount::query()->create([
            'name' => 'Buy 1 Get 1 Burger',
            'type' => DiscountType::Bogo,
            'scope' => 'item',
            'value' => 0,
            'buy_qty' => 1,
            'get_qty' => 1,
            'is_active' => true,
        ]);
        $promo->items()->create(['product_id' => $this->sellableProduct->id]);

        $service = app(DiscountService::class);
        $two = [['product_id' => $this->sellableProduct->id, 'quantity' => 2, 'unit_price' => 35000]];
        $three = [['product_id' => $this->sellableProduct->id, 'quantity' => 3, 'unit_price' => 35000]];

        $this->assertEquals(35000, $service->calculate($promo, 70000, $two));
        $this->assertEquals(35000, $service->calculate($promo, 105000, $three));
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

    public function test_admin_can_update_and_delete_discount(): void
    {
        $this->actingAsAtOutlet($this->admin);

        $this->put(route('marketing.discounts.update', $this->weekdayPromo), [
            'name' => 'Promo Weekday 15%',
            'type' => 'percentage',
            'scope' => 'order',
            'value' => 15,
            'minimum_transaction' => 50000,
            'maximum_discount' => 25000,
        ])->assertRedirect(route('marketing.discounts'));

        $this->weekdayPromo->refresh();
        $this->assertSame('Promo Weekday 15%', $this->weekdayPromo->name);
        $this->assertEquals(15, (float) $this->weekdayPromo->value);
        $this->assertEquals(25000, (float) $this->weekdayPromo->maximum_discount);

        $this->delete(route('marketing.discounts.destroy', $this->weekdayPromo))
            ->assertRedirect(route('marketing.discounts'));

        $this->assertSoftDeleted($this->weekdayPromo);
    }

    public function test_cashier_cannot_update_or_delete_discount(): void
    {
        $this->actingAsAtOutlet($this->cashier);

        $this->put(route('marketing.discounts.update', $this->weekdayPromo), [
            'name' => 'Hacked',
            'type' => 'percentage',
            'scope' => 'order',
            'value' => 99,
        ])->assertForbidden();

        $this->delete(route('marketing.discounts.destroy', $this->weekdayPromo))
            ->assertForbidden();

        $this->assertDatabaseHas('discounts', [
            'id' => $this->weekdayPromo->id,
            'name' => 'Promo Weekday 10%',
        ]);
    }
}
