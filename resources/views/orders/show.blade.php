@extends('layouts.app')
@section('title', $order->order_number)
@section('breadcrumb', 'Orders')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <x-status :value="$order->status->color()">{{ $order->status->label() }}</x-status>
                <x-status :value="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-status>
                <span class="badge bg-slate-100 text-slate-600">{{ $order->order_type?->label() }}</span>
                <span class="badge bg-slate-100 text-slate-600">{{ $order->channel?->label() }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
                {{ $order->outlet?->name }} · {{ $order->created_at?->format('d/m/Y H:i') }}
                · Kasir {{ $order->user?->name ?? '—' }}
                @if ($order->estimated_ready_at)
                    · ETA {{ $order->estimated_ready_at->format('H:i') }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('orders.index') }}" class="btn-ghost">Kembali</a>
            <a href="{{ route('pos.ticket', [$order, 'kitchen']) }}?reprint=1" target="_blank" class="btn-ghost">Reprint kitchen</a>
            <a href="{{ route('pos.ticket', [$order, 'bar']) }}?reprint=1" target="_blank" class="btn-ghost">Reprint bar</a>
            <a href="{{ route('pos.ticket', [$order, 'cashier']) }}?reprint=1" target="_blank" class="btn-ghost">Reprint kasir</a>
            @if ($order->payment_status === \App\Enums\PaymentStatus::Paid)
                <a href="{{ route('pos.receipt', $order) }}" class="btn-ghost">Receipt</a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.4fr_.8fr]">
        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-medium">Items</p>
                <div class="table-wrap mt-4">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Diskon</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $item->name }}</p>
                                        @if ($item->notes)
                                            <p class="text-xs text-slate-400">{{ $item->notes }}</p>
                                        @endif
                                        <p class="text-xs text-slate-400">{{ $item->product?->sku }} · {{ $item->station }} · {{ $item->status }}</p>
                                        @if ($item->batch)
                                            <p class="text-xs text-slate-400">Batch {{ $item->batch->batch_number }}</p>
                                        @endif
                                    </td>
                                    <td>{{ number_format($item->quantity, 0) }}</td>
                                    <td>{{ money($item->unit_price) }}</td>
                                    <td>{{ money($item->discount_amount) }}</td>
                                    <td class="font-medium">{{ money($item->total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-sm text-slate-400">Tidak ada item pada order ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-5">
                <p class="text-sm font-medium">Pembayaran</p>
                <div class="mt-4 space-y-3">
                    @forelse ($order->payments as $payment)
                        <div class="flex items-center justify-between rounded-2xl border border-line px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ $payment->method?->label() ?? $payment->method }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $payment->created_at?->format('d/m/Y H:i') }}
                                    @if ($payment->reference) · Ref {{ $payment->reference }} @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">{{ money($payment->amount) }}</p>
                                <p class="text-xs text-slate-400">Tendered {{ money($payment->tendered) }} · Change {{ money($payment->change_amount) }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada pembayaran tercatat.</div>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <p class="text-sm font-medium">Timeline status</p>
                <div class="mt-5 space-y-4">
                    @forelse ($order->histories->sortBy('created_at') as $history)
                        <div class="flex gap-3">
                            <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-ink"></div>
                            <div>
                                <p class="text-sm font-medium">{{ $history->from_status }} → {{ $history->to_status }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $history->user?->name ?? 'Sistem' }} · {{ $history->created_at?->format('d/m/Y H:i') }}
                                </p>
                                @if ($history->notes)
                                    <p class="mt-1 text-sm text-slate-500">{{ $history->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada riwayat status.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-medium">Ringkasan</p>
                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-500"><span>Pelanggan</span><span class="text-ink">{{ $order->customer?->name ?? 'Walk-in' }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Meja</span><span class="text-ink">{{ $order->table?->code ?? '—' }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Tamu</span><span class="text-ink">{{ $order->guest_count ?: '—' }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Diskon {{ $order->discount?->name }}</span><span>{{ money($order->discount_amount) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Pajak</span><span>{{ money($order->tax_amount) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Service</span><span>{{ money($order->service_charge) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Poin</span><span>- {{ money($order->points_value) }}</span></div>
                    <div class="flex justify-between border-t border-line pt-3 text-base font-semibold"><span>Grand total</span><span>{{ money($order->grand_total) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Terbayar</span><span>{{ money($order->paidTotal()) }}</span></div>
                    <div class="flex justify-between text-slate-500"><span>Sisa</span><span>{{ money($order->balanceDue()) }}</span></div>
                </div>
                @if ($order->notes)
                    <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $order->notes }}</p>
                @endif
            </div>

            @if ($order->status->isOpen())
                <div class="card p-5">
                    <p class="text-sm font-medium">Ubah status</p>
                    <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="label">Status</label>
                            <select name="status" class="input" required>
                                @foreach (['new', 'processing', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected($order->status->value === $status)>{{ \App\Enums\OrderStatus::from($status)->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Catatan</label>
                            <input class="input" name="notes" maxlength="255" placeholder="Opsional">
                        </div>
                        <button class="btn-primary w-full" type="submit">Simpan status</button>
                    </form>
                </div>

                <div class="card p-5">
                    <p class="text-sm font-medium">Batalkan order</p>
                    <form method="POST" action="{{ route('pos.cancel', $order) }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="label">Alasan</label>
                            <input class="input" name="reason" required maxlength="255" placeholder="Alasan pembatalan">
                        </div>
                        <button class="btn-danger w-full" type="submit">Cancel order</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
