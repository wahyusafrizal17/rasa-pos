@extends('layouts.app')
@section('title', 'Products')
@section('breadcrumb', 'Catalog')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Katalog produk, bahan baku, dan package</p>
        <a href="{{ route('products.create') }}" class="btn-primary">Tambah produk</a>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="card mb-6 p-5">
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="label">Cari</label>
                <input class="input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau SKU">
            </div>
            <div>
                <label class="label">Tipe</label>
                <select name="type" class="input">
                    <option value="">Semua tipe</option>
                    @foreach (\App\Enums\ProductType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Kategori</label>
                <select name="category_id" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button class="btn-primary" type="submit">Filter</button>
                <a href="{{ route('products.index') }}" class="btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th>Harga</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400">{{ $product->sku }}</p>
                            </td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ $product->type?->label() }}</td>
                            <td>{{ money($product->price) }}</td>
                            <td>{{ $product->unit?->code }}</td>
                            <td>
                                @if ($product->is_active)
                                    <x-status value="green">Aktif</x-status>
                                @else
                                    <x-status value="gray">Nonaktif</x-status>
                                @endif
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('products.edit', $product) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Edit</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger !px-3 !py-1.5 text-xs" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-sm text-slate-400">Belum ada produk. Tambahkan item katalog untuk mulai menjual.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
