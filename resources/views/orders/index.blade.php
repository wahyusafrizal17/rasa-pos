@extends('layouts.app')
@section('title', 'Sales Orders')
@section('breadcrumb', 'Operasional')
@section('content')
    @php
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();
    @endphp
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Total order</p>
                <p class="stat-value">{{ number_format($stats['total']) }}</p>
                <p class="stat-hint">Semua transaksi tercatat</p>
            </div>
            <span class="stat-icon bg-[#e8f1ff] text-[#3b82f6]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5h6M9 9h6M5 5h.01M5 9h.01M5 13h14M5 17h14M5 21h14"/></svg>
            </span>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Hari ini</p>
                <p class="stat-value">{{ number_format($stats['today']) }}</p>
                <p class="stat-hint">{{ wib(now(), 'd/m/Y') }}</p>
            </div>
            <span class="stat-icon bg-[#e8f8ee] text-[#1f9d57]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Antrian dapur</p>
                <p class="stat-value">{{ number_format($stats['kitchen']) }}</p>
                <p class="stat-hint">New, proses, dan preparing</p>
            </div>
            <span class="stat-icon bg-[#fff3e8] text-[#ff9f43]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            </span>
        </div>
    </div>

    @if ($zoho)
        @php $progress = $zoho['progress']; @endphp
        <script>
            function zohoRepush() {
                return {
                    repushOpen: false, scope: 'all', from: '', to: '', paymentStatus: 'paid', count: null, error: '',
                    query() {
                        const params = new URLSearchParams();
                        if (this.scope === 'range') {
                            if (this.from) params.set('from', this.from);
                            if (this.to) params.set('to', this.to);
                        }
                        params.set('payment_status', this.paymentStatus);
                        return params;
                    },
                    open() {
                        this.repushOpen = true;
                        this.refresh();
                    },
                    async refresh() {
                        this.count = null;
                        this.error = '';
                        const response = await fetch(@json(route('orders.zoho-repush.preview')) + '?' + this.query(), { headers: { Accept: 'application/json' } });
                        const data = await response.json();
                        if (response.ok) {
                            this.count = data.count;
                        } else {
                            this.error = data.message || 'Filter tidak valid.';
                        }
                    },
                    confirmSubmit(event) {
                        if (!this.count || !confirm(`Push ulang ${this.count} order ke Zoho?\n\nID Zoho lama order ini dilupakan lalu dibuat ulang (kontak, Sales Order, Invoice, Payment). Lakukan hanya kalau data order ini di Zoho sudah dibersihkan, kalau tidak akan dobel.`)) {
                            event.preventDefault();
                        }
                    },
                };
            }
        </script>
        <div x-data="zohoRepush()">
        <div class="card mb-5">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">Sinkron Zoho Books</h5>
                    <p class="card-header-subtitle">
                        {{ number_format($zoho['paid']) }} order lunas · {{ number_format($zoho['pending']) }} belum terkirim ke Zoho.
                        @if ($zoho['running'] && $progress)
                            <span class="font-semibold text-[#ff9f43]">Sedang berjalan: {{ $progress['done'] + $progress['failed'] }}/{{ $progress['total'] }} ({{ $progress['failed'] }} gagal). Muat ulang halaman untuk melihat progres.</span>
                        @elseif ($progress && $progress['status'] === 'finished')
                            Terakhir {{ wib($progress['finished_at']) }}: {{ $progress['done'] }} terkirim, {{ $progress['failed'] }} gagal dari {{ $progress['total'] }}@if (! empty($progress['filters'])) (filter: {{ implode(', ', array_map(fn ($key, $value) => $key.' '.$value, array_keys($progress['filters']), $progress['filters'])) }})@endif.
                        @endif
                    </p>
                    @if (! $zoho['running'] && ! empty($progress['errors']))
                        <details class="mt-1 text-xs text-muted">
                            <summary class="cursor-pointer text-brand">Lihat order yang gagal</summary>
                            <ul class="mt-1 list-disc pl-5">
                                @foreach ($progress['errors'] as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </details>
                    @endif
                </div>
                <div class="card-header-actions">
                    <form method="POST" action="{{ route('orders.zoho-repush') }}">
                        @csrf
                        <input type="hidden" name="mode" value="pending">
                        <button type="submit" class="btn-ghost" @disabled($zoho['running'] || $zoho['pending'] === 0)>Kirim yang belum terkirim</button>
                    </form>
                    <button type="button" class="btn-add" @click="open()" @disabled($zoho['running'] || $zoho['paid'] === 0)>Push ulang ke Zoho</button>
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="repushOpen" x-cloak @click.self="repushOpen = false">
            <div class="crud-modal">
                <form class="flex min-h-0 flex-1 flex-col" method="POST" action="{{ route('orders.zoho-repush') }}" @submit="confirmSubmit($event)">
                    @csrf
                    <input type="hidden" name="mode" value="all">
                    <div class="crud-modal-body">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold text-heading">Push ulang ke Zoho</h3>
                                <p class="mt-1 text-[13px] text-muted">Pilih order yang akan dikirim ulang. Order draft, held, dan batal tidak ikut dikirim.</p>
                            </div>
                            <button type="button" class="modal-close" @click="repushOpen = false">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                            </button>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div>
                                <label class="label">Periode</label>
                                <div class="flex gap-4 text-sm">
                                    <label class="inline-flex items-center gap-2"><input type="radio" value="all" x-model="scope" @change="refresh()"> Semua tanggal</label>
                                    <label class="inline-flex items-center gap-2"><input type="radio" value="range" x-model="scope" @change="refresh()"> Pilih tanggal</label>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3" x-show="scope === 'range'" x-cloak>
                                <div>
                                    <label class="label">Dari tanggal</label>
                                    <input type="date" class="input" name="from" x-model="from" :disabled="scope !== 'range'" @change="refresh()">
                                </div>
                                <div>
                                    <label class="label">Sampai tanggal</label>
                                    <input type="date" class="input" name="to" x-model="to" :disabled="scope !== 'range'" @change="refresh()">
                                </div>
                            </div>
                            <div>
                                <label class="label">Status bayar</label>
                                <select class="input" name="payment_status" x-model="paymentStatus" @change="refresh()">
                                    <option value="paid">Paid (lunas)</option>
                                    <option value="partial">Partial</option>
                                    <option value="unpaid">Unpaid</option>
                                    <option value="all">Semua status bayar</option>
                                </select>
                                <p class="mt-1 text-xs text-[#b26a1f]" x-show="paymentStatus !== 'paid'" x-cloak>Order belum lunas dikirim sebagai Sales Order + Invoice tanpa Payment, jadi tercatat sebagai piutang di Zoho.</p>
                            </div>
                            <p class="rounded-lg bg-[#f6f6f6] px-4 py-3 text-sm text-heading">
                                <span x-show="error" x-text="error" class="text-brand"></span>
                                <span x-show="!error && count === null">Menghitung order…</span>
                                <span x-show="!error && count !== null"><strong x-text="count"></strong> order akan dikirim ulang.</span>
                            </p>
                        </div>
                    </div>
                    <div class="crud-modal-footer">
                        <button type="button" class="btn-ghost" @click="repushOpen = false">Batal</button>
                        <button class="btn-add" type="submit" :disabled="!count">Push ulang</button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Daftar order</h5>
                <p class="card-header-subtitle">Filter kolom memuat ulang otomatis saat nilai diubah.</p>
            </div>
            @if ($hasFilters)
                <div class="card-header-actions">
                    <a href="{{ route('orders.index') }}" class="btn-ghost">Reset filter</a>
                </div>
            @endif
        </div>

        <form id="order-filters" method="GET" action="{{ route('orders.index') }}"></form>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Order</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Pelanggan</th>
                        <th>Meja</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Bayar</th>
                        <th>Total</th>
                        <th>Kasir</th>
                        <th class="col-actions"></th>
                    </tr>
                    <tr class="filter-row">
                        <th></th>
                        <th>
                            <input form="order-filters" class="col-filter" type="search" name="order_number" value="{{ $filters['order_number'] ?? '' }}" placeholder="No. order..." onchange="this.form.submit()">
                        </th>
                        <th>
                            <input form="order-filters" class="col-filter" type="date" name="date" value="{{ $filters['date'] ?? '' }}" onchange="this.form.submit()">
                        </th>
                        <th>
                            <select form="order-filters" class="col-filter" name="outlet_id" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected((string) ($filters['outlet_id'] ?? '') === (string) $outlet->id)>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <input form="order-filters" class="col-filter" type="search" name="customer" value="{{ $filters['customer'] ?? '' }}" placeholder="Nama..." onchange="this.form.submit()">
                        </th>
                        <th>
                            <input form="order-filters" class="col-filter" type="search" name="table" value="{{ $filters['table'] ?? '' }}" placeholder="Kode..." onchange="this.form.submit()">
                        </th>
                        <th>
                            <select form="order-filters" class="col-filter" name="order_type" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (\App\Enums\OrderType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(($filters['order_type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <select form="order-filters" class="col-filter" name="status" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (['draft', 'held', 'new', 'processing', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Enums\OrderStatus::from($status)->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <select form="order-filters" class="col-filter" name="payment_status" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (\App\Enums\PaymentStatus::cases() as $pay)
                                    <option value="{{ $pay->value }}" @selected(($filters['payment_status'] ?? '') === $pay->value)>{{ $pay->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th></th>
                        <th>
                            <input form="order-filters" class="col-filter" type="search" name="cashier" value="{{ $filters['cashier'] ?? '' }}" placeholder="Nama..." onchange="this.form.submit()">
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="col-no">{{ $orders->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="font-semibold hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="text-muted">{{ wib($order->created_at) }}</td>
                            <td>{{ $order->outlet?->name ?? '—' }}</td>
                            <td>{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td>{{ $order->table?->code ?? '—' }}</td>
                            <td>
                                <span class="badge-soft">{{ $order->order_type?->label() ?? '—' }}</span>
                            </td>
                            <td><x-status :value="$order->status->color()">{{ $order->status->label() }}</x-status></td>
                            <td><x-status :value="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-status></td>
                            <td class="font-semibold">{{ money($order->grand_total) }}</td>
                            <td>{{ $order->user?->name ?? '—' }}</td>
                            <td class="col-actions">
                                <a href="{{ route('orders.show', $order) }}" class="table-action" title="Lihat">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-16 text-center text-sm text-slate-400">Tidak ada order yang cocok dengan filter kolom ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
