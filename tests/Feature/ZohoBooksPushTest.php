<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\OrderService;
use App\Services\ZohoBooksPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class ZohoBooksPushTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
        $this->outlet->update(['zoho_location_id' => 'loc-1']);
        $this->sellableProduct->update(['zoho_item_id' => 'item-1']);
        config([
            'zoho.client_id' => 'cid',
            'zoho.client_secret' => 'secret',
            'zoho.refresh_token' => 'refresh',
            'zoho.organization_id' => '901189625',
            'zoho.token_url' => 'https://accounts.zoho.com/oauth/v2/token',
            'zoho.base_url' => 'https://www.zohoapis.com',
            'zoho.customer_id' => 'cust-1',
            'zoho.cf_source' => 'POS',
        ]);
    }

    public function test_paid_checkout_creates_zoho_salesorder_invoice_and_payment(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $this->fakeZohoBooks();

        $service = app(OrderService::class);
        $order = $service->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $service->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 2]);
        $order = $service->checkout($order->fresh(), [
            'method' => PaymentMethod::Cash->value,
            'tendered' => 100000,
        ]);

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $order->refresh();
        $this->assertSame('so-1', $order->zoho_salesorder_id);
        $this->assertSame('inv-1', $order->zoho_invoice_id);
        $this->assertSame('pay-1', $order->zoho_payment_id);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/books/v3/salesorders')
            && ! str_contains($request->url(), '/status/')
            && $request['customer_id'] === 'cust-1'
            && $request['reference_number'] === $order->order_number
            && $request['location_id'] === 'loc-1'
            && $request['line_items'][0]['item_id'] === 'item-1'
            && (float) $request['line_items'][0]['quantity'] === 2.0
            && $request['custom_fields'][0]['api_name'] === 'cf_source'
            && $request['custom_fields'][0]['value'] === 'POS');

        Http::assertSent(fn ($request) => $request->url() === 'https://www.zohoapis.com/books/v3/salesorders/so-1/status/open'
            || str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/salesorders/so-1/status/open'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/books/v3/invoices')
            && ! str_contains($request->url(), '/status/')
            && $request['line_items'][0]['salesorder_item_id'] === 'li-1');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/invoices/inv-1/status/sent'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/books/v3/customerpayments')
            && $request['payment_mode'] === 'cash'
            && $request['invoices'][0]['invoice_id'] === 'inv-1');
    }

    public function test_second_push_does_not_create_another_salesorder(): void
    {
        $this->actingAsAtOutlet($this->cashier);
        $this->fakeZohoBooks();

        $service = app(OrderService::class);
        $order = $service->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $service->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);
        $order = $service->checkout($order->fresh(), [
            'method' => PaymentMethod::Transfer->value,
            'tendered' => 100000,
        ]);

        app(ZohoBooksPush::class)->push($order->fresh());

        $soCreates = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), '/books/v3/salesorders')
                && $pair[0]->method() === 'POST'
                && ! str_contains($pair[0]->url(), '/status/'))
            ->count();

        $this->assertSame(1, $soCreates);
        $this->assertSame('so-1', $order->fresh()->zoho_salesorder_id);
    }

    protected function fakeZohoBooks(): void
    {
        Http::fake([
            'accounts.zoho.com/oauth/v2/token' => Http::response([
                'access_token' => 'tok_test',
                'expires_in' => 3600,
            ], 200),
            'www.zohoapis.com/books/v3/salesorders/*/status/open*' => Http::response(['code' => 0], 200),
            'www.zohoapis.com/books/v3/invoices/*/status/sent*' => Http::response(['code' => 0], 200),
            'www.zohoapis.com/books/v3/customerpayments*' => Http::response([
                'code' => 0,
                'payment' => ['payment_id' => 'pay-1'],
            ], 200),
            'www.zohoapis.com/books/v3/invoices*' => Http::response([
                'code' => 0,
                'invoice' => ['invoice_id' => 'inv-1', 'balance' => 77700],
            ], 200),
            'www.zohoapis.com/books/v3/salesorders*' => Http::response([
                'code' => 0,
                'salesorder' => [
                    'salesorder_id' => 'so-1',
                    'line_items' => [
                        ['line_item_id' => 'li-1', 'item_id' => 'item-1'],
                    ],
                ],
            ], 200),
        ]);
    }
}
