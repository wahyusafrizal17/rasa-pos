@extends('layouts.app')
@section('title', 'Transfers')
@section('breadcrumb', 'Inventory')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Mutasi stok antar outlet</p>
        <a href="{{ route('transfers.create') }}" class="btn-primary">Buat transfer</a>
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Dari</th>
                        <th>Ke</th>
                        <th>Status</th>
                        <th>Requester</th>
                        <th>Tanggal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td class="font-medium">{{ $transfer->number }}</td>
                            <td>{{ $transfer->sourceOutlet?->name }}</td>
                            <td>{{ $transfer->destinationOutlet?->name }}</td>
                            <td><x-status :value="$transfer->status->color()">{{ $transfer->status->label() }}</x-status></td>
                            <td>{{ $transfer->requester?->name ?? '—' }}</td>
                            <td>{{ $transfer->transfer_date?->format('d/m/Y') ?? $transfer->created_at?->format('d/m/Y') }}</td>
                            <td><a href="{{ route('transfers.show', $transfer) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-sm text-slate-400">Belum ada transfer stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transfers->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $transfers->links() }}</div>
        @endif
    </div>
@endsection
