@extends('layouts.app')
@section('title', 'Sales Report')
@section('breadcrumb', 'Reports')
@section('content')
    @unless ($exporting ?? false)
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <p class="text-sm text-slate-500">Laporan penjualan terbayar</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn-ghost">Export XLSX</a>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn-ghost">Export PDF</a>
            </div>
        </div>
        @include('reports._nav')
        @include('reports._period')
        <form method="GET" action="{{ route('reports.sales') }}" class="card mb-6 p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label class="label">Dari</label>
                    <input class="input" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div>
                    <label class="label">Sampai</label>
                    <input class="input" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div>
                    <label class="label">Outlet</label>
                    <select name="outlet_id" class="input">
                        <option value="">Semua</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((string) ($filters['outlet_id'] ?? '') === (string) $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Cari</label>
                    <input class="input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="No. order">
                </div>
                <div class="flex items-end">
                    <button class="btn-primary w-full" type="submit">Filter</button>
                </div>
            </div>
        </form>
    @endunless

    <div class="{{ ($exporting ?? false) ? '' : 'card overflow-hidden' }}">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Order</th>
                        <th>Outlet</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $order)
                        <tr>
                            <td class="font-medium">{{ $order->order_number }}</td>
                            <td>{{ $order->outlet?->name }}</td>
                            <td>{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td>{{ money($order->grand_total) }}</td>
                            <td>{{ $order->status?->label() ?? $order->status?->value }}</td>
                            <td>{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-sm text-slate-400">Tidak ada data penjualan pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @unless ($exporting ?? false)
            @if ($rows->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $rows->links() }}</div>
            @endif
        @endunless
    </div>
@endsection
