<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Models\Product;
use App\Services\OrderService;
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

    public function test_outlet_manager_can_create_bundle(): void
    {
        $manager = $this->makeUser('Manager User', 'manager@test.com', 'outlet_manager', [$this->outlet]);

        $this->actingAsAtOutlet($manager)
            ->get(route('marketing.bundles'))
            ->assertOk()
            ->assertSee('Tambah bundle');

        $this->actingAsAtOutlet($manager)
            ->post(route('marketing.bundles.store'), [
                'name' => 'Paket Hemat',
                'price' => 50000,
                'items' => [
                    ['product_id' => $this->sellableProduct->id, 'quantity' => 1],
                    ['product_id' => $this->sellableProduct->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('marketing.bundles'));

        $this->assertDatabaseHas('bundles', ['name' => 'Paket Hemat']);
    }

    public function test_cashier_cannot_create_bundle(): void
    {
        $this->actingAsAtOutlet($this->cashier)
            ->post(route('marketing.bundles.store'), [
                'name' => 'Paket Kasir',
                'price' => 10000,
                'items' => [
                    ['product_id' => $this->sellableProduct->id, 'quantity' => 1],
                    ['product_id' => $this->sellableProduct->id, 'quantity' => 1],
                ],
            ])
            ->assertForbidden();
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

    public function test_sidebar_follows_clean_menu_tree(): void
    {
        $this->actingAsAtOutlet($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Sales Orders<', false)
            ->assertSee('>Production Orders<', false)
            ->assertSee('>Operasional<', false)
            ->assertSee('>Marketing<', false)
            ->assertDontSee('>Penjualan<', false)
            ->assertSee('>Katalog<', false)
            ->assertSee('>Pengaturan<', false)
            ->assertSee(route('marketing.bundles'), false)
            ->assertDontSee(route('reports.promo', ['nav' => 'penjualan']), false)
            ->assertDontSee('>Kitchen<', false);
    }

    public function test_penjualan_does_not_include_promo(): void
    {
        $html = $this->actingAsAtOutlet($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('nav=penjualan', $html);

        $promo = $this->get(route('reports.promo'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-nav="Marketing" data-open="0"', $promo);
        $this->assertStringContainsString('data-nav="Laporan" data-open="1"', $promo);
    }

    public function test_kitchen_and_bar_menus_are_removed(): void
    {
        $this->actingAsAtOutlet($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Kitchen<', false)
            ->assertDontSee('>Bar<', false);

        $this->actingAsAtOutlet($this->kitchen)
            ->get('/kitchen')
            ->assertNotFound();

        $this->actingAsAtOutlet($this->cashier)
            ->get('/bar')
            ->assertNotFound();
    }

    public function test_kitchen_cannot_access_pos_without_permission(): void
    {
        $this->assertFalse($this->kitchen->hasPermission('pos.access'));

        $this->actingAsAtOutlet($this->kitchen)
            ->get(route('pos.index'))
            ->assertForbidden();
    }

    public function test_kitchen_can_advance_order_status(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
        $orders->submit($order);

        $this->actingAsAtOutlet($this->kitchen)
            ->from(route('orders.show', $order))
            ->post(route('orders.status', $order), ['status' => 'processing'])
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Processing, $order->fresh()->status);
    }

    public function test_cannot_mark_order_ready_until_all_items_ready(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $orders = app(OrderService::class);
        $order = $orders->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $first = $orders->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
        $drink = Product::query()->create([
            'sku' => 'PRD-READY-2',
            'name' => 'Es Teh',
            'category_id' => $this->foodCategory->id,
            'unit_id' => $this->unitPcs->id,
            'type' => ProductType::Finished,
            'price' => 8000,
            'is_sellable' => true,
            'is_active' => true,
        ]);
        $second = $orders->addItem($order->fresh(), ['product_id' => $drink->id, 'quantity' => 1]);
        $orders->submit($order->fresh());

        $this->actingAsAtOutlet($this->kitchen)
            ->from(route('orders.show', $order))
            ->post(route('orders.status', $order), ['status' => 'ready'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('status');

        $this->post(route('order-items.status', $first), ['status' => 'ready']);
        $this->post(route('order-items.status', $second), ['status' => 'ready']);

        $this->from(route('orders.show', $order))
            ->post(route('orders.status', $order), ['status' => 'ready'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }
}
