@extends('layouts.app')
@section('title', 'Pickup / Online')
@section('breadcrumb', 'Orders')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4" x-data x-init="setTimeout(() => location.reload(), 15000)">
        <p class="text-sm text-slate-500">Papan status order pickup dan online. Diperbarui otomatis setiap 15 detik.</p>
        <a href="{{ route('orders.index') }}" class="btn-ghost">Semua Orders</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-4">
        @foreach ($columns as $column)
            @php $orders = $grouped[$column->value] ?? collect(); @endphp
            <section class="card flex min-h-[28rem] flex-col p-4">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-status :value="$column->color()">{{ $column->label() }}</x-status>
                    </div>
                    <span class="text-xs text-slate-400">{{ $orders->count() }}</span>
                </div>
                <div class="flex-1 space-y-3">
                    @forelse ($orders as $order)
                        <article class="rounded-2xl border border-line bg-white p-4">
                            <a href="{{ route('orders.show', $order) }}" class="block">
                                <p class="font-medium">{{ $order->order_number }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $order->customer?->name ?? 'Walk-in' }} · {{ $order->order_type?->label() }}</p>
                                <p class="mt-2 text-sm font-medium">{{ money($order->grand_total) }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $order->items->count() }} item · {{ $order->created_at?->diffForHumans() }}</p>
                                @if ($order->estimated_ready_at)
                                    <p class="mt-1 text-xs font-medium text-heading">ETA {{ $order->estimated_ready_at->format('H:i') }}</p>
                                @endif
                            </a>
                            <form method="POST" action="{{ route('orders.status', $order) }}" class="mt-3 space-y-2">
                                @csrf
                                <select name="status" class="input !py-2 text-xs">
                                    @foreach ($columns as $next)
                                        <option value="{{ $next->value }}" @selected($order->status->value === $next->value)>{{ $next->label() }}</option>
                                    @endforeach
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <input class="input !py-2 text-xs" name="notes" placeholder="Catatan">
                                <button class="btn-primary w-full !py-2 text-xs" type="submit">Update status</button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Tidak ada order di kolom ini.</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection
