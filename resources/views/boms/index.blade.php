@extends('layouts.app')
@section('title', 'BOM')
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Bill of materials untuk produksi</p>
        <a href="{{ route('boms.create') }}" class="btn-primary">Buat BOM</a>
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Versi</th>
                        <th>Yield</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($boms as $bom)
                        <tr>
                            <td class="font-medium">{{ $bom->product?->name }}</td>
                            <td>{{ $bom->version }}</td>
                            <td>{{ number_format($bom->yield_percentage, 1) }}%</td>
                            <td>{{ $bom->items->count() }}</td>
                            <td>
                                @if ($bom->is_active)
                                    <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('boms.show', $bom) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a>
                                    <a href="{{ route('boms.edit', $bom) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-sm text-slate-400">Belum ada BOM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($boms->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $boms->links() }}</div>
        @endif
    </div>
@endsection
