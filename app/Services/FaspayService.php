<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FaspayService
{
    public function startQris(Order $order): array
    {
        $order->loadMissing('customer');
        $billNo = substr('P'.$order->id.now()->format('ymdHis'), 0, 32);
        $total = (string) (int) round(((float) $order->grand_total) * 100);

        $response = Http::asJson()->post($this->url('post_url'), [
            'request' => 'Post Data Transaction',
            'merchant_id' => config('faspay.merchant_id'),
            'merchant' => config('faspay.merchant'),
            'bill_no' => $billNo,
            'bill_date' => now()->format('Y-m-d H:i:s'),
            'bill_expired' => now()->addMinutes(15)->format('Y-m-d H:i:s'),
            'bill_desc' => 'POS '.$order->order_number,
            'bill_currency' => 'IDR',
            'bill_total' => $total,
            'payment_channel' => (string) config('faspay.qris_channel'),
            'pay_type' => '1',
            'cust_no' => (string) ($order->customer_id ?: $order->id),
            'cust_name' => $order->customer?->name ?: 'Walk-in',
            'msisdn' => '081000000000',
            'email' => $order->customer?->email ?: 'pos@local',
            'terminal' => '10',
            'item' => [[
                'product' => $order->order_number,
                'qty' => '1',
                'amount' => $total,
                'payment_plan' => '01',
                'merchant_id' => config('faspay.merchant_id'),
                'tenor' => '00',
            ]],
            'signature' => $this->sign($billNo),
        ])->json() ?? [];

        if (($response['response_code'] ?? '') !== '00') {
            throw ValidationException::withMessages([
                'qris' => $response['response_desc'] ?? 'Faspay QRIS gagal dibuat.',
            ]);
        }

        $pending = [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'trx_id' => (string) ($response['trx_id'] ?? ''),
            'bill_no' => $billNo,
            'qr_url' => $response['web_url'] ?? $response['alt_url'] ?? null,
            'qr_content' => $response['qr_content'] ?? null,
        ];
        if (! $pending['qr_url'] && filled($pending['qr_content'])) {
            $pending['qr_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data='.rawurlencode($pending['qr_content']);
        }

        Cache::put($this->orderKey($order->id), $pending, now()->addMinutes(30));
        Cache::put($this->billKey($billNo), $order->id, now()->addMinutes(30));

        return $pending + ['paid' => false];
    }

    public function sync(Order $order): array
    {
        $order->refresh();
        if ($order->payment_status === PaymentStatus::Paid) {
            return ['paid' => true, 'order' => $order];
        }

        $pending = Cache::get($this->orderKey($order->id));
        if (! $pending) {
            return ['paid' => false, 'order' => $order];
        }

        $inquiry = Http::asJson()->post($this->url('inquiry_url'), [
            'request' => 'Inquiry Payment Status',
            'trx_id' => $pending['trx_id'],
            'merchant_id' => config('faspay.merchant_id'),
            'bill_no' => $pending['bill_no'],
            'signature' => $this->sign($pending['bill_no']),
        ])->json() ?? [];

        if ((string) ($inquiry['payment_status_code'] ?? '') === '2') {
            $this->settleByBill($pending['bill_no'], $pending['trx_id']);
            $order->refresh();
        }

        return ['paid' => $order->payment_status === PaymentStatus::Paid, 'order' => $order];
    }

    public function settleFromNotify(array $payload): array
    {
        $billNo = (string) ($payload['bill_no'] ?? '');
        $status = (string) ($payload['payment_status_code'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');

        if ($billNo === '' || ! hash_equals($this->notifySign($billNo, $status), $signature)) {
            return $this->notifyReply($payload, '01', 'Invalid signature');
        }

        if ($status === '2') {
            $this->settleByBill($billNo, (string) ($payload['trx_id'] ?? ''));
        }

        return $this->notifyReply($payload, '00', 'Success');
    }

    protected function settleByBill(string $billNo, string $trxId): void
    {
        $orderId = Cache::get($this->billKey($billNo));
        if (! $orderId) {
            return;
        }

        $order = Order::query()->find($orderId);
        if (! $order || $order->payment_status === PaymentStatus::Paid) {
            return;
        }

        $pending = Cache::get($this->orderKey($order->id), []);
        app(OrderService::class)->checkout($order, [
            'method' => PaymentMethod::Qris->value,
            'amount' => (float) $order->grand_total,
            'tendered' => (float) $order->grand_total,
            'reference' => $trxId ?: ($pending['trx_id'] ?? null),
            'user_id' => $pending['user_id'] ?? null,
        ]);
    }

    protected function sign(string $billNo): string
    {
        return sha1(md5(config('faspay.user').config('faspay.password').$billNo));
    }

    protected function notifySign(string $billNo, string $status): string
    {
        return sha1(md5(config('faspay.user').config('faspay.password').$billNo.$status));
    }

    protected function notifyReply(array $payload, string $code, string $desc): array
    {
        return [
            'response' => 'Payment Notification',
            'trx_id' => $payload['trx_id'] ?? '',
            'merchant_id' => config('faspay.merchant_id'),
            'bill_no' => $payload['bill_no'] ?? '',
            'response_code' => $code,
            'response_desc' => $desc,
            'response_date' => now()->format('Y-m-d H:i:s'),
        ];
    }

    protected function url(string $key): string
    {
        return config('faspay.'.$key)[(bool) config('faspay.sandbox')] ?? config('faspay.'.$key)[true];
    }

    protected function orderKey(int $orderId): string
    {
        return 'faspay.qris.'.$orderId;
    }

    protected function billKey(string $billNo): string
    {
        return 'faspay.bill.'.$billNo;
    }
}
