@extends('layouts.app')
@section('title', 'Bundles')
@section('breadcrumb', 'Marketing')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Paket menu dengan harga khusus</p>
        <a href="{{ route('marketing.discounts') }}" class="btn-ghost">Discounts</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.9fr_1.1fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Buat bundle</p>
            <form method="POST" action="{{ route('marketing.bundles.store') }}" class="mt-4 space-y-4" x-data="{ items: [{ product_id: '', quantity: 1 }, { product_id: '', quantity: 1 }] }">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="120">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">SKU</label>
                        <input class="input" name="sku" maxlength="40">
                    </div>
                    <div>
                        <label class="label">Harga</label>
                        <input class="input" type="number" step="0.01" min="0" name="price" required>
                    </div>
                    <div>
                        <label class="label">Mulai</label>
                        <input class="input" type="date" name="start_date">
                    </div>
                    <div>
                        <label class="label">Selesai</label>
                        <input class="input" type="date" name="end_date">
                    </div>
                </div>
                <div>
                    <label class="label">Outlet</label>
                    <select name="outlet_ids[]" class="input min-h-24" multiple>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="label mb-0">Items (min. 2)</label>
                        <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="items.push({ product_id: '', quantity: 1 })">Tambah</button>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="grid grid-cols-12 gap-2">
                                <select class="input col-span-8" :name="`items[${index}][product_id]`" x-model="item.product_id" required>
                                    <option value="">Pilih produk</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <input class="input col-span-3" type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" required>
                                <button type="button" class="btn-ghost col-span-1 !px-2" @click="items.length > 2 && items.splice(index, 1)">×</button>
                            </div>
                        </template>
                    </div>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan bundle</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($bundles as $bundle)
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $bundle->name }}</p>
                            <p class="text-xs text-slate-400">{{ $bundle->sku }} · {{ $bundle->start_date?->format('d/m/Y') }} – {{ $bundle->end_date?->format('d/m/Y') }}</p>
                        </div>
                        <p class="font-semibold">{{ money($bundle->price) }}</p>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        @foreach ($bundle->items as $item)
                            <div class="flex justify-between text-slate-500">
                                <span>{{ $item->product?->name }}</span>
                                <span>× {{ number_format($item->quantity) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="card px-4 py-16 text-center text-sm text-slate-400">Belum ada bundle.</div>
            @endforelse
            @if ($bundles->hasPages())
                <div>{{ $bundles->links() }}</div>
            @endif
        </div>
    </div>
@endsection
