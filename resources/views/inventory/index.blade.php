@extends('layouts.app')
@section('title', 'Stock')
@section('breadcrumb', 'Inventory')
@section('content')
    @php
        $adjustProducts = \App\Models\Product::query()->where('is_stockable', true)->orderBy('name')->get();
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Posisi stok outlet saat ini</p>
        <a href="{{ route('inventory.movements') }}" class="btn-ghost">Lihat movements</a>
    </div>

    <div class="mb-4 grid gap-4 md:grid-cols-2">
        <div class="card p-5">
            <p class="text-sm font-medium">Low stock</p>
            <div class="mt-4 space-y-3">
                @forelse ($lowStock as $product)
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium">{{ $product->name }}</p>
                            <p class="text-xs text-slate-400">Reorder {{ $product->reorder_level }} {{ $product->unit?->code }}</p>
                        </div>
                        <span class="badge bg-orange-50 text-orange-700">{{ number_format($product->inventories->first()?->quantity ?? 0, 1) }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">Tidak ada item low stock.</p>
                @endforelse
            </div>
        </div>
        <div class="card p-5">
            <p class="text-sm font-medium">Out of stock</p>
            <div class="mt-4 space-y-3">
                @forelse ($outOfStock as $product)
                    <div class="flex items-center justify-between text-sm">
                        <p class="font-medium">{{ $product->name }}</p>
                        <span class="badge bg-red-50 text-red-700">0 {{ $product->unit?->code }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">Semua item tersedia.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.4fr_.8fr]">
        <div class="card overflow-hidden">
            <form method="GET" action="{{ route('inventory.index') }}" class="flex gap-3 border-b border-line p-4">
                <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Cari produk">
                <button class="btn-primary" type="submit">Cari</button>
            </form>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Qty</th>
                            <th>Reserved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stocks as $stock)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $stock->product?->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $stock->product?->sku }}</p>
                                </td>
                                <td>{{ $stock->product?->category?->name ?? '—' }}</td>
                                <td>{{ number_format($stock->quantity, 2) }} {{ $stock->product?->unit?->code }}</td>
                                <td>{{ number_format($stock->reserved_quantity, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-sm text-slate-400">Belum ada data stok untuk outlet ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($stocks->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $stocks->links() }}</div>
            @endif
        </div>

        <div class="card p-5 h-fit">
            <p class="text-sm font-medium">Adjustment stok</p>
            <form method="POST" action="{{ route('inventory.adjust') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Produk</label>
                    <select name="product_id" class="input" required>
                        <option value="">Pilih produk</option>
                        @foreach ($adjustProducts as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Quantity (+ / −)</label>
                    <input class="input" type="number" step="0.001" name="quantity" required placeholder="5 atau -2">
                </div>
                <div>
                    <label class="label">Alasan</label>
                    <input class="input" name="reason" required maxlength="255">
                </div>
                <button class="btn-primary w-full" type="submit">Sesuaikan stok</button>
            </form>
        </div>
    </div>
@endsection
