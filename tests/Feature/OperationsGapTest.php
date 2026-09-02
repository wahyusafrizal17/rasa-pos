<?php

namespace Tests\Feature;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Unit;
use App\Services\OrderService;
use App\Services\ProductionService;
use App\Services\TableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class OperationsGapTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_pickup_draft_uses_pickup_channel(): void
    {
        $this->actingAsAtOutlet($this->cashier);

        $order = app(OrderService::class)->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);

        $this->assertSame(OrderChannel::Pickup, $order->channel);
    }

    public function test_table_split_moves_selected_items(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);

        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'order_type' => OrderType::DineIn->value,
        ]);
        $first = $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
        $second = $orders->addItem($order->fresh(), ['product_id' => $this->sellableProduct->id, 'quantity' => 2]);

        $split = app(TableService::class)->split($this->tableA->id, $this->tableB->id, [$second->id]);

        $this->assertSame($this->tableB->id, $split->table_id);
        $this->assertTrue($split->items->contains('id', $second->id));
        $this->assertTrue($order->fresh()->items->contains('id', $first->id));
        $this->assertFalse($order->fresh()->items->contains('id', $second->id));
        $this->assertSame(TableStatus::Occupied, $this->tableA->fresh()->status);
        $this->assertSame(TableStatus::Occupied, $this->tableB->fresh()->status);
    }

    public function test_apply_points_from_pos_route(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $this->customer->update(['points' => 200]);

        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 2]);

        $this->postJson(route('pos.points', $order), ['points' => 100])
            ->assertOk()
            ->assertJsonPath('points_redeemed', 100);

        $this->assertEquals(10000, (float) $order->fresh()->points_value);
    }

    public function test_captain_cannot_checkout(): void
    {
        $captain = $this->makeUser('Captain', 'captain@example.com', 'captain', [$this->outlet]);
        $this->actingAsAtOutlet($captain);

        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);

        $this->postJson(route('pos.checkout', $order), [
            'method' => 'cash',
            'tendered' => 50000,
        ])->assertForbidden();
    }

    public function test_kitchen_checker_can_confirm_item(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::DineIn->value,
            'table_id' => $this->tableA->id,
        ]);
        $item = $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
        $orders->submit($order->fresh());

        $this->actingAsAtOutlet($this->kitchen)
            ->post(route('order-items.status', $item), ['status' => 'ready'])
            ->assertRedirect();

        $this->assertSame('ready', $item->fresh()->status);
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
    }

    public function test_unit_conversion_between_same_family(): void
    {
        $gram = Unit::query()->create([
            'code' => 'G',
            'name' => 'Gram',
            'family' => 'weight',
            'conversion_factor' => 1,
        ]);

        $converted = $this->unitKg->convertTo($gram, 0.5);

        $this->assertEquals(500, $converted);
    }

    public function test_bom_explode_lists_levels(): void
    {
        $rows = app(ProductionService::class)->explode($this->bom, 2);

        $this->assertNotEmpty($rows);
        $this->assertSame(1, $rows[0]['level']);
        $this->assertContains($this->rawChicken->id, array_column($rows, 'product_id'));
    }

    public function test_category_and_promo_reports_are_reachable(): void
    {
        $this->actingAsAtOutlet($this->admin)
            ->get(route('reports.categories', ['period' => 'today']))
            ->assertOk();

        $this->actingAsAtOutlet($this->admin)
            ->get(route('reports.promo', ['period' => 'month']))
            ->assertOk();
    }

    public function test_dine_in_submit_requires_a_table(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::DineIn->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);

        $this->postJson(route('pos.submit', $order))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('table_id');

        $this->postJson(route('pos.submit', $order), ['table_id' => $this->tableA->id])
            ->assertOk()
            ->assertJsonPath('order.status', 'new');

        $this->assertSame($this->tableA->id, $order->fresh()->table_id);
        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_pos_can_list_and_recall_held_orders(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 2]);

        $this->postJson(route('pos.hold', $order))
            ->assertOk()
            ->assertJsonPath('id', $order->id)
            ->assertJsonPath('status', 'held');

        $this->assertSame(OrderStatus::Held, $order->fresh()->status);

        $this->getJson(route('pos.held'))
            ->assertOk()
            ->assertJsonFragment(['id' => $order->id, 'order_number' => $order->order_number]);

        $this->getJson(route('pos.recall', $order))
            ->assertOk()
            ->assertJsonPath('id', $order->id)
            ->assertJsonPath('status', 'held');
    }

    public function test_pos_can_register_member(): void
    {
        $this->actingAsAtOutlet($this->cashier)
            ->postJson(route('pos.customers.store'), [
                'name' => 'Siti Member',
                'phone' => '0812000111',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Siti Member');
    }

    public function test_split_route_requires_items(): void
    {
        $this->actingAsAtOutlet($this->admin);

        DiningTable::query()->whereKey($this->tableA->id)->update(['status' => TableStatus::Occupied]);

        $this->post(route('tables.split'), [
            'source_id' => $this->tableA->id,
            'target_id' => $this->tableB->id,
            'item_ids' => [],
        ])->assertSessionHasErrors();
    }
}
