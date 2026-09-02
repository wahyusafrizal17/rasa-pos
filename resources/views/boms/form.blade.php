@extends('layouts.app')
@section('title', $bom->exists ? 'Edit BOM' : 'Buat BOM')
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">Susun resep bahan dan yield produksi</p>
        <a href="{{ route('boms.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form
        method="POST"
        action="{{ $bom->exists ? route('boms.update', $bom) : route('boms.store') }}"
        class="card mx-auto max-w-4xl p-6"
        x-data="{
            items: {{ Js::from($bom->relationLoaded('items') && $bom->items->isNotEmpty()
                ? $bom->items->map(fn ($i) => ['component_id' => $i->component_id, 'unit_id' => $i->unit_id, 'quantity' => $i->quantity, 'waste_percentage' => $i->waste_percentage, 'yield_percentage' => $i->yield_percentage])
                : collect([['component_id' => '', 'unit_id' => '', 'quantity' => 1, 'waste_percentage' => 0, 'yield_percentage' => 100]])) }}
        }"
    >
        @csrf
        @if ($bom->exists)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Produk jadi</label>
                <select name="product_id" class="input" required>
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id', $bom->product_id) === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Versi</label>
                <input class="input" name="version" required maxlength="20" value="{{ old('version', $bom->version) }}">
            </div>
            <div>
                <label class="label">Yield %</label>
                <input class="input" type="number" step="0.01" min="1" max="200" name="yield_percentage" required value="{{ old('yield_percentage', $bom->yield_percentage) }}">
            </div>
            <div>
                <label class="label">Waste %</label>
                <input class="input" type="number" step="0.01" min="0" name="waste_percentage" value="{{ old('waste_percentage', $bom->waste_percentage ?? 0) }}">
            </div>
            <div>
                <label class="label">Aktif dari</label>
                <input class="input" type="date" name="active_from" value="{{ old('active_from', $bom->active_from?->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="label">Aktif sampai</label>
                <input class="input" type="date" name="active_until" value="{{ old('active_until', $bom->active_until?->format('Y-m-d')) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Catatan</label>
                <textarea class="input min-h-24" name="notes">{{ old('notes', $bom->notes) }}</textarea>
            </div>
            <div>
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bom->is_active))>
                    Aktif
                </label>
            </div>
        </div>

        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-sm font-medium">Komponen</p>
                <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="items.push({ component_id: '', unit_id: '', quantity: 1, waste_percentage: 0, yield_percentage: 100 })">Tambah item</button>
            </div>
            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid gap-3 rounded-2xl border border-line p-4 sm:grid-cols-12">
                        <div class="sm:col-span-4">
                            <label class="label">Komponen</label>
                            <select class="input" :name="`items[${index}][component_id]`" x-model="item.component_id" required>
                                <option value="">Pilih bahan</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Unit</label>
                            <select class="input" :name="`items[${index}][unit_id]`" x-model="item.unit_id">
                                <option value="">—</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Qty</label>
                            <input class="input" type="number" step="0.0001" min="0.0001" :name="`items[${index}][quantity]`" x-model="item.quantity" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Waste %</label>
                            <input class="input" type="number" step="0.01" min="0" :name="`items[${index}][waste_percentage]`" x-model="item.waste_percentage">
                        </div>
                        <div class="sm:col-span-1">
                            <label class="label">Yield %</label>
                            <input class="input" type="number" step="0.01" min="1" :name="`items[${index}][yield_percentage]`" x-model="item.yield_percentage">
                        </div>
                        <div class="flex items-end sm:col-span-1">
                            <button type="button" class="btn-ghost w-full" @click="items.length > 1 && items.splice(index, 1)">×</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <button class="btn-primary mt-6" type="submit">{{ $bom->exists ? 'Simpan BOM' : 'Buat BOM' }}</button>
    </form>
@endsection
