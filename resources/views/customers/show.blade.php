@extends('layouts.app')
@section('title', $customer->name)
@section('breadcrumb', 'Customers')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge bg-slate-100 text-slate-600">{{ $customer->membership_level?->label() }}</span>
                <span class="badge bg-slate-100 text-slate-600">{{ $customer->code }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $customer->phone ?: '—' }} · {{ $customer->email ?: 'Tanpa email' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer) }}" class="btn-ghost">Edit</a>
            <a href="{{ route('customers.index') }}" class="btn-ghost">Kembali</a>
        </div>
    </div>

    <div class="mb-4 grid gap-4 md:grid-cols-3">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Poin</p>
            <p class="mt-2 text-2xl font-semibold">{{ number_format($customer->points) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">Total transaksi</p>
            <p class="mt-2 text-2xl font-semibold">{{ money($customer->total_transaction) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-slate-500">Transaksi terakhir</p>
            <p class="mt-2 text-2xl font-semibold">{{ $customer->last_transaction_at?->format('d/m/Y') ?? '—' }}</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card p-5">
            <p class="text-sm font-medium">Riwayat transaksi</p>
            <div class="mt-4 space-y-3">
                @forelse ($customer->orders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="flex items-center justify-between rounded-2xl border border-line px-4 py-3 text-sm hover:bg-slate-50">
                        <div>
                            <p class="font-medium">{{ $order->order_number }}</p>
                            <p class="text-xs text-slate-400">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">{{ money($order->grand_total) }}</p>
                            <x-status :value="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-status>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada transaksi.</div>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <p class="text-sm font-medium">Points ledger</p>
            <div class="mt-4 space-y-3">
                @forelse ($customer->pointLedgers as $ledger)
                    <div class="flex items-center justify-between rounded-2xl border border-line px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $ledger->reason ?: $ledger->type }}</p>
                            <p class="text-xs text-slate-400">{{ $ledger->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium {{ $ledger->points >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $ledger->points > 0 ? '+' : '' }}{{ $ledger->points }}</p>
                            <p class="text-xs text-slate-400">Saldo {{ number_format($ledger->balance_after) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada mutasi poin.</div>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <p class="text-sm font-medium">Favorites</p>
            <div class="mt-4 space-y-3">
                @forelse ($favorites as $name => $qty)
                    <div class="flex items-center justify-between text-sm">
                        <span>{{ $name }}</span>
                        <span class="badge bg-slate-100 text-slate-600">{{ number_format($qty) }}x</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-400">Belum ada item favorit.</div>
                @endforelse
            </div>
        </div>

        <div class="space-y-4">
            <div class="card p-5">
                <p class="text-sm font-medium">Adjust points</p>
                <form method="POST" action="{{ route('customers.points', $customer) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label">Poin (+ / −)</label>
                        <input class="input" type="number" name="points" required placeholder="100 atau -50">
                    </div>
                    <div>
                        <label class="label">Alasan</label>
                        <input class="input" name="reason" required maxlength="255">
                    </div>
                    <button class="btn-primary w-full" type="submit">Simpan poin</button>
                </form>
            </div>

            <div class="card p-5">
                <p class="text-sm font-medium">Redeem reward</p>
                <form method="POST" action="{{ route('customers.rewards', $customer) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="label">Reward</label>
                        <select name="reward_id" class="input" required>
                            <option value="">Pilih reward</option>
                            @foreach ($rewards as $reward)
                                <option value="{{ $reward->id }}">{{ $reward->name }} · {{ number_format($reward->points_required) }} poin</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($rewards->isEmpty())
                        <p class="text-sm text-slate-400">Belum ada reward aktif.</p>
                    @endif
                    <button class="btn-primary w-full" type="submit" @disabled($rewards->isEmpty())>Tukarkan</button>
                </form>
            </div>
        </div>
    </div>
@endsection
