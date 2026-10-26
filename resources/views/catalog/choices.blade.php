@extends('layouts.app')
@section('title', $label)
@section('breadcrumb', 'Catalog')
@section('content')
    <div x-data="choicePage()" @keydown.escape.window="formOpen = false; deleteOpen = false">
        <div class="card overflow-hidden">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">{{ $label }}</h5>
                    <p class="card-header-subtitle">Pilihan yang muncul di kasir saat item dicentang {{ $label }}.</p>
                </div>
                <div class="card-header-actions">
                    @can('products.manage')
                        <button type="button" class="btn-add" @click="openCreate()">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                            Tambah
                        </button>
                    @endcan
                </div>
            </div>
            <div class="table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th class="col-no">No.</th>
                            <th>Nama</th>
                            <th>Harga tambahan</th>
                            <th class="col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($options as $option)
                            <tr>
                                <td class="col-no">{{ $loop->iteration }}</td>
                                <td class="font-semibold">{{ $option->name }}</td>
                                <td>{{ money($option->price) }}</td>
                                <td class="col-actions">
                                    @can('products.manage')
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" class="table-action" title="Edit" @click="openEdit({{ Js::from($option->toModalArray()) }})">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                            </button>
                                            <button type="button" class="table-action table-action-danger" title="Hapus" @click="confirmDelete({{ Js::from($option->toModalArray()) }})">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M9 7V5h6v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                            </button>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-sm text-slate-400">Belum ada pilihan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @can('products.manage')
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="formOpen" x-cloak @click.self="formOpen = false">
                <div class="crud-modal !max-w-md">
                    <form class="flex min-h-0 flex-1 flex-col" method="POST" :action="formMode === 'edit' ? form.update_url : storeUrl">
                        @csrf
                        <template x-if="formMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="crud-modal-body">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-semibold text-heading" x-text="formMode === 'edit' ? 'Edit pilihan' : 'Tambah pilihan'"></h3>
                                <button type="button" class="modal-close" @click="formOpen = false">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                                </button>
                            </div>
                            <div class="mt-5 space-y-4">
                                <div>
                                    <label class="label">Nama</label>
                                    <input class="input" name="name" required maxlength="80" x-model="form.name" placeholder="HOT">
                                </div>
                                <div>
                                    <label class="label">Harga tambahan</label>
                                    <input class="input" type="number" step="1" min="0" name="price" x-model="form.price">
                                    <p class="mt-1.5 text-[12px] text-muted">Isi 0 kalau tidak menambah harga.</p>
                                </div>
                            </div>
                        </div>
                        <div class="crud-modal-footer">
                            <button type="button" class="btn-ghost" @click="formOpen = false">Batal</button>
                            <button class="btn-add" type="submit" x-text="formMode === 'edit' ? 'Simpan' : 'Tambah'"></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/40 p-4" x-show="deleteOpen" x-cloak @click.self="deleteOpen = false">
                <div class="crud-modal-sm">
                    <h3 class="text-lg font-semibold text-heading">Hapus pilihan</h3>
                    <p class="mt-2 text-sm text-muted">Hapus <span class="font-medium text-heading" x-text="form.name"></span>?</p>
                    <form method="POST" class="mt-6 flex justify-end gap-2" :action="form.delete_url">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn-ghost" @click="deleteOpen = false">Batal</button>
                        <button class="btn-danger" type="submit">Hapus</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        function choicePage() {
            return {
                storeUrl: @json(route('catalog.choices.store', $group)),
                formOpen: false,
                deleteOpen: false,
                formMode: 'create',
                form: { name: '', price: 0, update_url: '', delete_url: '' },
                openCreate() {
                    this.formMode = 'create';
                    this.form = { name: '', price: 0, update_url: '', delete_url: '' };
                    this.deleteOpen = false;
                    this.formOpen = true;
                },
                openEdit(row) {
                    this.formMode = 'edit';
                    this.form = { ...row };
                    this.deleteOpen = false;
                    this.formOpen = true;
                },
                confirmDelete(row) {
                    this.form = { ...row };
                    this.formOpen = false;
                    this.deleteOpen = true;
                },
            };
        }
    </script>
@endpush
