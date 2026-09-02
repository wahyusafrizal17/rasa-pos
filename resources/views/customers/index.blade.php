@extends('layouts.app')
@section('title', 'Customers')
@section('breadcrumb', 'CRM')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Database pelanggan, membership, dan poin</p>
        <a href="{{ route('customers.create') }}" class="btn-primary">Tambah pelanggan</a>
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="card mb-6 p-5">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-64 flex-1">
                <label class="label">Cari</label>
                <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Nama, telepon, atau email">
            </div>
            <button class="btn-primary" type="submit">Cari</button>
            <a href="{{ route('customers.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Kontak</th>
                        <th>Level</th>
                        <th>Poin</th>
                        <th>Total belanja</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $customer) }}" class="font-medium hover:underline">{{ $customer->name }}</a>
                                <p class="text-xs text-slate-400">{{ $customer->code }}</p>
                            </td>
                            <td>
                                <p>{{ $customer->phone ?: '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $customer->email }}</p>
                            </td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $customer->membership_level?->label() }}</span></td>
                            <td>{{ number_format($customer->points) }}</td>
                            <td>{{ money($customer->total_transaction) }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-sm text-slate-400">Belum ada pelanggan tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
