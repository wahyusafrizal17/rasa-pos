@extends('layouts.app')
@section('title', 'Stock Opname')
@section('breadcrumb', 'Inventory')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Hitung fisik dan sesuaikan stok sistem</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Buat opname baru</p>
            <form method="POST" action="{{ route('opnames.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Kategori (opsional)</label>
                    <select name="category_id" class="input">
                        <option value="">Semua produk stokable</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Catatan</label>
                    <textarea class="input min-h-24" name="notes"></textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Mulai opname</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Outlet</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opnames as $opname)
                            <tr>
                                <td class="font-medium">{{ $opname->number }}</td>
                                <td>{{ $opname->outlet?->name }}</td>
                                <td><span class="badge bg-slate-100 text-slate-600">{{ $opname->status }}</span></td>
                                <td>
                                    <p>{{ $opname->creator?->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $opname->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td><a href="{{ route('opnames.show', $opname) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Buka</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada stock opname.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($opnames->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $opnames->links() }}</div>
            @endif
        </div>
    </div>
@endsection
