@extends('layouts.app')
@section('title', 'Discounts')
@section('breadcrumb', 'Marketing')
@section('content')
    @php
        $formError = $errors->any();
        $formScope = old('scope', 'order');
        $oldOutlets = array_map('strval', (array) old('outlet_ids', []));
        $oldProducts = array_map('strval', (array) old('product_ids', []));
        $oldCategories = array_map('strval', (array) old('category_ids', []));
    @endphp
    <div x-data="discountPage()" @keydown.escape.window="closeTop()">
        <div class="mb-5 grid gap-4 md:grid-cols-3">
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Total diskon</p>
                    <p class="stat-value">{{ number_format($stats['total']) }}</p>
                    <p class="stat-hint">Semua promo terdaftar</p>
                </div>
                <span class="stat-icon bg-[#e8f1ff] text-[#3b82f6]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 9V4a1 1 0 011-1h3z"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Sedang berjalan</p>
                    <p class="stat-value">{{ number_format($stats['active']) }}</p>
                    <p class="stat-hint">Aktif dalam periode hari ini</p>
                </div>
                <span class="stat-icon bg-[#e8f8ee] text-[#1f9d57]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Nonaktif</p>
                    <p class="stat-value">{{ number_format($stats['inactive']) }}</p>
                    <p class="stat-hint">Tidak dipakai di kasir</p>
                </div>
                <span class="stat-icon bg-[#fde8e8] text-brand">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </span>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">Daftar diskon</h5>
                    <p class="card-header-subtitle">Filter kolom memuat ulang otomatis saat nilai diubah.</p>
                </div>
                <div class="card-header-actions">
                    @can('marketing.manage')
                        <button type="button" class="btn-add" @click="openCreate()">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                            Tambah diskon
                        </button>
                    @endcan
                </div>
            </div>

            <form id="discount-filters" method="GET" action="{{ route('marketing.discounts') }}"></form>
            <div class="table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="col-no">No.</th>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Tipe</th>
                            <th>Cakupan</th>
                            <th>Nilai</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="col-actions"></th>
                        </tr>
                        <tr class="filter-row">
                            <th></th>
                            <th>
                                <input form="discount-filters" class="col-filter" type="search" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Nama..." onchange="this.form.submit()">
                            </th>
                            <th>
                                <input form="discount-filters" class="col-filter" type="search" name="code" value="{{ $filters['code'] ?? '' }}" placeholder="Kode..." onchange="this.form.submit()">
                            </th>
                            <th>
                                <select form="discount-filters" class="col-filter" name="type" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="percentage" @selected(($filters['type'] ?? '') === 'percentage')>Persentase</option>
                                    <option value="nominal" @selected(($filters['type'] ?? '') === 'nominal')>Nominal</option>
                                </select>
                            </th>
                            <th>
                                <select form="discount-filters" class="col-filter" name="scope" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="order" @selected(($filters['scope'] ?? '') === 'order')>Pesanan</option>
                                    <option value="item" @selected(($filters['scope'] ?? '') === 'item')>Produk</option>
                                    <option value="category" @selected(($filters['scope'] ?? '') === 'category')>Kategori</option>
                                </select>
                            </th>
                            <th></th>
                            <th></th>
                            <th>
                                <select form="discount-filters" class="col-filter" name="status" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discounts as $discount)
                            @php $row = $discount->toModalArray(); @endphp
                            <tr>
                                <td class="col-no">{{ $discounts->firstItem() + $loop->index }}</td>
                                <td>
                                    <button type="button" class="font-semibold hover:underline" @click="openView({{ Js::from($row) }})">{{ $discount->name }}</button>
                                </td>
                                <td>{{ $discount->code ?: '—' }}</td>
                                <td><span class="badge-soft">{{ $discount->typeLabel() }}</span></td>
                                <td>{{ $discount->scopeLabel() }}</td>
                                <td class="font-semibold">{{ $discount->valueLabel() }}</td>
                                <td class="text-[13px] text-muted">{{ $discount->periodLabel() }}</td>
                                <td>
                                    <x-status :value="$discount->statusColor()">{{ $discount->statusLabel() }}</x-status>
                                </td>
                                <td class="col-actions">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" class="table-action" title="Lihat" @click="openView({{ Js::from($row) }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                        </button>
                                        @can('marketing.manage')
                                            <form method="POST" action="{{ route('marketing.discounts.toggle', $discount) }}">
                                                @csrf
                                                <button type="submit" class="table-action {{ $discount->is_active ? '' : 'table-action-danger' }}" title="{{ $discount->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @if ($discount->is_active)
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    @else
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    @endif
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-16 text-center text-sm text-slate-400">Tidak ada diskon yang cocok dengan filter kolom ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($discounts->hasPages())
                <div class="border-t border-line px-5 py-4">{{ $discounts->links() }}</div>
            @endif
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="viewOpen" x-cloak @click.self="viewOpen = false">
            <div class="crud-modal">
                <div class="crud-modal-body">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-heading" x-text="viewing?.name || 'Detail Diskon'"></h3>
                            <p class="mt-1 text-[13px] text-muted">
                                <span x-text="viewing?.code || '—'"></span>
                                ·
                                <span x-text="viewing?.status_label || '—'"></span>
                            </p>
                        </div>
                        <button type="button" class="modal-close" @click="viewOpen = false">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Nilai</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.value_label || '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Tipe</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.type_label || '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Cakupan</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.scope_label || '—'"></p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-[12px] text-muted">Periode</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.period_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">Jam berlaku</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.time_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">Min. transaksi</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.minimum_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">Maks. diskon</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.maximum_label || '—'"></dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-[12px] text-muted">Outlet</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.outlets_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">Produk</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.products_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">Kategori</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.categories_label || '—'"></dd>
                        </div>
                    </dl>
                </div>
                @can('marketing.manage')
                    <div class="crud-modal-footer">
                        <button type="button" class="btn-ghost" @click="viewOpen = false">Tutup</button>
                        <form method="POST" :action="viewing?.toggle_url">
                            @csrf
                            <button class="btn-add" type="submit" x-text="viewing?.is_active ? 'Nonaktifkan' : 'Aktifkan'"></button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>

        @can('marketing.manage')
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="formOpen" x-cloak @click.self="formOpen = false">
                <div class="crud-modal">
                    <form class="flex min-h-0 flex-1 flex-col" method="POST" action="{{ route('marketing.discounts.store') }}">
                        @csrf
                        <div class="crud-modal-body">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-heading">Tambah Diskon</h3>
                                    <p class="mt-1 text-[13px] text-muted">Promo yang aktif akan muncul di kasir sesuai cakupan dan periode.</p>
                                </div>
                                <button type="button" class="modal-close" @click="formOpen = false">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                                </button>
                            </div>

                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="label">Nama</label>
                                    <input class="input" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Contoh: Promo weekday 10%">
                                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label">Kode</label>
                                    <input class="input" name="code" maxlength="40" value="{{ old('code') }}" placeholder="Opsional">
                                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label">Tipe</label>
                                    <select name="type" class="input" required>
                                        <option value="percentage" @selected(old('type', 'percentage') === 'percentage')>Persentase</option>
                                        <option value="nominal" @selected(old('type') === 'nominal')>Nominal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Cakupan</label>
                                    <select name="scope" class="input" required x-model="scope">
                                        <option value="order">Pesanan</option>
                                        <option value="item">Produk</option>
                                        <option value="category">Kategori</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label">Nilai</label>
                                    <input class="input" type="number" step="0.01" min="0" name="value" required value="{{ old('value') }}" placeholder="10 atau 5000">
                                    <p class="mt-1 text-[12px] text-muted">Persentase: 10 untuk 10%. Nominal: jumlah potongan.</p>
                                    @error('value')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label">Min. transaksi</label>
                                    <input class="input" type="number" step="0.01" min="0" name="minimum_transaction" value="{{ old('minimum_transaction') }}" placeholder="0">
                                </div>
                                <div>
                                    <label class="label">Maks. diskon</label>
                                    <input class="input" type="number" step="0.01" min="0" name="maximum_discount" value="{{ old('maximum_discount') }}" placeholder="Opsional">
                                </div>
                                <div>
                                    <label class="label">Mulai</label>
                                    <input class="input" type="date" name="start_date" value="{{ old('start_date') }}">
                                </div>
                                <div>
                                    <label class="label">Selesai</label>
                                    <input class="input" type="date" name="end_date" value="{{ old('end_date') }}">
                                    @error('end_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="label">Jam mulai</label>
                                    <input class="input" type="time" name="start_time" value="{{ old('start_time') }}">
                                </div>
                                <div>
                                    <label class="label">Jam selesai</label>
                                    <input class="input" type="time" name="end_time" value="{{ old('end_time') }}">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label">Outlet</label>
                                    <div class="max-h-36 space-y-0.5 overflow-y-auto rounded-lg border border-line bg-[#fafafa] p-2">
                                        @forelse ($outlets as $outlet)
                                            <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-white">
                                                <input type="checkbox" name="outlet_ids[]" value="{{ $outlet->id }}" class="rounded border-line text-brand focus:ring-brand/20" @checked(in_array((string) $outlet->id, $oldOutlets, true))>
                                                {{ $outlet->name }}
                                            </label>
                                        @empty
                                            <p class="px-2 py-3 text-[13px] text-muted">Belum ada outlet aktif.</p>
                                        @endforelse
                                    </div>
                                    <p class="mt-1.5 text-[12px] text-muted">Kosongkan untuk berlaku di semua outlet.</p>
                                </div>
                                <div class="sm:col-span-2" x-show="scope === 'item'" x-cloak>
                                    <label class="label">Produk</label>
                                    <input class="input mb-2" type="search" x-model="productQuery" placeholder="Cari produk...">
                                    <div class="max-h-40 space-y-0.5 overflow-y-auto rounded-lg border border-line bg-[#fafafa] p-2">
                                        @forelse ($products as $product)
                                            <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-white" x-show="matchName({{ Js::from($product->name) }}, productQuery)">
                                                <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="rounded border-line text-brand focus:ring-brand/20" @checked(in_array((string) $product->id, $oldProducts, true))>
                                                {{ $product->name }}
                                            </label>
                                        @empty
                                            <p class="px-2 py-3 text-[13px] text-muted">Belum ada produk.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="sm:col-span-2" x-show="scope === 'category'" x-cloak>
                                    <label class="label">Kategori</label>
                                    <input class="input mb-2" type="search" x-model="categoryQuery" placeholder="Cari kategori...">
                                    <div class="max-h-40 space-y-0.5 overflow-y-auto rounded-lg border border-line bg-[#fafafa] p-2">
                                        @forelse ($categories as $category)
                                            <label class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-sm hover:bg-white" x-show="matchName({{ Js::from($category->name) }}, categoryQuery)">
                                                <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="rounded border-line text-brand focus:ring-brand/20" @checked(in_array((string) $category->id, $oldCategories, true))>
                                                {{ $category->name }}
                                            </label>
                                        @empty
                                            <p class="px-2 py-3 text-[13px] text-muted">Belum ada kategori.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="crud-modal-footer">
                            <button type="button" class="btn-ghost" @click="formOpen = false">Batal</button>
                            <button class="btn-add" type="submit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        function discountPage() {
            const formError = @json($formError);
            const formScope = @json($formScope);

            return {
                formOpen: formError,
                viewOpen: false,
                viewing: null,
                scope: formScope,
                productQuery: '',
                categoryQuery: '',
                openCreate() {
                    this.viewOpen = false;
                    this.formOpen = true;
                },
                openView(row) {
                    if (! row) return;
                    this.viewing = row;
                    this.formOpen = false;
                    this.viewOpen = true;
                },
                closeTop() {
                    if (this.formOpen) this.formOpen = false;
                    else if (this.viewOpen) this.viewOpen = false;
                },
                matchName(name, query) {
                    if (! query) return true;
                    return String(name).toLowerCase().includes(String(query).toLowerCase());
                },
            };
        }
    </script>
@endpush
