<?php

namespace Tests\Feature;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Product;
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
        $other = Product::query()->create([
            'sku' => 'PRD-SPLIT-2',
            'name' => 'Es Teh',
            'category_id' => $this->foodCategory->id,
            'unit_id' => $this->unitPcs->id,
            'type' => ProductType::Finished,
            'price' => 8000,
            'is_sellable' => true,
            'is_active' => true,
        ]);
        $second = $orders->addItem($order->fresh(), ['product_id' => $other->id, 'quantity' => 2]);

        $split = app(TableService::class)->split($this->tableA->id, $this->tableB->id, [$second->id]);

        $this->assertSame($this->tableB->id, $split->table_id);
        $this->assertTrue($split->items->contains('id', $second->id));
        $this->assertTrue($order->fresh()->items->contains('id', $first->id));
        $this->assertFalse($order->fresh()->items->contains('id', $second->id));
        $this->assertSame(TableStatus::Occupied, $this->tableA->fresh()->status);
        $this->assertSame(TableStatus::Occupied, $this->tableB->fresh()->status);
    }

    public function test_captain_can_checkout_and_send_to_kitchen(): void
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
            'method' => 'card',
            'tendered' => 50000,
        ])->assertOk()
            ->assertJsonPath('order.status', 'new')
            ->assertJsonPath('order.payment_status', 'paid');
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

    public function test_merge_moves_items_and_frees_source_table(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);

        $source = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'order_type' => OrderType::DineIn->value,
        ]);
        $sourceItem = $orders->addItem($source, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);

        $target = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableB->id,
            'order_type' => OrderType::DineIn->value,
        ]);
        $targetItem = $orders->addItem($target, ['product_id' => $this->sellableProduct->id, 'quantity' => 2]);

        $this->post(route('tables.merge'), [
            'source_id' => $this->tableA->id,
            'target_id' => $this->tableB->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(TableStatus::Available, $this->tableA->fresh()->status);
        $this->assertSame(TableStatus::Occupied, $this->tableB->fresh()->status);
        $this->assertTrue($target->fresh()->items->contains('id', $sourceItem->id));
        $this->assertTrue($target->fresh()->items->contains('id', $targetItem->id));
        $this->assertSame(OrderStatus::Cancelled, $source->fresh()->status);

        $html = $this->get(route('tables.index'))->assertOk()->getContent();
        $this->assertStringContainsString('2 item', $html);
        $this->assertStringContainsString('Gabung dari T-01', $html);
    }

    public function test_merge_requires_active_orders_on_both_tables(): void
    {
        $this->actingAsAtOutlet($this->admin);
        DiningTable::query()->whereKey($this->tableA->id)->update(['status' => TableStatus::Occupied]);

        $this->from(route('tables.index'))
            ->post(route('tables.merge'), [
                'source_id' => $this->tableA->id,
                'target_id' => $this->tableB->id,
            ])
            ->assertRedirect(route('tables.index'))
            ->assertSessionHasErrors();

        $this->assertSame(TableStatus::Occupied, $this->tableA->fresh()->status);
        $this->assertSame(TableStatus::Available, $this->tableB->fresh()->status);
    }

    public function test_tables_index_lists_active_reservations(): void
    {
        $this->actingAsAtOutlet($this->admin);

        app(TableService::class)->reserve([
            'outlet_id' => $this->outlet->id,
            'table_id' => $this->tableA->id,
            'guest_name' => 'Budi Reservasi',
            'guest_phone' => '0812555000',
            'guest_count' => 3,
            'reserved_at' => now()->addHour(),
            'notes' => 'Ulang tahun',
        ]);

        $this->get(route('tables.index'))
            ->assertOk()
            ->assertSee('Daftar reservasi')
            ->assertSee('Budi Reservasi')
            ->assertSee('T-01')
            ->assertSee('Ulang tahun');
    }

    public function test_table_move_script_uses_generated_url(): void
    {
        $this->actingAsAtOutlet($this->admin);

        $html = $this->get(route('tables.index'))->assertOk()->getContent();

        $this->assertStringContainsString(url('/tables'), $html);
        $this->assertStringNotContainsString("fetch('/tables/'", $html);
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

    public function test_adding_the_same_product_increments_qty(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);

        $this->postJson(route('pos.items.store', $order), [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 1,
        ])->assertOk();
        $this->postJson(route('pos.items.store', $order), [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 1,
        ])->assertOk();

        $items = $order->fresh()->items()->whereNull('parent_id')->get();
        $this->assertCount(1, $items);
        $this->assertEquals(2, (float) $items->first()->quantity);
    }

    public function test_pos_adds_selected_addons_under_the_parent_item(): void
    {
        $addon = $this->makeAddonProduct();
        $this->sellableProduct->addons()->attach($addon->id);
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);

        $this->postJson(route('pos.items.store', $order), [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 2,
            'addon_ids' => [$addon->id],
        ])->assertOk();

        $order->refresh()->load('items');
        $parent = $order->items->firstWhere('product_id', $this->sellableProduct->id);
        $child = $order->items->firstWhere('product_id', $addon->id);

        $this->assertNotNull($parent);
        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertEquals(2, (float) $child->quantity);
        $this->assertEquals(35000 * 2 + 5000 * 2, (float) $order->subtotal);

        $this->deleteJson(route('pos.items.destroy', [$order, $parent]))->assertOk();
        $this->assertCount(0, $order->fresh()->items);
    }

    public function test_pos_ignores_addons_not_linked_to_the_product(): void
    {
        $addon = $this->makeAddonProduct();
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);

        $this->postJson(route('pos.items.store', $order), [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 1,
            'addon_ids' => [$addon->id],
        ])->assertOk();

        $this->assertNull($order->fresh()->items->firstWhere('product_id', $addon->id));
    }

    public function test_pos_only_offers_addons_assigned_to_each_menu(): void
    {
        $addon = $this->makeAddonProduct();
        $this->sellableProduct->addons()->attach($addon->id);
        $this->actingAsAtOutlet($this->cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertViewHas('productAddons', function ($map) use ($addon) {
                $forBurger = collect($map[(string) $this->sellableProduct->id] ?? []);

                return $forBurger->pluck('id')->contains($addon->id);
            });
    }

    public function test_pos_menu_hides_addon_products(): void
    {
        $addon = $this->makeAddonProduct();
        $this->actingAsAtOutlet($this->cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertDontSee('addProduct('.$addon->id, false);
    }

    protected function makeAddonProduct(): Product
    {
        return Product::query()->create([
            'sku' => 'ADD-TEST-EGG',
            'name' => 'Extra Telur',
            'category_id' => $this->foodCategory->id,
            'unit_id' => $this->unitPcs->id,
            'type' => ProductType::Finished,
            'price' => 5000,
            'is_sellable' => true,
            'is_stockable' => false,
            'is_addon' => true,
            'is_active' => true,
            'station' => 'kitchen',
        ]);
    }
}
