@extends('layouts.app')
@section('title', 'Discounts')
@section('breadcrumb', 'Marketing')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Promo persentase dan nominal untuk order atau item</p>
        <a href="{{ route('marketing.bundles') }}" class="btn-ghost">Bundles</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.9fr_1.1fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Buat diskon</p>
            <form method="POST" action="{{ route('marketing.discounts.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="120">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kode</label>
                        <input class="input" name="code" maxlength="40">
                    </div>
                    <div>
                        <label class="label">Tipe</label>
                        <select name="type" class="input" required>
                            <option value="percentage">Percentage</option>
                            <option value="nominal">Nominal</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Scope</label>
                        <select name="scope" class="input" required>
                            <option value="order">Order</option>
                            <option value="item">Item</option>
                            <option value="category">Category</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Nilai</label>
                        <input class="input" type="number" step="0.01" min="0" name="value" required>
                    </div>
                    <div>
                        <label class="label">Min. transaksi</label>
                        <input class="input" type="number" step="0.01" min="0" name="minimum_transaction">
                    </div>
                    <div>
                        <label class="label">Max. diskon</label>
                        <input class="input" type="number" step="0.01" min="0" name="maximum_discount">
                    </div>
                    <div>
                        <label class="label">Mulai</label>
                        <input class="input" type="date" name="start_date">
                    </div>
                    <div>
                        <label class="label">Selesai</label>
                        <input class="input" type="date" name="end_date">
                    </div>
                    <div>
                        <label class="label">Jam mulai</label>
                        <input class="input" type="time" name="start_time">
                    </div>
                    <div>
                        <label class="label">Jam selesai</label>
                        <input class="input" type="time" name="end_time">
                    </div>
                </div>
                <div>
                    <label class="label">Outlet</label>
                    <select name="outlet_ids[]" class="input min-h-28" multiple>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Produk</label>
                    <select name="product_ids[]" class="input min-h-28" multiple>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Kategori</label>
                    <select name="category_ids[]" class="input min-h-28" multiple>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan diskon</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Nilai</th>
                            <th>Periode</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discounts as $discount)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $discount->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $discount->code }} · {{ $discount->scope }}</p>
                                </td>
                                <td>{{ $discount->type?->label() }}</td>
                                <td>{{ $discount->type === \App\Enums\DiscountType::Percentage ? number_format($discount->value).'%' : money($discount->value) }}</td>
                                <td class="text-xs text-slate-500">
                                    {{ $discount->start_date?->format('d/m/Y') ?? '—' }} – {{ $discount->end_date?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('marketing.discounts.toggle', $discount) }}">
                                        @csrf
                                        <button class="{{ $discount->is_active ? 'btn-ghost' : 'btn-primary' }} !px-3 !py-1.5 text-xs" type="submit">
                                            {{ $discount->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada diskon.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($discounts->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $discounts->links() }}</div>
            @endif
        </div>
    </div>
@endsection
