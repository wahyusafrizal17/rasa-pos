<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ZohoBooksPush
{
    protected const SENDABLE = [PaymentStatus::Paid, PaymentStatus::Partial, PaymentStatus::Unpaid];

    public function __construct(protected ZohoClient $zoho) {}

    /** @param  bool  $includeUnpaid  order belum lunas ikut dikirim (SO + Invoice tanpa Payment) */
    public function push(Order $order, bool $includeUnpaid = false): void
    {
        $sendable = $includeUnpaid ? self::SENDABLE : [PaymentStatus::Paid];
        if (! in_array($order->payment_status, $sendable, true) || $order->zoho_payment_id) {
            return;
        }

        if (trim((string) config('zoho.customer_id')) === '') {
            return;
        }

        $order->loadMissing(['items.product', 'outlet', 'payments', 'customer']);

        if (! $order->zoho_contact_id) {
            $this->resolveContact($order);
        }

        if (! $order->zoho_salesorder_id) {
            $this->createSalesOrder($order);
        }

        if (! $order->zoho_invoice_id) {
            $this->createInvoice($order);
        }

        // compliment: invoice diskon 100%, tidak ada uang masuk; belum lunas: tercatat piutang di Zoho
        if ($order->is_compliment || $order->payment_status !== PaymentStatus::Paid) {
            return;
        }

        if (! $order->zoho_payment_id) {
            $this->createPayment($order);
        }
    }

    public function pending(): void
    {
        $this->pendingQuery()->each(fn (Order $order) => $this->push($order));
    }

    /**
     * Order yang boleh dikirim ke Zoho. Default hanya lunas; draft, held, dan batal tidak pernah ikut.
     *
     * @param  array{from?: ?string, to?: ?string, payment_status?: ?string}  $filters  tanggal transaksi (WIB); payment_status: paid|partial|unpaid|all
     */
    public function pushableQuery(array $filters = []): Builder
    {
        $payment = $filters['payment_status'] ?? PaymentStatus::Paid->value;

        return Order::query()
            ->whereIn('payment_status', $payment === 'all' ? self::SENDABLE : [$payment])
            ->whereNotIn('status', [OrderStatus::Draft, OrderStatus::Held, OrderStatus::Cancelled])
            ->when($filters['from'] ?? null, fn ($q, $day) => $q->where('created_at', '>=', wib_day_bounds($day)[0]))
            ->when($filters['to'] ?? null, fn ($q, $day) => $q->where('created_at', '<=', wib_day_bounds($day)[1]));
    }

    /** Order yang belum selesai terkirim ke Zoho. */
    public function pendingQuery(array $filters = []): Builder
    {
        return $this->pushableQuery($filters)
            ->whereNull('zoho_payment_id')
            ->where(fn ($q) => $q->whereNull('zoho_invoice_id')
                ->orWhere(fn ($q) => $q->where('payment_status', PaymentStatus::Paid)->where('is_compliment', false)));
    }

    /**
     * Lupakan ID Zoho supaya order dikirim ulang dari awal (setelah data di Zoho dibersihkan).
     * ID kontak customer selalu direset: kontak dicari lagi by name, jadi aman untuk filter apa pun.
     */
    public function resetAll(array $filters = []): int
    {
        Customer::query()->whereNotNull('zoho_contact_id')->update(['zoho_contact_id' => null]);

        return $this->pushableQuery($filters)->update([
            'zoho_contact_id' => null,
            'zoho_salesorder_id' => null,
            'zoho_invoice_id' => null,
            'zoho_payment_id' => null,
        ]);
    }

    protected function sourceField(): array
    {
        return [['api_name' => 'cf_source', 'value' => config('zoho.cf_source', 'POS')]];
    }

    protected function wibDate(?\DateTimeInterface $at): string
    {
        return wib($at ?? now(), 'Y-m-d');
    }

    /**
     * Kontak Zoho sesuai nama customer di POS: pakai yang sudah ada (nama sama), kalau belum ada dibuat.
     * Order tanpa nama tetap ke kontak default ZOHO_CUSTOMER_ID.
     */
    protected function resolveContact(Order $order): void
    {
        $customer = $order->customer;
        $name = trim((string) ($customer?->name ?: $order->guest_name));

        $contactId = $customer?->zoho_contact_id;
        if (! $contactId && $name !== '') {
            $contactId = $this->findContact($name) ?? $this->createContact($name, $customer);
            $customer?->update(['zoho_contact_id' => $contactId]);
        }

        $order->update(['zoho_contact_id' => $contactId ?: (string) config('zoho.customer_id')]);
    }

    protected function findContact(string $name): ?string
    {
        $contacts = $this->zoho->get('/books/v3/contacts', [
            'contact_name' => $name,
            'contact_type' => 'customer',
        ])['contacts'] ?? [];

        foreach ($contacts as $contact) {
            if (mb_strtolower(trim((string) ($contact['contact_name'] ?? ''))) === mb_strtolower($name)) {
                return (string) $contact['contact_id'];
            }
        }

        return null;
    }

    protected function createContact(string $name, ?Customer $customer): string
    {
        $payload = array_filter([
            'contact_name' => $name,
            'contact_type' => 'customer',
            'customer_sub_type' => 'individual',
            'phone' => $customer?->phone,
            'email' => $customer?->email,
        ], fn ($value) => filled($value));

        $contactId = (string) ($this->zoho->post('/books/v3/contacts', $payload)['contact']['contact_id'] ?? '');
        if ($contactId === '') {
            throw new RuntimeException('Zoho contact for "'.$name.'" was not created');
        }

        return $contactId;
    }

    protected function customerId(Order $order): string
    {
        return (string) ($order->zoho_contact_id ?: config('zoho.customer_id'));
    }

    protected function createSalesOrder(Order $order): void
    {
        $lines = $this->lineItems($order);
        if ($lines === []) {
            throw new RuntimeException('Order '.$order->order_number.' has no Zoho items');
        }

        $payload = $this->withDiscount($order, [
            'customer_id' => $this->customerId($order),
            'date' => $this->wibDate($order->created_at),
            'reference_number' => $order->order_number,
            'custom_fields' => $this->sourceField(),
            'line_items' => $lines,
        ]);

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
            $lines[] = $line + ['salesorder_item_id' => (string) $soLine['line_item_id']];
        }

        if ($lines === []) {
            throw new RuntimeException('Zoho SO '.$order->zoho_salesorder_id.' has no line items');
        }

        $invoice = $this->zoho->post('/books/v3/invoices', $this->withDiscount($order, [
            'customer_id' => $this->customerId($order),
            'reference_number' => $order->order_number,
            'date' => $this->wibDate($order->created_at),
            'custom_fields' => $this->sourceField(),
            'line_items' => $lines,
        ]))['invoice'] ?? [];

        $order->update(['zoho_invoice_id' => (string) ($invoice['invoice_id'] ?? '')]);
        $this->zoho->post('/books/v3/invoices/'.$order->zoho_invoice_id.'/status/sent');
    }

    protected function createPayment(Order $order): void
    {
        $method = $order->payments->sortByDesc('id')->first()?->method ?? PaymentMethod::Cash;
        $amount = (float) $order->grand_total;

        $payment = $this->zoho->post('/books/v3/customerpayments', [
            'customer_id' => $this->customerId($order),
            'payment_mode' => $this->paymentMode($method),
            'account_id' => (string) config('zoho.payment_account_id'),
            'amount' => $amount,
            'date' => $this->wibDate($order->payments->sortByDesc('id')->first()?->created_at ?? $order->created_at),
            'reference_number' => $order->payments->sortByDesc('id')->first()?->reference ?: $order->order_number,
            'invoices' => [[
                'invoice_id' => $order->zoho_invoice_id,
                'amount_applied' => $amount,
            ]],
        ])['payment'] ?? [];

        $order->update(['zoho_payment_id' => (string) ($payment['payment_id'] ?? '')]);
    }

    protected function withDiscount(Order $order, array $payload): array
    {
        $discount = round((float) $order->discount_amount, 2);
        if ($discount <= 0) {
            return $payload;
        }

        $payload['discount'] = $discount;
        $payload['discount_type'] = 'entity_level';
        $payload['is_discount_before_tax'] = true;

        return $payload;
    }

    protected function lineItems(Order $order): array
    {
        $taxId = $this->taxId($order);
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
                'tax_id' => $taxId,
            ];
        }

        return $lines;
    }

    /** Pajak Zoho harus setarif dengan order, kalau tidak total invoice beda dengan pembayaran POS. */
    protected function taxId(Order $order): string
    {
        $rate = (float) $order->tax_rate;
        if ($rate <= 0) {
            return '';
        }

        $taxes = Cache::remember('zoho.taxes', 3600, fn () => $this->zoho->get('/books/v3/settings/taxes')['taxes'] ?? []);
        $ids = collect($taxes)
            ->filter(fn (array $tax) => abs((float) ($tax['tax_percentage'] ?? -1) - $rate) < 0.001)
            ->pluck('tax_id')
            ->map(fn ($id) => (string) $id);

        if ($ids->isEmpty()) {
            throw new RuntimeException("Pajak {$rate}% tidak ada di Zoho Books.");
        }

        $configured = (string) config('zoho.tax_id');

        return $ids->contains($configured) ? $configured : $ids->first();
    }

    protected function paymentMode(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::Cash => 'Cash',
            PaymentMethod::Edc => 'creditcard',
            PaymentMethod::Transfer => 'banktransfer',
            PaymentMethod::Qris, PaymentMethod::Compliment => 'others',
        };
    }
}
