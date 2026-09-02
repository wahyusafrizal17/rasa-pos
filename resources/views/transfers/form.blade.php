@extends('layouts.app')
@section('title', 'Buat Transfer')
@section('breadcrumb', 'Inventory')
@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">Pindahkan stok dari outlet sumber ke tujuan</p>
        <a href="{{ route('transfers.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form
        method="POST"
        action="{{ route('transfers.store') }}"
        class="card mx-auto max-w-4xl p-6"
        x-data="{ items: [{ product_id: '', quantity: 1 }] }"
    >
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Outlet sumber</label>
                <select name="source_outlet_id" class="input" required>
                    <option value="">Pilih outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected($outlet->id === current_outlet_id())>{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Outlet tujuan</label>
                <select name="destination_outlet_id" class="input" required>
                    <option value="">Pilih outlet</option>
                    @foreach ($outlets as $outlet)
                        <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Tanggal transfer</label>
                <input class="input" type="date" name="transfer_date" value="{{ now()->toDateString() }}">
            </div>
            <div>
                <label class="label">Catatan</label>
                <input class="input" name="notes">
            </div>
        </div>

        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-medium">Items</p>
                <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="items.push({ product_id: '', quantity: 1 })">Tambah item</button>
            </div>
            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-3 rounded-2xl border border-line p-4 sm:grid-cols-12">
                        <div class="sm:col-span-8">
                            <label class="label">Produk</label>
                            <select class="input" :name="`items[${index}][product_id]`" x-model="item.product_id" required>
                                <option value="">Pilih produk</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="label">Qty</label>
                            <input class="input" type="number" step="0.001" min="0.001" :name="`items[${index}][quantity]`" x-model="item.quantity" required>
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <button type="button" class="btn-ghost w-full !px-3" @click="items.length > 1 && items.splice(index, 1)">×</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <button class="btn-primary mt-6" type="submit">Buat transfer</button>
    </form>
@endsection
