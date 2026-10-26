@extends('layouts.app')
@section('title', 'Products')
@section('breadcrumb', 'Catalog')
@section('content')
    <div x-data="productPage()" @keydown.escape.window="closeTop()">
        <div class="mb-5 grid gap-4 md:grid-cols-3">
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Total produk</p>
                    <p class="stat-value">{{ number_format($stats['total']) }}</p>
                    <p class="stat-hint">Semua item katalog</p>
                </div>
                <span class="stat-icon bg-[#e8f1ff] text-[#3b82f6]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Dapat dijual</p>
                    <p class="stat-value">{{ number_format($stats['sellable']) }}</p>
                    <p class="stat-hint">Aktif di kasir</p>
                </div>
                <span class="stat-icon bg-[#e8f8ee] text-[#1f9d57]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Nonaktif</p>
                    <p class="stat-value">{{ number_format($stats['inactive']) }}</p>
                    <p class="stat-hint">Tidak tampil di POS</p>
                </div>
                <span class="stat-icon bg-brand-soft text-brand">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </span>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">Daftar produk</h5>
                    <p class="card-header-subtitle">Centang Optional atau Expresso untuk item yang memakainya di kasir.</p>
                </div>
                <div class="card-header-actions">
                    @can('products.manage')
                        <form method="POST" action="{{ route('products.sync') }}">
                            @csrf
                            <button type="submit" class="btn-add">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12a8 8 0 01-13.7 5.7M4 12a8 8 0 0113.7-5.7M4 4v5h5M20 20v-5h-5"/></svg>
                                Sinkronisasi
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <form id="product-filters" method="GET" action="{{ route('products.index') }}"></form>
            <div class="table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="col-no">No.</th>
                            <th>SKU</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Tipe</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Pilihan</th>
                            <th class="col-actions"></th>
                        </tr>
                        <tr class="filter-row">
                            <th></th>
                            <th>
                                <input form="product-filters" class="col-filter" type="search" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="SKU..." onchange="this.form.submit()">
                            </th>
                            <th>
                                <input form="product-filters" class="col-filter" type="search" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Nama..." onchange="this.form.submit()">
                            </th>
                            <th>
                                <select form="product-filters" class="col-filter" name="category_id" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th>
                                <select form="product-filters" class="col-filter" name="type" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach (\App\Enums\ProductType::cases() as $type)
                                        <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th></th>
                            <th>
                                <select form="product-filters" class="col-filter" name="status" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                                </select>
                            </th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php $row = $product->toModalArray(); @endphp
                            <tr>
                                <td class="col-no">{{ $products->firstItem() + $loop->index }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $product->imageUrl() }}" alt="" width="36" height="36" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                                        <button type="button" class="text-left font-semibold hover:underline" @click="openView({{ Js::from($row) }})">{{ $product->name }}</button>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td><span class="badge-soft">{{ $product->type?->label() }}</span></td>
                                <td class="font-semibold">{{ money($product->price) }}</td>
                                <td>
                                    <x-status :value="$product->is_active ? 'green' : 'gray'">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</x-status>
                                </td>
                                <td>
                                    @can('products.manage')
                                        <form method="POST" action="{{ route('products.choices', $product) }}" class="flex flex-col gap-1 text-[12px] text-heading">
                                            @csrf
                                            @method('PATCH')
                                            <label class="flex items-center gap-1.5 whitespace-nowrap">
                                                <input type="hidden" name="has_optional" value="0">
                                                <input type="checkbox" name="has_optional" value="1" class="rounded border-line text-brand" @checked($product->has_optional) onchange="this.form.submit()">
                                                Optional
                                            </label>
                                            <label class="flex items-center gap-1.5 whitespace-nowrap">
                                                <input type="hidden" name="has_expresso" value="0">
                                                <input type="checkbox" name="has_expresso" value="1" class="rounded border-line text-brand" @checked($product->has_expresso) onchange="this.form.submit()">
                                                Expresso
                                            </label>
                                        </form>
                                    @else
                                        <span class="text-[12px] text-muted">{{ collect([$product->has_optional ? 'Optional' : null, $product->has_expresso ? 'Expresso' : null])->filter()->join(', ') ?: '—' }}</span>
                                    @endcan
                                </td>
                                <td class="col-actions">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" class="table-action" title="Lihat" @click="openView({{ Js::from($row) }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-16 text-center text-sm text-slate-400">Tidak ada produk yang cocok dengan filter kolom ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="border-t border-line px-5 py-4">{{ $products->links() }}</div>
            @endif
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="viewOpen" x-cloak @click.self="viewOpen = false">
            <div class="crud-modal">
                <div class="crud-modal-body">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <img :src="viewing?.image_url" alt="" class="h-14 w-14 shrink-0 rounded-xl object-cover">
                            <div>
                                <h3 class="text-lg font-semibold text-heading" x-text="viewing?.name || 'Detail Produk'"></h3>
                                <p class="mt-1 text-[13px] text-muted">
                                    <span x-text="viewing?.sku || '—'"></span>
                                    ·
                                    <span x-text="viewing?.status_label || '—'"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button" class="modal-close" @click="viewOpen = false">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Harga</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.price_label || '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Tipe</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.type_label || '—'"></p>
                        </div>
                        <div class="rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Kategori</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.category_label || '—'"></p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-[12px] text-muted">Unit</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.unit_label || '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[12px] text-muted">HPP</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.cost_label || '—'"></dd>
                        </div>
                    </dl>

                    <div class="mt-5" x-show="viewing?.variants?.length">
                        <p class="stat-kicker">Varian</p>
                        <div class="mt-2 divide-y divide-[#f0f0f0] overflow-hidden rounded-xl border border-line">
                            <template x-for="(variant, index) in (viewing?.variants || [])" :key="index">
                                <div class="flex items-center justify-between bg-white px-4 py-2.5 text-sm">
                                    <span class="font-medium" x-text="variant.name"></span>
                                    <span class="text-muted" x-text="variant.price_adjustment_label"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function productPage() {
            const focus = @json($focusPayload);
            const requestedModal = @json(request('modal'));

            return {
                viewOpen: requestedModal === 'view' && !!focus,
                viewing: focus,
                openView(row) {
                    if (! row) return;
                    this.viewing = row;
                    this.viewOpen = true;
                },
                closeTop() {
                    this.viewOpen = false;
                },
            };
        }
    </script>
@endpush
