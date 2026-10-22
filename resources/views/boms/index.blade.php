@extends('layouts.app')
@section('title', 'BOM')
@section('breadcrumb', 'Production')
@section('content')
    @php
        $formError = $errors->any();
        $requestedModal = request('modal');
        $storeUrl = route('boms.store');
        $oldItems = old('items');
        if (! is_array($oldItems) || $oldItems === []) {
            $oldItems = data_get($focusPayload, 'items', []);
            if (! is_array($oldItems) || $oldItems === []) {
                $oldItems = [['component_id' => '', 'unit_id' => '', 'quantity' => 1]];
            }
        } else {
            $oldItems = array_values(array_map(fn ($item) => [
                'component_id' => (string) ($item['component_id'] ?? ''),
                'unit_id' => (string) ($item['unit_id'] ?? ''),
                'quantity' => $item['quantity'] ?? 1,
            ], $oldItems));
        }
        $formOld = [
            'id' => old('_bom_id', data_get($focusPayload, 'id')),
            'mode' => old('_form_mode', 'create'),
            'product_id' => (string) old('product_id', data_get($focusPayload, 'product_id', '')),
            'notes' => old('notes', data_get($focusPayload, 'notes', '')),
            'is_active' => old('is_active', data_get($focusPayload, 'is_active', true) ? '1' : '0') !== '0',
            'items' => $oldItems,
        ];
    @endphp
    <div x-data="bomPage()" @keydown.escape.window="closeTop()">
        <div class="mb-5 grid gap-4 md:grid-cols-3">
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Total BOM</p>
                    <p class="stat-value">{{ number_format($stats['total']) }}</p>
                    <p class="stat-hint">Semua resep menu</p>
                </div>
                <span class="stat-icon bg-[#e8f1ff] text-[#3b82f6]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5h6M9 9h6M5 5h.01M5 9h.01M5 13h14M5 17h14M5 21h14"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Aktif</p>
                    <p class="stat-value">{{ number_format($stats['active']) }}</p>
                    <p class="stat-hint">Dipakai saat menu terjual</p>
                </div>
                <span class="stat-icon bg-[#e8f8ee] text-[#1f9d57]">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="stat-card">
                <div>
                    <p class="stat-kicker">Nonaktif</p>
                    <p class="stat-value">{{ number_format($stats['inactive']) }}</p>
                    <p class="stat-hint">Tidak dipakai otomatis</p>
                </div>
                <span class="stat-icon bg-brand-soft text-brand">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </span>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">Daftar BOM</h5>
                    <p class="card-header-subtitle">Filter kolom memuat ulang otomatis saat nilai diubah.</p>
                </div>
                <div class="card-header-actions">
                    @can('production.manage')
                        <button type="button" class="btn-add" @click="openCreate()">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                            Buat BOM
                        </button>
                    @endcan
                </div>
            </div>

            <form id="bom-filters" method="GET" action="{{ route('boms.index') }}"></form>
            <div class="table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="col-no">No.</th>
                            <th>Produk</th>
                            <th>Item</th>
                            <th>Status</th>
                            <th class="col-actions"></th>
                        </tr>
                        <tr class="filter-row">
                            <th></th>
                            <th>
                                <input form="bom-filters" class="col-filter" type="search" name="product" value="{{ $filters['product'] ?? '' }}" placeholder="Nama / SKU..." onchange="this.form.submit()">
                            </th>
                            <th></th>
                            <th>
                                <select form="bom-filters" class="col-filter" name="status" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($boms as $bom)
                            @php $row = $bom->toModalArray(); @endphp
                            <tr>
                                <td class="col-no">{{ $boms->firstItem() + $loop->index }}</td>
                                <td>
                                    <button type="button" class="font-semibold hover:underline" @click="openView({{ Js::from($row) }})">{{ $bom->product?->name ?? '—' }}</button>
                                    <p class="mt-0.5 text-[12px] text-muted">{{ $bom->product?->sku ?? '—' }}</p>
                                </td>
                                <td>{{ number_format($row['items_count']) }}</td>
                                <td>
                                    <x-status :value="$bom->is_active ? 'green' : 'gray'">{{ $bom->is_active ? 'Aktif' : 'Nonaktif' }}</x-status>
                                </td>
                                <td class="col-actions">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" class="table-action" title="Lihat" @click="openView({{ Js::from($row) }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                        </button>
                                        @can('production.manage')
                                            <button type="button" class="table-action" title="Edit" @click="openEdit({{ Js::from($row) }})">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-sm text-slate-400">Tidak ada BOM yang cocok dengan filter kolom ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($boms->hasPages())
                <div class="border-t border-line px-5 py-4">{{ $boms->links() }}</div>
            @endif
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="viewOpen" x-cloak @click.self="viewOpen = false">
            <div class="crud-modal !max-w-[880px]">
                <div class="crud-modal-body">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-heading" x-text="viewing?.product_name || 'Detail BOM'"></h3>
                            <p class="mt-1 text-[13px] text-muted" x-text="(viewing?.sku || '—') + ' · ' + (viewing?.status_label || '—')"></p>
                        </div>
                        <button type="button" class="modal-close" @click="viewOpen = false">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </div>

                    <div class="mt-5">
                        <div class="max-w-[200px] rounded-xl bg-[#fafafa] px-4 py-3">
                            <p class="stat-kicker">Item</p>
                            <p class="mt-1 text-lg font-semibold" x-text="viewing?.items_count ?? 0"></p>
                        </div>
                    </div>

                    <dl class="mt-5 text-sm">
                        <div>
                            <dt class="text-[12px] text-muted">Catatan</dt>
                            <dd class="mt-0.5 font-medium" x-text="viewing?.notes_label || '—'"></dd>
                        </div>
                    </dl>

                    <div class="mt-5 overflow-hidden rounded-xl border border-line">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in (viewing?.items || [])" :key="item.id || item.component_id">
                                    <tr>
                                        <td>
                                            <p class="font-semibold" x-text="item.component_name"></p>
                                            <p class="mt-0.5 text-[12px] text-muted"><span x-text="item.sku"></span> · <span x-text="item.unit || '—'"></span></p>
                                        </td>
                                        <td class="font-semibold" x-text="item.quantity_label"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p class="px-4 py-10 text-center text-sm text-slate-400" x-show="! (viewing?.items || []).length">BOM ini belum memiliki item.</p>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-xl border border-line">
                        <p class="px-4 pt-4 text-sm font-medium">Explode 6 level (qty 1)</p>
                        <table class="list-table mt-2">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in (viewing?.exploded || [])" :key="index">
                                    <tr>
                                        <td x-text="row.level"></td>
                                        <td>
                                            <p class="font-semibold" :style="'padding-left: ' + ((row.level - 1) * 16) + 'px'" x-text="row.name"></p>
                                        </td>
                                        <td x-text="row.quantity_label"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <p class="px-4 py-10 text-center text-sm text-slate-400" x-show="! (viewing?.exploded || []).length">Tidak ada item di tahap berikutnya.</p>
                    </div>
                </div>
                <div class="crud-modal-footer">
                    <button type="button" class="btn-ghost" @click="viewOpen = false">Tutup</button>
                    @can('production.manage')
                        <button type="button" class="btn-add" @click="openEdit(viewing)">Edit</button>
                    @endcan
                </div>
            </div>
        </div>

        @can('production.manage')
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="formOpen" x-cloak @click.self="formOpen = false">
                <div class="crud-modal !max-w-[880px]">
                    <form class="flex min-h-0 flex-1 flex-col" method="POST" :action="formMode === 'edit' ? form.update_url : storeUrl">
                        @csrf
                        <input type="hidden" name="_form_mode" :value="formMode">
                        <input type="hidden" name="_bom_id" :value="form.id || ''">
                        <template x-if="formMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="crud-modal-body">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-heading" x-text="formMode === 'edit' ? 'Edit BOM' : 'Buat BOM'"></h3>
                                    <p class="mt-1 text-[13px] text-muted">Resep bahan untuk satu unit menu, sampai 6 tahap. Stok bahan paling bawah berkurang saat menu ini terjual.</p>
                                </div>
                                <button type="button" class="modal-close" @click="formOpen = false">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                                </button>
                            </div>

                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="label">Produk jadi</label>
                                    <select name="product_id" class="input js-product-select" required x-model="form.product_id">
                                        <option value="">Pilih produk</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                        @endforeach
                                    </select>
                                    @error('product_id')<p class="mt-1 text-sm text-red-600" x-show="serverFormError" x-cloak>{{ $message }}</p>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="label">Catatan</label>
                                    <input class="input" name="notes" x-model="form.notes" placeholder="Opsional">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="flex items-center gap-3 text-sm">
                                        <input type="hidden" name="is_active" :value="form.is_active ? 1 : 0">
                                        <input type="checkbox" class="h-4 w-4 rounded border-line" x-model="form.is_active">
                                        Aktif
                                    </label>
                                </div>
                            </div>

                            @error('items')<p class="mt-4 text-sm text-red-600" x-show="serverFormError" x-cloak>{{ $message }}</p>@enderror

                            <div class="mt-6">
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-medium">Item</p>
                                    <button type="button" class="btn-import !px-3 !py-1.5 text-xs" @click="form.items.push(emptyItem())">Tambah item</button>
                                </div>
                                <div class="space-y-3">
                                    <template x-for="(item, index) in form.items" :key="index">
                                        <div class="grid gap-3 rounded-xl border border-line p-4 sm:grid-cols-12">
                                            <div class="sm:col-span-6">
                                                <label class="label">Item</label>
                                                <select class="input js-product-select" :name="'items[' + index + '][component_id]'" x-model="item.component_id" required>
                                                    <option value="">Pilih item</option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="sm:col-span-3">
                                                <label class="label">Unit</label>
                                                <select class="input" :name="'items[' + index + '][unit_id]'" x-model="item.unit_id">
                                                    <option value="">—</option>
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="label">Qty</label>
                                                <input class="input" type="number" step="0.0001" min="0.0001" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required>
                                            </div>
                                            <div class="flex items-end sm:col-span-1">
                                                <button type="button" class="btn-ghost w-full !px-3" @click="form.items.length > 1 && form.items.splice(index, 1)">×</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="crud-modal-footer">
                            <button type="button" class="btn-ghost" @click="formOpen = false">Batal</button>
                            <button class="btn-add" type="submit" x-text="formMode === 'edit' ? 'Simpan perubahan' : 'Buat BOM'"></button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        function bomPage() {
            const emptyItem = () => ({
                component_id: '',
                unit_id: '',
                quantity: 1,
            });
            const emptyForm = () => ({
                id: null,
                product_id: '',
                notes: '',
                is_active: true,
                items: [emptyItem()],
                update_url: '',
            });
            const itemsFrom = (row) => {
                const items = (row?.items || []).map((item) => ({
                    component_id: String(item.component_id || ''),
                    unit_id: String(item.unit_id || ''),
                    quantity: item.quantity || 1,
                }));
                return items.length ? items : [emptyItem()];
            };

            const focus = @json($focusPayload);
            const formError = @json($formError);
            const requestedModal = @json($requestedModal);
            const formOld = @json($formOld);

            let form = emptyForm();
            if (focus) {
                form = { ...emptyForm(), ...focus, items: itemsFrom(focus) };
            }
            if (formError) {
                form = { ...form, ...formOld, items: formOld.items?.length ? formOld.items : form.items };
            }

            return {
                storeUrl: @json($storeUrl),
                formOpen: formError || requestedModal === 'create' || (requestedModal === 'edit' && !! focus),
                formMode: formError ? formOld.mode : (requestedModal === 'edit' ? 'edit' : 'create'),
                form,
                viewOpen: ! formError && requestedModal === 'view' && !! focus,
                viewing: focus ? JSON.parse(JSON.stringify(focus)) : null,
                serverFormError: formError,
                emptyItem,
                openCreate() {
                    this.formMode = 'create';
                    this.form = emptyForm();
                    this.viewOpen = false;
                    this.serverFormError = false;
                    this.formOpen = true;
                },
                openEdit(row) {
                    if (! row) return;
                    this.formMode = 'edit';
                    this.form = { ...emptyForm(), ...row, items: itemsFrom(row) };
                    this.viewOpen = false;
                    this.serverFormError = false;
                    this.formOpen = true;
                },
                openView(row) {
                    if (! row) return;
                    this.viewing = JSON.parse(JSON.stringify(row));
                    this.formOpen = false;
                    this.viewOpen = true;
                },
                closeTop() {
                    if (this.formOpen) this.formOpen = false;
                    else if (this.viewOpen) this.viewOpen = false;
                },
            };
        }
    </script>
@endpush
