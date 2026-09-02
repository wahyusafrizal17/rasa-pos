@extends('layouts.app')
@section('title', 'Buat Production')
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">Rencanakan produksi berdasarkan BOM</p>
        <a href="{{ route('production.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form method="POST" action="{{ route('production.store') }}" class="card mx-auto max-w-2xl p-6 space-y-4">
        @csrf
        <div>
            <label class="label">Outlet</label>
            <select name="outlet_id" class="input" required>
                <option value="">Pilih outlet</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @selected($outlet->id === current_outlet_id())>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Produk</label>
            <select name="product_id" class="input" required>
                <option value="">Pilih produk</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">BOM</label>
            <select name="bom_id" class="input">
                <option value="">Otomatis / tanpa BOM</option>
                @foreach ($boms as $bom)
                    <option value="{{ $bom->id }}">{{ $bom->product?->name }} · v{{ $bom->version }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Qty planned</label>
            <input class="input" type="number" step="0.001" min="0.001" name="quantity_planned" required>
        </div>
        <div>
            <label class="label">Tanggal produksi</label>
            <input class="input" type="date" name="production_date" value="{{ now()->toDateString() }}">
        </div>
        <div>
            <label class="label">Catatan</label>
            <textarea class="input min-h-24" name="notes"></textarea>
        </div>
        <button class="btn-primary" type="submit">Buat production order</button>
    </form>
@endsection
