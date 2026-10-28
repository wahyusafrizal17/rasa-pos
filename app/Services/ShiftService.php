<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public const AUTO_CLOSE_NOTE = 'Ditutup otomatis sesuai jadwal';

    public function __construct(protected ShiftSchedule $schedule) {}

    /** @return list<string> */
    public function labels(int $outletId): array
    {
        $rows = $this->schedule->forOutlet($outletId);

        return $rows ? array_column($rows, 'label') : Shift::LABELS;
    }

    /** Label shift yang jadwalnya sedang berjalan, untuk default pilihan saat buka manual. */
    public function currentLabel(int $outletId): ?string
    {
        return $this->schedule->windowAt($this->schedule->forOutlet($outletId), now())['label'] ?? null;
    }

    public function open(int $outletId, string $label, ?string $note = null): Shift
    {
        return DB::transaction(function () use ($outletId, $label, $note) {
            $active = Shift::query()->where('outlet_id', $outletId)->open()->lockForUpdate()->first();
            if ($active) {
                throw ValidationException::withMessages([
                    'shift' => $active->label.' masih terbuka. Tutup dulu sebelum membuka shift baru.',
                ]);
            }

            return Shift::query()->create([
                'outlet_id' => $outletId,
                'label' => $label,
                'opened_by' => Auth::id(),
                'opened_at' => now(),
                'scheduled_end' => $this->schedule->endFor($this->schedule->forOutlet($outletId), $label, now()),
                'opening_note' => $note,
            ]);
        });
    }

    /**
     * Tutup shift yang sudah lewat jadwal, lalu buka shift jadwal sekarang.
     * Shift yang ditutup lebih awal oleh kasir tidak dibuka ulang di jam yang sama.
     */
    public function rotate(int $outletId): ?Shift
    {
        $active = Shift::activeFor($outletId);
        if ($active?->scheduled_end && now()->gte($active->scheduled_end)) {
            $this->close($active, self::AUTO_CLOSE_NOTE, $active->scheduled_end);
            $active = null;
        }

        if ($active) {
            return $active;
        }

        $window = $this->schedule->windowAt($this->schedule->forOutlet($outletId), now());
        if (! $window) {
            return null;
        }

        $alreadyRan = Shift::query()
            ->where('outlet_id', $outletId)
            ->where('opened_at', '>=', $window['start'])
            ->exists();
        if ($alreadyRan) {
            return null;
        }

        try {
            return DB::transaction(function () use ($outletId, $window) {
                if (Shift::query()->where('outlet_id', $outletId)->open()->lockForUpdate()->exists()) {
                    return Shift::activeFor($outletId);
                }

                // mulai dari awal jadwal (atau sejak shift sebelumnya ditutup) supaya tidak ada transaksi yang bolong
                $lastClosed = Shift::query()->where('outlet_id', $outletId)->max('closed_at');
                $openedAt = $lastClosed && Carbon::parse($lastClosed)->gt($window['start'])
                    ? Carbon::parse($lastClosed)
                    : $window['start'];

                return Shift::query()->create([
                    'outlet_id' => $outletId,
                    'label' => $window['label'],
                    'opened_by' => null,
                    'opened_at' => $openedAt,
                    'scheduled_end' => $window['end'],
                    'opening_note' => 'Dibuka otomatis sesuai jadwal',
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return Shift::activeFor($outletId);
        }
    }

    public function close(Shift $shift, ?string $note = null, ?Carbon $closedAt = null): Shift
    {
        return DB::transaction(function () use ($shift, $note, $closedAt) {
            $shift = Shift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();
            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['shift' => 'Shift ini sudah ditutup.']);
            }

            $closedAt = $closedAt ?? now();
            $shift->update([
                'closed_by' => Auth::id(),
                'closed_at' => $closedAt,
                'closing_note' => $note,
                'summary' => $this->report($shift, $closedAt),
            ]);

            return $shift->fresh(['outlet', 'opener', 'closer']);
        });
    }

    /** Laporan shift tertutup dibaca dari snapshot; shift aktif dihitung live. */
    public function summary(Shift $shift): array
    {
        return $shift->summary ?? $this->report($shift, now());
    }

    public function report(Shift $shift, Carbon $until): array
    {
        $outletId = $shift->outlet_id;
        $from = $shift->opened_at;

        $payments = Payment::query()
            ->whereBetween('payments.created_at', [$from, $until])
            ->whereHas('order', fn ($q) => $q->where('outlet_id', $outletId))
            ->get();

        $paidOrders = Order::query()
            ->whereIn('id', $payments->pluck('order_id')->unique())
            ->where('payment_status', PaymentStatus::Paid)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->get();

        $compliments = $paidOrders->where('is_compliment', true);

        $byMethod = [];
        foreach (PaymentMethod::cases() as $method) {
            $rows = $payments->filter(fn (Payment $p) => $p->method === $method);
            if ($rows->isEmpty()) {
                continue;
            }
            $byMethod[] = [
                'method' => $method->value,
                'label' => $method->label(),
                'count' => $rows->count(),
                'amount' => round((float) $rows->sum('amount'), 2),
            ];
        }

        $products = OrderItem::query()
            ->whereIn('order_id', $paidOrders->pluck('id'))
            ->selectRaw('name, SUM(quantity) as qty, SUM(total) as total')
            ->groupBy('name')
            ->orderByDesc('qty')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'qty' => round((float) $row->qty, 3),
                'total' => round((float) $row->total, 2),
            ])
            ->values()
            ->all();

        return [
            'generated_at' => $until->toIso8601String(),
            'orders' => [
                'created' => Order::query()
                    ->where('outlet_id', $outletId)
                    ->whereBetween('created_at', [$from, $until])
                    ->whereNotIn('status', [OrderStatus::Draft])
                    ->count(),
                'paid' => $paidOrders->count(),
                'cancelled' => Order::query()
                    ->where('outlet_id', $outletId)
                    ->whereBetween('cancelled_at', [$from, $until])
                    ->count(),
                'open' => Order::query()
                    ->where('outlet_id', $outletId)
                    ->whereNotIn('status', [OrderStatus::Draft, OrderStatus::Completed, OrderStatus::Cancelled])
                    ->where('payment_status', '!=', PaymentStatus::Paid)
                    ->count(),
            ],
            'sales' => [
                'subtotal' => round((float) $paidOrders->sum('subtotal'), 2),
                'discount' => round((float) $paidOrders->where('is_compliment', false)->sum('discount_amount'), 2),
                'tax' => round((float) $paidOrders->sum('tax_amount'), 2),
                'service' => round((float) $paidOrders->sum('service_charge'), 2),
                'grand_total' => round((float) $paidOrders->sum('grand_total'), 2),
            ],
            'compliment' => [
                'count' => $compliments->count(),
                'value' => round((float) $compliments->sum('subtotal'), 2),
                'orders' => $compliments->map(fn (Order $order) => [
                    'order_number' => $order->order_number,
                    'guest' => $order->compliment_guest,
                    'reason' => $order->compliment_reason,
                    'value' => round((float) $order->subtotal, 2),
                ])->values()->all(),
            ],
            'payments' => $byMethod,
            'products' => $products,
            'stock' => $this->stock($outletId, $from, $until),
        ];
    }

    protected function stock(int $outletId, Carbon $from, Carbon $until): array
    {
        $types = array_map(fn (StockMovementType $type) => $type->value, StockMovementType::cases());

        return InventoryMovement::query()
            ->with(['product.unit'])
            ->where('outlet_id', $outletId)
            ->whereBetween('created_at', [$from, $until])
            ->orderBy('id')
            ->get()
            ->groupBy('product_id')
            ->map(function ($rows) use ($types) {
                $first = $rows->first();
                $last = $rows->last();
                $moves = array_fill_keys($types, 0.0);
                foreach ($rows as $row) {
                    $moves[$row->type->value] += (float) $row->quantity;
                }

                return [
                    'name' => $first->product?->name ?? '#'.$first->product_id,
                    'unit' => $first->product?->unit?->code ?? '',
                    'opening' => round((float) $first->before_stock, 3),
                    'closing' => round((float) $last->after_stock, 3),
                    'moves' => array_map(fn ($qty) => round($qty, 3), $moves),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }
}
