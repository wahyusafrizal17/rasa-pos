@extends('layouts.app')
@section('title', 'Production')
@section('breadcrumb', 'Production')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Perintah produksi semi-finished dan finished goods</p>
        <a href="{{ route('production.create') }}" class="btn-primary">Buat production</a>
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Produk</th>
                        <th>Outlet</th>
                        <th>Planned</th>
                        <th>Produced</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $order->number }}</p>
                                <p class="text-xs text-slate-400">{{ $order->production_date?->format('d/m/Y') }}</p>
                            </td>
                            <td>{{ $order->product?->name }}</td>
                            <td>{{ $order->outlet?->name }}</td>
                            <td>{{ number_format($order->quantity_planned, 2) }}</td>
                            <td>{{ number_format($order->quantity_produced ?? 0, 2) }}</td>
                            <td><x-status :value="$order->status->color()">{{ $order->status->label() }}</x-status></td>
                            <td><a href="{{ route('production.show', $order) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-sm text-slate-400">Belum ada production order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
