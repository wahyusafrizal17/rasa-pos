@extends('layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Tambah Product')
@section('breadcrumb', 'Catalog')
@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $product->exists ? 'Perbarui data produk dan varian' : 'Buat produk baru di katalog' }}</p>
        <a href="{{ route('products.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form
        method="POST"
        action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        enctype="multipart/form-data"
        class="grid gap-4 xl:grid-cols-[1.4fr_.8fr]"
        x-data="{
            variants: {{ Js::from($product->relationLoaded('variants') ? $product->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku, 'price_adjustment' => $v->price_adjustment]) : collect()) }}
        }"
    >
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-medium">Informasi produk</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">SKU</label>
                        <input class="input" name="sku" required maxlength="50" value="{{ old('sku', $product->sku) }}">
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="150" value="{{ old('name', $product->name) }}">
                    </div>
                    <div>
                        <label class="label">Kategori</label>
                        <select name="category_id" class="input">
                            <option value="">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Unit</label>
                        <select name="unit_id" class="input" required>
                            <option value="">Pilih unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipe</label>
                        <select name="type" class="input" required>
                            @foreach (\App\Enums\ProductType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('type', $product->type?->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">BOM level</label>
                        <input class="input" type="number" name="bom_level" min="0" max="4" value="{{ old('bom_level', $product->bom_level ?? 0) }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Deskripsi</label>
                        <textarea class="input min-h-24" name="description">{{ old('description', $product->description) }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Gambar menu</label>
                        @if ($product->image)
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="mb-3 h-24 w-32 rounded-lg object-cover">
                        @endif
                        <input class="input" type="file" name="image_file" accept="image/*">
                        <p class="mt-2 text-xs text-muted">Atau tempel URL gambar</p>
                        <input class="input mt-1.5" name="image" value="{{ old('image', $product->image) }}" placeholder="https://...">
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium">Variants</p>
                    <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="variants.push({ id: '', name: '', sku: '', price_adjustment: 0 })">Tambah varian</button>
                </div>
                <div class="mt-4 space-y-3">
                    <template x-for="(variant, index) in variants" :key="index">
                        <div class="grid gap-3 rounded-2xl border border-line p-4 sm:grid-cols-12">
                            <input type="hidden" :name="`variants[${index}][id]`" x-model="variant.id">
                            <div class="sm:col-span-4">
                                <label class="label">Nama</label>
                                <input class="input" :name="`variants[${index}][name]`" x-model="variant.name" placeholder="Regular">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="label">SKU</label>
                                <input class="input" :name="`variants[${index}][sku]`" x-model="variant.sku">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="label">Adj. harga</label>
                                <input class="input" type="number" step="0.01" :name="`variants[${index}][price_adjustment]`" x-model="variant.price_adjustment">
                            </div>
                            <div class="flex items-end sm:col-span-2">
                                <button type="button" class="btn-ghost w-full !py-2 text-xs text-red-600" @click="variants.splice(index, 1)">Hapus</button>
                            </div>
                        </div>
                    </template>
                    <p class="text-sm text-slate-400" x-show="!variants.length">Belum ada varian. Produk tanpa varian tetap bisa dijual.</p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-medium">Harga & stok</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="label">Harga jual</label>
                        <input class="input" type="number" step="0.01" min="0" name="price" required value="{{ old('price', $product->price ?? 0) }}">
                    </div>
                    <div>
                        <label class="label">HPP / Cost</label>
                        <input class="input" type="number" step="0.01" min="0" name="cost" value="{{ old('cost', $product->cost ?? 0) }}">
                    </div>
                    <div>
                        <label class="label">Minimum stock</label>
                        <input class="input" type="number" step="0.001" min="0" name="minimum_stock" value="{{ old('minimum_stock', $product->minimum_stock ?? 0) }}">
                    </div>
                    <div>
                        <label class="label">Reorder level</label>
                        <input class="input" type="number" step="0.001" min="0" name="reorder_level" value="{{ old('reorder_level', $product->reorder_level ?? 0) }}">
                    </div>
                    <div>
                        <label class="label">Maximum stock</label>
                        <input class="input" type="number" step="0.001" min="0" name="maximum_stock" value="{{ old('maximum_stock', $product->maximum_stock ?? 0) }}">
                    </div>
                    <div>
                        <label class="label">Station</label>
                        <select name="station" class="input">
                            <option value="">—</option>
                            @foreach (['kitchen', 'bar', 'cashier'] as $station)
                                <option value="{{ $station }}" @selected(old('station', $product->station) === $station)>{{ ucfirst($station) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Prep minutes</label>
                        <input class="input" type="number" min="0" name="prep_minutes" value="{{ old('prep_minutes', $product->prep_minutes ?? 0) }}">
                    </div>
                </div>
            </div>

            <div class="card p-5 space-y-3">
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_sellable" value="1" class="h-4 w-4 rounded border-line" @checked(old('is_sellable', $product->is_sellable))>
                    Dapat dijual (is_sellable)
                </label>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_stockable" value="1" class="h-4 w-4 rounded border-line" @checked(old('is_stockable', $product->is_stockable))>
                    Track stok (is_stockable)
                </label>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line" @checked(old('is_active', $product->is_active))>
                    Aktif
                </label>
            </div>

            <button class="btn-primary w-full" type="submit">{{ $product->exists ? 'Simpan perubahan' : 'Buat produk' }}</button>
        </div>
    </form>
@endsection
