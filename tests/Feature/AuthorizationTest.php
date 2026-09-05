<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
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

    public function test_kitchen_can_open_kitchen_display_but_not_bar(): void
    {
        $this->actingAsAtOutlet($this->kitchen)
            ->get(route('kitchen.index'))
            ->assertOk()
            ->assertSee('Kitchen');

        $this->actingAsAtOutlet($this->kitchen)
            ->get(route('bar.index'))
            ->assertForbidden();
    }

    public function test_cashier_cannot_open_station_displays(): void
    {
        $this->actingAsAtOutlet($this->cashier)
            ->get(route('kitchen.index'))
            ->assertForbidden();

        $this->actingAsAtOutlet($this->cashier)
            ->get(route('bar.index'))
            ->assertForbidden();
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
        $second = $orders->addItem($order->fresh(), ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
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
