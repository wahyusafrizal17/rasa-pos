<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use RuntimeException;

class ZohoBooksPush
{
    public function __construct(protected ZohoClient $zoho) {}

    public function push(Order $order): void
    {
        if ($order->payment_status !== PaymentStatus::Paid || $order->zoho_payment_id) {
            return;
        }

        if (trim((string) config('zoho.customer_id')) === '') {
            return;
        }

        $order->loadMissing(['items.product', 'outlet', 'payments']);

        if (! $order->zoho_salesorder_id) {
            $this->createSalesOrder($order);
        }

        if (! $order->zoho_invoice_id) {
            $this->createInvoice($order);
        }

        if (! $order->zoho_payment_id) {
            $this->createPayment($order);
        }
    }

    public function pending(): void
    {
        Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNull('zoho_payment_id')
            ->each(fn (Order $order) => $this->push($order));
    }

    protected function createSalesOrder(Order $order): void
    {
        $lines = $this->lineItems($order);
        if ($lines === []) {
            throw new RuntimeException('Order '.$order->order_number.' has no Zoho items');
        }

        $payload = [
            'customer_id' => config('zoho.customer_id'),
            'date' => $order->created_at?->toDateString() ?? now()->toDateString(),
            'reference_number' => $order->order_number,
            'custom_fields' => [
                ['api_name' => 'cf_source', 'value' => config('zoho.cf_source', 'POS')],
            ],
            'line_items' => $lines,
        ];

        if ($order->outlet?->zoho_location_id) {
            $payload['location_id'] = $order->outlet->zoho_location_id;
        }

        $salesorder = $this->zoho->post('/books/v3/salesorders', $payload)['salesorder'] ?? [];
        $order->update(['zoho_salesorder_id' => (string) ($salesorder['salesorder_id'] ?? '')]);
        $this->zoho->post('/books/v3/salesorders/'.$order->zoho_salesorder_id.'/status/open');
    }

    protected function createInvoice(Order $order): void
    {
        $salesorder = $this->zoho->get('/books/v3/salesorders/'.$order->zoho_salesorder_id)['salesorder'] ?? [];
        $soLines = $salesorder['line_items'] ?? [];
        $lines = [];

        foreach ($this->lineItems($order) as $index => $line) {
            $soLine = $soLines[$index] ?? null;
            if (! $soLine) {
                continue;
            }
            $lines[] = [
                'item_id' => $line['item_id'],
                'quantity' => $line['quantity'],
                'rate' => $line['rate'],
                'salesorder_item_id' => (string) $soLine['line_item_id'],
            ];
        }

        if ($lines === []) {
            throw new RuntimeException('Zoho SO '.$order->zoho_salesorder_id.' has no line items');
        }

        $invoice = $this->zoho->post('/books/v3/invoices', [
            'customer_id' => config('zoho.customer_id'),
            'reference_number' => $order->order_number,
            'date' => now()->toDateString(),
            'line_items' => $lines,
        ])['invoice'] ?? [];

        $order->update(['zoho_invoice_id' => (string) ($invoice['invoice_id'] ?? '')]);
        $this->zoho->post('/books/v3/invoices/'.$order->zoho_invoice_id.'/status/sent');
    }

    protected function createPayment(Order $order): void
    {
        $method = $order->payments->sortByDesc('id')->first()?->method ?? PaymentMethod::Cash;
        $amount = (float) $order->grand_total;

        $payment = $this->zoho->post('/books/v3/customerpayments', [
            'customer_id' => config('zoho.customer_id'),
            'payment_mode' => $this->paymentMode($method),
            'amount' => $amount,
            'date' => now()->toDateString(),
            'reference_number' => $order->payments->sortByDesc('id')->first()?->reference ?: $order->order_number,
            'invoices' => [[
                'invoice_id' => $order->zoho_invoice_id,
                'amount_applied' => $amount,
            ]],
        ])['payment'] ?? [];

        $order->update(['zoho_payment_id' => (string) ($payment['payment_id'] ?? '')]);
    }

    protected function lineItems(Order $order): array
    {
        $lines = [];
        foreach ($order->items as $item) {
            $itemId = (string) ($item->product?->zoho_item_id ?? '');
            if ($itemId === '') {
                continue;
            }
            $lines[] = [
                'item_id' => $itemId,
                'quantity' => (float) $item->quantity,
                'rate' => (float) $item->unit_price,
            ];
        }

        return $lines;
    }

    protected function paymentMode(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Cash => 'cash',
            PaymentMethod::Edc => 'creditcard',
            PaymentMethod::Transfer => 'banktransfer',
            PaymentMethod::Qris => 'others',
        };
    }
}
