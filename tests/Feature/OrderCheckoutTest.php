<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\CustomerPoint;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
    }

    public function test_checkout_completes_order_decreases_stock_and_earns_points(): void
    {
        $this->actingAsAtOutlet($this->cashier);

        $service = app(OrderService::class);
        $stockBefore = (float) Inventory::query()
            ->where('outlet_id', $this->outlet->id)
            ->where('product_id', $this->sellableProduct->id)
            ->value('quantity');

        $order = $service->createDraft([
            'outlet_id' => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'order_type' => OrderType::Pickup->value,
            'guest_count' => 1,
        ]);

        $this->assertSame(OrderStatus::Draft, $order->status);

        $service->addItem($order, [
            'product_id' => $this->sellableProduct->id,
            'quantity' => 2,
        ]);

        $order = $service->checkout($order->fresh(), [
            'method' => PaymentMethod::Cash->value,
            'tendered' => 100000,
        ]);

        $this->assertSame(OrderStatus::Completed, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertTrue(
            Payment::query()->where('order_id', $order->id)->where('status', 'paid')->exists()
        );

        $expectedSubtotal = 35000 * 2;
        $expectedTax = round($expectedSubtotal * 0.11, 2);
        $this->assertEquals($expectedSubtotal, (float) $order->subtotal);
        $this->assertEquals($expectedSubtotal + $expectedTax, (float) $order->grand_total);

        $stockAfter = (float) Inventory::query()
            ->where('outlet_id', $this->outlet->id)
            ->where('product_id', $this->sellableProduct->id)
            ->value('quantity');
        $this->assertEquals($stockBefore - 2, $stockAfter);

        $this->assertTrue(
            InventoryMovement::query()
                ->where('outlet_id', $this->outlet->id)
                ->where('product_id', $this->sellableProduct->id)
                ->where('type', StockMovementType::Sale)
                ->where('reference_id', $order->id)
                ->exists()
        );

        $this->customer->refresh();
        $expectedPoints = (int) floor(((float) $order->grand_total) / 10000);
        $this->assertSame($expectedPoints, (int) $this->customer->points);
        $this->assertTrue(
            CustomerPoint::query()
                ->where('customer_id', $this->customer->id)
                ->where('order_id', $order->id)
                ->where('type', 'earn')
                ->exists()
        );
        $this->assertEquals((float) $order->grand_total, (float) $this->customer->total_transaction);
        $this->assertNotNull($this->customer->last_transaction_at);
    }
}
