@extends('layouts.app')
@section('title', $opname->number)
@section('breadcrumb', 'Stock Opname')
@section('content')
    @php $locked = filled($opname->finalized_at) || $opname->status === 'finalized'; @endphp

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="badge bg-slate-100 text-slate-600">{{ $opname->status }}</span>
                @if ($opname->finalized_at)
                    <span class="badge bg-emerald-50 text-emerald-700">Finalized {{ $opname->finalized_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $opname->outlet?->name }} · {{ $opname->notes }}</p>
        </div>
        <a href="{{ route('opnames.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form method="POST" action="{{ route('opnames.update', $opname) }}" class="card overflow-hidden">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Sistem</th>
                        <th>Fisik</th>
                        <th>Selisih</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opname->items as $item)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $item->product?->name }}</p>
                                <p class="text-xs text-slate-400">{{ $item->product?->sku }} · {{ $item->product?->unit?->code }}</p>
                                <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                            </td>
                            <td>{{ number_format($item->system_qty, 2) }}</td>
                            <td>
                                <input
                                    class="input !w-28"
                                    type="number"
                                    step="0.001"
                                    name="items[{{ $item->id }}][physical_qty]"
                                    value="{{ old("items.{$item->id}.physical_qty", $item->physical_qty ?? $item->system_qty) }}"
                                    @disabled($locked)
                                    required
                                >
                            </td>
                            <td>{{ number_format($item->difference ?? 0, 2) }}</td>
                            <td>
                                <input
                                    class="input"
                                    name="items[{{ $item->id }}][reason]"
                                    value="{{ old("items.{$item->id}.reason", $item->reason) }}"
                                    @disabled($locked)
                                    placeholder="Opsional"
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-sm text-slate-400">Tidak ada item pada opname ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @unless ($locked)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4">
                <p class="text-sm text-slate-500">Simpan perhitungan fisik sebelum finalisasi.</p>
                <button class="btn-primary" type="submit">Simpan hitungan</button>
            </div>
        @endunless
    </form>

    @unless ($locked)
        <form method="POST" action="{{ route('opnames.finalize', $opname) }}" class="mt-4">
            @csrf
            <button class="btn-primary" type="submit">Finalize opname</button>
        </form>
    @endunless
@endsection
