@extends('layouts.app')
@section('title', 'Promo Performance')
@section('breadcrumb', 'Reports')
@section('content')
    @unless ($exporting ?? false)
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <p class="text-sm text-slate-500">Performa diskon dan bundle per program</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn-ghost">Export XLSX</a>
                <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn-ghost">Export PDF</a>
            </div>
        </div>
        @include('reports._nav')
        @include('reports._period')
        <form method="GET" action="{{ route('reports.promo') }}" class="card mb-6 p-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
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
                <div class="flex items-end">
                    <button class="btn-primary w-full" type="submit">Filter</button>
                </div>
            </div>
        </form>
    @endunless

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <p class="px-5 pt-5 text-sm font-medium">Diskon</p>
            <div class="table-wrap mt-3">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Pakai</th>
                            <th>Diskon</th>
                            <th>Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($discounts as $row)
                            <tr>
                                <td class="font-medium">{{ $row->name }}</td>
                                <td>{{ $row->usage_count }}</td>
                                <td>{{ money($row->discount_total) }}</td>
                                <td>{{ money($row->sales_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-sm text-slate-400">Belum ada pemakaian diskon.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card overflow-hidden">
            <p class="px-5 pt-5 text-sm font-medium">Bundle</p>
            <div class="table-wrap mt-3">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Qty</th>
                            <th>Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundles as $row)
                            <tr>
                                <td class="font-medium">{{ $row->name }}</td>
                                <td>{{ number_format($row->qty, 0) }}</td>
                                <td>{{ money($row->sales_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center text-sm text-slate-400">Belum ada penjualan bundle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
