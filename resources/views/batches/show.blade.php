@extends('layouts.app')
@section('title', $batch->batch_number)
@section('breadcrumb', 'Batches')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">{{ $batch->product?->name }} · {{ $batch->outlet?->name }}</p>
        </div>
        <a href="{{ route('batches.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card p-5 space-y-3 text-sm">
            <p class="font-medium">Detail batch</p>
            <div class="flex justify-between text-slate-500"><span>Quantity</span><span class="text-ink">{{ number_format($batch->quantity, 2) }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Yield qty</span><span class="text-ink">{{ number_format($batch->yield_quantity ?? 0, 2) }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Sisa</span><span class="text-ink">{{ number_format($batch->remaining_quantity ?? 0, 2) }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Diproduksi</span><span class="text-ink">{{ $batch->produced_at?->format('d/m/Y') }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Expired</span><span class="text-ink">{{ $batch->expires_at?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Tujuan</span><span class="text-ink">{{ $batch->destinationOutlet?->name ?? $batch->outlet?->name }}</span></div>
            <div class="flex justify-between text-slate-500">
                <span>Production order</span>
                <span class="text-ink">
                    @if ($batch->productionOrder)
                        <a href="{{ route('production.show', $batch->productionOrder) }}" class="hover:underline">{{ $batch->productionOrder->number }}</a>
                    @else
                        —
                    @endif
                </span>
            </div>
        </div>

        <div class="card p-5">
            <p class="text-sm font-medium">Bahan pada production</p>
            <div class="mt-4 space-y-3">
                @forelse ($batch->productionOrder?->items ?? [] as $item)
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ $item->product?->name }}</span>
                        <span>{{ number_format($item->quantity_used ?? $item->quantity_required, 3) }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Tidak ada rincian bahan.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-4 overflow-hidden">
        <p class="px-5 pt-5 text-sm font-medium">Terpakai di penjualan</p>
        <div class="table-wrap mt-3">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Item</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batch->salesItems as $item)
                        <tr>
                            <td><a href="{{ route('orders.show', $item->order) }}" class="hover:underline">{{ $item->order?->order_number }}</a></td>
                            <td>{{ $item->name }}</td>
                            <td>{{ number_format($item->quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-12 text-center text-sm text-slate-400">Batch ini belum terpakai di penjualan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
