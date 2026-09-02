@extends('layouts.app')
@section('title', 'Movements')
@section('breadcrumb', 'Inventory')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Mutasi stok masuk, keluar, produksi, dan adjustment</p>
        <a href="{{ route('inventory.index') }}" class="btn-ghost">Kembali ke stock</a>
    </div>

    <form method="GET" action="{{ route('inventory.movements') }}" class="card mb-6 p-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label">Tipe</label>
                <select name="type" class="input !w-auto" onchange="this.form.submit()">
                    <option value="">Semua tipe</option>
                    @foreach (\App\Enums\StockMovementType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                        <th>Qty</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>User</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $movement->reference_number }}</p>
                                <p class="text-xs text-slate-400">{{ $movement->reason }}</p>
                            </td>
                            <td>{{ $movement->product?->name }}</td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $movement->type?->label() }}</span></td>
                            <td>{{ number_format($movement->quantity, 2) }}</td>
                            <td>{{ number_format($movement->before_stock, 2) }}</td>
                            <td>{{ number_format($movement->after_stock, 2) }}</td>
                            <td>{{ $movement->user?->name ?? '—' }}</td>
                            <td class="text-slate-500">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-sm text-slate-400">Belum ada mutasi stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
