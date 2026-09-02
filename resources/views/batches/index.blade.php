@extends('layouts.app')
@section('title', 'Batches')
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Batch hasil produksi</p>
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Produk</th>
                        <th>Outlet</th>
                        <th>Qty</th>
                        <th>Produksi</th>
                        <th>Expired</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td class="font-medium">{{ $batch->batch_number }}</td>
                            <td>{{ $batch->product?->name }}</td>
                            <td>{{ $batch->outlet?->name }}</td>
                            <td>{{ number_format($batch->quantity, 2) }}</td>
                            <td>{{ $batch->produced_at?->format('d/m/Y') }}</td>
                            <td>{{ $batch->expires_at?->format('d/m/Y') ?? '—' }}</td>
                            <td><a href="{{ route('batches.show', $batch) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-sm text-slate-400">Belum ada batch produksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($batches->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $batches->links() }}</div>
        @endif
    </div>
@endsection
