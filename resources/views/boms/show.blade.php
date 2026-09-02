@extends('layouts.app')
@section('title', 'BOM '.$bom->version)
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500">{{ $bom->product?->name }} · Yield {{ number_format($bom->yield_percentage, 1) }}% · Waste {{ number_format($bom->waste_percentage ?? 0, 1) }}%</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('boms.edit', $bom) }}" class="btn-ghost">Edit</a>
            <a href="{{ route('boms.index') }}" class="btn-ghost">Kembali</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Waste</th>
                        <th>Yield</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bom->items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->component?->name }}</td>
                            <td>{{ number_format($item->quantity, 4) }}</td>
                            <td>{{ $item->unit?->code ?? $item->component?->unit?->code }}</td>
                            <td>{{ number_format($item->waste_percentage ?? 0, 1) }}%</td>
                            <td>{{ number_format($item->yield_percentage ?? 100, 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-sm text-slate-400">BOM ini belum memiliki komponen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bom->notes)
            <p class="border-t border-line px-5 py-4 text-sm text-slate-500">{{ $bom->notes }}</p>
        @endif
    </div>

    <div class="card mt-4 overflow-hidden">
        <p class="px-5 pt-5 text-sm font-medium">Explode 4 level (qty 1)</p>
        <div class="table-wrap mt-3">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>Komponen</th>
                        <th>Qty</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($exploded ?? [] as $row)
                        <tr>
                            <td>{{ $row['level'] }}</td>
                            <td class="font-medium" style="padding-left: {{ 16 + (($row['level'] - 1) * 16) }}px">{{ $row['name'] }}</td>
                            <td>{{ number_format($row['quantity'], 4) }}</td>
                            <td>{{ $row['unit'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-sm text-slate-400">Tidak ada komponen rekursif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
