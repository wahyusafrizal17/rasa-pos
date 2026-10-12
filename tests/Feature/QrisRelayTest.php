<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\SeedsPosFixture;
use Tests\TestCase;

class QrisRelayTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
        config([
            'qris.base_url' => 'https://rasaconnect.com',
            'qris.api_key' => 'test-key',
            'qris.callback_token' => 'cb-token',
        ]);
    }

    protected function draftOrder()
    {
        $this->actingAsAtOutlet($this->cashier);
        $service = app(OrderService::class);
        $order = $service->createDraft([
            'outlet_id' => $this->outlet->id,
            'order_type' => OrderType::Pickup->value,
        ]);
        $service->addItem($order, ['product_id' => $this->sellableProduct->id, 'quantity' => 1]);

        return $order->fresh();
    }

    protected function fakeGenerate(): void
    {
        Http::fake([
            'rasaconnect.com/api/v1/external/qris/rdi/generate' => Http::response([
                'status' => 'success',
                'responseCode' => '2004700',
                'data' => [
                    'partner_reference_no' => 'ORD-TEST',
                    'reference_no' => '3702083645902822',
                    'qr_image_url' => 'https://web.faspay.co.id/__assets/qr/paydia/qr.png',
                    'amount' => '38850.00',
                ],
            ], 200),
            'rasaconnect.com/api/v1/external/qris/status/*' => Http::response([
                'status' => 'success',
                'data' => ['status' => 'pending'],
            ], 200),
        ]);
    }

    public function test_qris_checkout_is_not_instant(): void
    {
        $order = $this->draftOrder();
        $this->postJson(route('pos.checkout', $order), [
            'method' => 'qris',
            'tendered' => 50000,
        ])->assertUnprocessable();
        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_starting_qris_returns_qr_without_marking_paid(): void
    {
        $this->fakeGenerate();
        $order = $this->draftOrder();

        $this->postJson(route('pos.qris', $order))
            ->assertOk()
            ->assertJsonPath('qr_url', 'https://web.faspay.co.id/__assets/qr/paydia/qr.png')
            ->assertJsonPath('paid', false);

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);

        Http::assertSent(function ($request) use ($order) {
            return str_contains($request->url(), '/api/v1/external/qris/rdi/generate')
                && $request->hasHeader('X-API-KEY', 'test-key')
                && (int) $request['amount'] === (int) round((float) $order->fresh()->grand_total)
                && filled($request['reference_no'])
                && filled($request['callback_url']);
        });
    }

    public function test_qris_webhook_pays_order(): void
    {
        $this->fakeGenerate();
        $order = $this->draftOrder();
        $reference = $this->postJson(route('pos.qris', $order))->assertOk()->json('reference_no');

        $payload = [
            'event' => 'payment.verified',
            'status' => 'paid',
            'reference_no' => $reference,
            'faspay_reference_no' => '3702083645902822',
            'amount' => (int) round((float) $order->grand_total),
        ];
        $body = json_encode($payload);
        $timestamp = '1791708522';

        $this->call('POST', route('webhooks.qris'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CALLBACK_TOKEN' => 'cb-token',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$body, 'cb-token'),
            'HTTP_X_EVENT' => 'payment.verified',
        ], $body)->assertOk()->assertJsonPath('status', 'success');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(PaymentMethod::Qris, $order->payments->first()->method);
    }

    public function test_qris_webhook_rejects_bad_token(): void
    {
        $this->fakeGenerate();
        $order = $this->draftOrder();
        $reference = $this->postJson(route('pos.qris', $order))->json('reference_no');

        $this->withHeaders(['X-Callback-Token' => 'wrong'])->postJson(route('webhooks.qris'), [
            'event' => 'payment.verified',
            'status' => 'paid',
            'reference_no' => $reference,
        ])->assertUnauthorized();

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_qris_status_settles_after_inquiry_paid(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/qris/status/')) {
                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'status' => 'paid',
                        'faspay_reference_no' => '3702083645902822',
                    ],
                ], 200);
            }

            return Http::response([
                'status' => 'success',
                'data' => [
                    'qr_image_url' => 'https://web.faspay.co.id/__assets/qr/paydia/qr.png',
                    'reference_no' => '3702083645902822',
                ],
            ], 200);
        });

        $order = $this->draftOrder();
        $this->postJson(route('pos.qris', $order))->assertOk();

        $this->getJson(route('pos.qris.status', $order))
            ->assertOk()
            ->assertJsonPath('paid', true)
            ->assertJsonPath('order.payment_status', 'paid');
    }
}
