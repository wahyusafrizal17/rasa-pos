<?php

namespace App\Http\Controllers;

use App\Console\Commands\RepushZohoOrders;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Outlet;
use App\Services\OrderService;
use App\Services\ZohoBooksPush;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function repushZoho(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate(['mode' => ['required', 'in:all,pending']] + $this->repushRules(), $this->repushMessages());
        $filters = array_filter(array_intersect_key($data, array_flip(['from', 'to', 'payment_status'])));

        if (! $this->repushIdle()) {
            return back()->with('success', 'Push ulang ke Zoho masih berjalan.');
        }

        $args = $data['mode'] === 'all' ? ' --reset' : '';
        foreach ($filters as $key => $value) {
            $args .= ' --'.str_replace('_', '-', $key).'='.escapeshellarg($value);
        }
        $command = sprintf(
            'cd %s && setsid %s %s zoho:repush-orders%s >> %s 2>&1 < /dev/null & echo $!',
            escapeshellarg(base_path()),
            escapeshellarg($this->phpCliBinary()),
            escapeshellarg(base_path('artisan')),
            $args,
            escapeshellarg(storage_path('logs/zoho-repush.log')),
        );

        if (app()->runningUnitTests()) {
            Cache::put('zoho-repush-orders.spawned', $command, 60);
        } elseif (! function_exists('exec')) {
            return back()->with('error', 'Server tidak bisa menjalankan proses background. Jalankan: php artisan zoho:repush-orders'.$args);
        } else {
            exec($command);
            Log::info('Zoho repush spawned from orders page', ['mode' => $data['mode'], 'user_id' => $request->user()->id]);
        }

        return back()->with('success', $data['mode'] === 'all'
            ? 'Push ulang semua order ke Zoho dimulai. Progres tampil di halaman ini.'
            : 'Pengiriman order yang belum terkirim ke Zoho dimulai.');
    }

    /** Jumlah order yang akan dikirim ulang untuk filter di modal. */
    public function repushPreview(Request $request, ZohoBooksPush $push): JsonResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $filters = array_filter($request->validate($this->repushRules(), $this->repushMessages()));

        return response()->json(['count' => $push->pushableQuery($filters)->count()]);
    }

    protected function repushRules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'payment_status' => ['nullable', 'in:paid,partial,unpaid,all'],
        ];
    }

    protected function repushMessages(): array
    {
        return [
            'from.date_format' => 'Tanggal mulai tidak valid.',
            'to.date_format' => 'Tanggal sampai tidak valid.',
            'to.after_or_equal' => 'Tanggal sampai tidak boleh sebelum tanggal mulai.',
            'payment_status.in' => 'Status bayar tidak valid.',
        ];
    }

    protected function phpCliBinary(): string
    {
        $binary = PHP_BINARY;
        if ($binary !== '' && ! str_contains(strtolower($binary), 'fpm') && is_executable($binary)) {
            return $binary;
        }

        foreach (['/usr/bin/php', '/usr/local/bin/php'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('orders.view'), 403);

        $orders = Order::query()
            ->with(['outlet', 'customer', 'table', 'user'])
            ->when($request->order_number, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->when($request->date, function ($q, $d) {
                [$from, $to] = wib_day_bounds($d);

                return $q->whereBetween('created_at', [$from, $to]);
            })
            ->when($request->outlet_id, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($request->customer, fn ($q, $s) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")))
            ->when($request->table, fn ($q, $s) => $q->whereHas('table', fn ($t) => $t->where('code', 'like', "%{$s}%")))
            ->when($request->order_type, fn ($q, $t) => $q->where('order_type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->payment_status, fn ($q, $s) => $q->where('payment_status', $s))
            ->when($request->cashier, fn ($q, $s) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        [$todayFrom, $todayTo] = wib_day_bounds();

        return view('orders.index', [
            'orders' => $orders,
            'outlets' => Outlet::query()->orderBy('name')->get(),
            'filters' => $request->only([
                'order_number', 'date', 'outlet_id', 'customer', 'table',
                'order_type', 'status', 'payment_status', 'cashier',
            ]),
            'stats' => [
                'total' => Order::query()->count(),
                'today' => Order::query()->whereBetween('created_at', [$todayFrom, $todayTo])->count(),
                'kitchen' => Order::query()->whereIn('status', [
                    OrderStatus::New->value,
                    OrderStatus::Processing->value,
                    OrderStatus::Preparing->value,
                ])->count(),
            ],
            'zoho' => $request->user()->hasPermission('settings.manage') ? [
                'paid' => app(ZohoBooksPush::class)->pushableQuery()->count(),
                'pending' => app(ZohoBooksPush::class)->pendingQuery()->count(),
                'running' => ! $this->repushIdle(),
                'progress' => Cache::get(RepushZohoOrders::PROGRESS),
            ] : null,
        ]);
    }

    protected function repushIdle(): bool
    {
        $probe = Cache::lock(RepushZohoOrders::LOCK, 1);
        if (! $probe->get()) {
            return false;
        }
        $probe->release();

        return true;
    }

    public function show(Order $order): View
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);

        return view('orders.show', [
            'order' => $order->load(['items.product', 'items.batch', 'payments', 'customer', 'outlet', 'table', 'user', 'discount']),
        ]);
    }

    public function status(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->can('orders.manage') || $request->user()->can('orders.check'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,processing,preparing,ready,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'ready' && ! $order->allItemsReady()) {
            throw ValidationException::withMessages([
                'status' => 'Tandai semua item siap di Daftar item sebelum lanjut ke Siap.',
            ]);
        }

        $orders->transition($order, OrderStatus::from($data['status']), $data['notes'] ?? null);

        return back()->with('success', 'Status order diperbarui.');
    }
}
