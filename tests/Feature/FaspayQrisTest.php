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

class FaspayQrisTest extends TestCase
{
    use RefreshDatabase;
    use SeedsPosFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPosFixture();
        config([
            'faspay.merchant_id' => '99999',
            'faspay.merchant' => 'Rasa POS',
            'faspay.user' => 'bot99999',
            'faspay.password' => 'p@ssword',
            'faspay.qris_channel' => '702',
            'faspay.sandbox' => true,
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
        Http::fake([
            'debit-sandbox.faspay.co.id/*' => Http::response([
                'response_code' => '00',
                'trx_id' => '1234567890123456',
                'web_url' => 'https://cdn.example/qr.png',
                'qr_content' => '00020156ID.CO.QRIS',
            ], 200),
        ]);

        $order = $this->draftOrder();
        $this->postJson(route('pos.qris', $order))
            ->assertOk()
            ->assertJsonPath('qr_url', 'https://cdn.example/qr.png')
            ->assertJsonPath('paid', false);

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_faspay_webhook_pays_qris_order(): void
    {
        Http::fake([
            'debit-sandbox.faspay.co.id/*' => Http::response([
                'response_code' => '00',
                'trx_id' => '1234567890123456',
                'web_url' => 'https://cdn.example/qr.png',
            ], 200),
        ]);

        $order = $this->draftOrder();
        $billNo = $this->postJson(route('pos.qris', $order))->assertOk()->json('bill_no');
        $sig = sha1(md5('bot99999'.'p@ssword'.$billNo.'2'));

        $this->postJson(route('webhooks.faspay'), [
            'trx_id' => '1234567890123456',
            'merchant_id' => '99999',
            'bill_no' => $billNo,
            'payment_status_code' => '2',
            'signature' => $sig,
        ])->assertOk()->assertJsonPath('response_code', '00');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(PaymentMethod::Qris, $order->payments->first()->method);
    }

    public function test_faspay_webhook_rejects_bad_signature(): void
    {
        Http::fake([
            'debit-sandbox.faspay.co.id/*' => Http::response([
                'response_code' => '00',
                'trx_id' => '1234567890123456',
                'web_url' => 'https://cdn.example/qr.png',
            ], 200),
        ]);

        $order = $this->draftOrder();
        $billNo = $this->postJson(route('pos.qris', $order))->json('bill_no');

        $this->postJson(route('webhooks.faspay'), [
            'trx_id' => '1234567890123456',
            'merchant_id' => '99999',
            'bill_no' => $billNo,
            'payment_status_code' => '2',
            'signature' => 'deadbeef',
        ])->assertOk();

        $this->assertSame(PaymentStatus::Unpaid, $order->fresh()->payment_status);
    }

    public function test_qris_status_settles_after_faspay_inquiry_success(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '100004')) {
                return Http::response([
                    'response_code' => '00',
                    'payment_status_code' => '2',
                    'trx_id' => '1234567890123456',
                ], 200);
            }

            return Http::response([
                'response_code' => '00',
                'trx_id' => '1234567890123456',
                'web_url' => 'https://cdn.example/qr.png',
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
