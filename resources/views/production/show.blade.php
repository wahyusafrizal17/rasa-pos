@extends('layouts.app')
@section('title', $order->number)
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <x-status :value="$order->status->color()">{{ $order->status->label() }}</x-status>
            <p class="mt-2 text-sm text-slate-500">{{ $order->product?->name }} · {{ $order->outlet?->name }} · {{ $order->production_date?->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('production.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @if (in_array($order->status, [\App\Enums\ProductionStatus::Draft, \App\Enums\ProductionStatus::Planned], true))
            <form method="POST" action="{{ route('production.start', $order) }}">
                @csrf
                <button class="btn-primary" type="submit">Start produksi</button>
            </form>
        @endif
        @if ($order->status === \App\Enums\ProductionStatus::InProduction)
            <form method="POST" action="{{ route('production.complete', $order) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label class="label">Qty produced</label>
                    <input class="input !w-36" type="number" step="0.001" min="0.001" name="quantity_produced" value="{{ $order->quantity_planned }}" required>
                </div>
                <button class="btn-primary" type="submit">Complete</button>
            </form>
        @endif
        @if ($order->status->value !== 'completed' && $order->status->value !== 'cancelled')
            <form method="POST" action="{{ route('production.cancel', $order) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <div>
                    <label class="label">Alasan batal</label>
                    <input class="input !w-56" name="reason" required maxlength="255">
                </div>
                <button class="btn-danger" type="submit">Cancel</button>
            </form>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card p-5">
            <p class="text-sm font-medium">Ringkasan</p>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between text-slate-500"><span>Planned</span><span class="text-ink">{{ number_format($order->quantity_planned, 2) }}</span></div>
                <div class="flex justify-between text-slate-500"><span>Produced</span><span class="text-ink">{{ number_format($order->quantity_produced ?? 0, 2) }}</span></div>
                <div class="flex justify-between text-slate-500"><span>Yield</span><span class="text-ink">{{ number_format($order->yield_percentage ?? 0, 1) }}%</span></div>
                <div class="flex justify-between text-slate-500"><span>Batch</span>
                    <span class="text-ink">
                        @if ($order->batch)
                            <a href="{{ route('batches.show', $order->batch) }}" class="hover:underline">{{ $order->batch->batch_number }}</a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="flex justify-between text-slate-500"><span>User</span><span class="text-ink">{{ $order->user?->name ?? '—' }}</span></div>
            </div>
            @if ($order->notes)
                <p class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm">{{ $order->notes }}</p>
            @endif
        </div>

        <div class="card p-5">
            <p class="text-sm font-medium">Bahan terpakai</p>
            <div class="mt-4 space-y-3">
                @forelse ($order->items as $item)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium">{{ $item->product?->name }}</p>
                            <p class="text-xs text-slate-400">Required {{ number_format($item->quantity_required, 3) }} {{ $item->product?->unit?->code }}</p>
                        </div>
                        <span>{{ number_format($item->quantity_used ?? 0, 3) }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada item bahan.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
