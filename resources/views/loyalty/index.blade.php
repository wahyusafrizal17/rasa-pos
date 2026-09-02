@extends('layouts.app')
@section('title', 'Loyalty')
@section('breadcrumb', 'Marketing')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Papan poin pelanggan dan reward aktif</p>
        <a href="{{ route('loyalty.rewards') }}" class="btn-primary">Kelola rewards</a>
    </div>

    <div class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($rewards as $reward)
            <div class="card p-5">
                <p class="text-sm font-medium">{{ $reward->name }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ number_format($reward->points_required) }}</p>
                <p class="mt-1 text-xs text-slate-400">poin · nilai {{ money($reward->value) }}</p>
            </div>
        @empty
            <div class="card px-4 py-10 text-center text-sm text-slate-400 md:col-span-2">Belum ada reward aktif.</div>
        @endforelse
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
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
                                <p class="font-medium">{{ $customer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $customer->phone }}</p>
                            </td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $customer->membership_level?->label() }}</span></td>
                            <td class="font-medium">{{ number_format($customer->points) }}</td>
                            <td>{{ money($customer->total_transaction) }}</td>
                            <td><a href="{{ route('customers.show', $customer) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Profil</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada data loyalty.</td>
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
