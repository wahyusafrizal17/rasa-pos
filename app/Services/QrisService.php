<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class QrisService
{
    public function startQris(Order $order): array
    {
        if (trim((string) config('qris.api_key')) === '') {
            throw ValidationException::withMessages([
                'qris' => 'API key QRIS belum diisi di Pengaturan.',
            ]);
        }

        if ($cached = Cache::get($this->orderKey($order->id))) {
            return $cached + ['paid' => false];
        }

        $amount = (int) round((float) $order->grand_total);
        if ($amount < 1000) {
            throw ValidationException::withMessages([
                'qris' => 'Nominal QRIS minimal Rp 1.000.',
            ]);
        }

        $reference = substr((string) $order->order_number, 0, 48).'-'.now()->format('ymdHis');

        $response = Http::withHeaders([
            'X-API-KEY' => config('qris.api_key'),
            'Accept' => 'application/json',
        ])->asJson()->timeout(20)->post($this->url('/api/v1/external/qris/rdi/generate'), [
            'reference_no' => $reference,
            'amount' => $amount,
            'callback_url' => route('webhooks.qris'),
            'callback_token' => (string) config('qris.callback_token'),
            'customer_name' => $order->customer?->name ?: 'Walk-in',
            'customer_phone' => $order->customer?->phone ?: '',
            'description' => 'POS '.$order->order_number,
            'validity_minutes' => 15,
        ])->json() ?? [];

        $qrUrl = $response['data']['qr_image_url'] ?? null;
        if (($response['status'] ?? '') !== 'success' || ! $qrUrl) {
            throw ValidationException::withMessages([
                'qris' => $response['responseMessage'] ?? 'QRIS gagal dibuat.',
            ]);
        }

        $pending = [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'reference_no' => $reference,
            'faspay_reference_no' => (string) ($response['data']['reference_no'] ?? ''),
            'qr_url' => $qrUrl,
        ];

        Cache::put($this->orderKey($order->id), $pending, now()->addMinutes(30));
        Cache::put($this->refKey($reference), $order->id, now()->addMinutes(30));

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

        $inquiry = Http::withHeaders([
            'X-API-KEY' => config('qris.api_key'),
            'Accept' => 'application/json',
        ])->timeout(20)->get($this->url('/api/v1/external/qris/status/'.$pending['reference_no']))->json() ?? [];

        if (($inquiry['data']['status'] ?? '') === 'paid') {
            $this->settleByReference(
                $pending['reference_no'],
                (string) ($inquiry['data']['faspay_reference_no'] ?? $pending['faspay_reference_no'] ?? '')
            );
            $order->refresh();
        }

        return ['paid' => $order->payment_status === PaymentStatus::Paid, 'order' => $order];
    }

    public function settleFromWebhook(Request $request): JsonResponse
    {
        $expected = (string) config('qris.callback_token');
        $token = (string) $request->header('X-Callback-Token');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $signature = (string) $request->header('X-Signature');
        if ($signature !== '') {
            $check = hash_hmac('sha256', $request->header('X-Timestamp').'.'.$request->getContent(), $expected);
            if (! hash_equals($check, $signature)) {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all() ?: (json_decode($request->getContent(), true) ?: []);
        if (($payload['event'] ?? '') === 'payment.verified' && ($payload['status'] ?? '') === 'paid') {
            $this->settleByReference(
                (string) ($payload['reference_no'] ?? ''),
                (string) ($payload['faspay_reference_no'] ?? '')
            );
        }

        return response()->json(['status' => 'success']);
    }

    protected function settleByReference(string $reference, string $trxId): void
    {
        $orderId = Cache::get($this->refKey($reference));
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
            'reference' => $trxId ?: ($pending['faspay_reference_no'] ?? $reference),
            'user_id' => $pending['user_id'] ?? null,
        ]);
    }

    protected function url(string $path): string
    {
        return rtrim((string) config('qris.base_url'), '/').$path;
    }

    protected function orderKey(int $orderId): string
    {
        return 'qris.order.'.$orderId;
    }

    protected function refKey(string $reference): string
    {
        return 'qris.ref.'.$reference;
    }
}
