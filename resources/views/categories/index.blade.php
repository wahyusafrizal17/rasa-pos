@extends('layouts.app')
@section('title', 'Categories')
@section('breadcrumb', 'Catalog')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Kelompok menu dan station cetak</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Tambah kategori</p>
            <form method="POST" action="{{ route('categories.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="80">
                </div>
                <div>
                    <label class="label">Station</label>
                    <select name="station" class="input">
                        <option value="">—</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="bar">Bar</option>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>
                <div>
                    <label class="label">Warna</label>
                    <input class="input" name="color" maxlength="20" placeholder="#c2410c">
                </div>
                <div>
                    <label class="label">Urutan</label>
                    <input class="input" type="number" name="sort_order" value="0">
                </div>
                <button class="btn-primary w-full" type="submit">Simpan</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Station</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr x-data="{ open: false }">
                                <td>
                                    <p class="font-medium">{{ $category->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $category->slug }}</p>
                                </td>
                                <td>{{ $category->station ?: '—' }}</td>
                                <td>{{ $category->sort_order }}</td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="open = !open">Edit</button>
                                        <form method="POST" action="{{ route('categories.destroy', $category) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger !px-3 !py-1.5 text-xs" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                    <form method="POST" action="{{ route('categories.update', $category) }}" class="mt-3 space-y-2 rounded-2xl bg-slate-50 p-3" x-show="open" x-cloak>
                                        @csrf
                                        @method('PUT')
                                        <input class="input" name="name" value="{{ $category->name }}" required>
                                        <select name="station" class="input">
                                            <option value="">—</option>
                                            @foreach (['kitchen', 'bar', 'cashier'] as $station)
                                                <option value="{{ $station }}" @selected($category->station === $station)>{{ ucfirst($station) }}</option>
                                            @endforeach
                                        </select>
                                        <input class="input" name="color" value="{{ $category->color }}" placeholder="Warna">
                                        <input class="input" type="number" name="sort_order" value="{{ $category->sort_order }}">
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Aktif
                                        </label>
                                        <button class="btn-primary w-full" type="submit">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada kategori.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($categories->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>
@endsection
