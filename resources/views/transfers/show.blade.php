@extends('layouts.app')
@section('title', $transfer->number)
@section('breadcrumb', 'Transfers')
@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <x-status :value="$transfer->status->color()">{{ $transfer->status->label() }}</x-status>
            <p class="mt-2 text-sm text-slate-500">
                {{ $transfer->sourceOutlet?->name }} → {{ $transfer->destinationOutlet?->name }}
                · {{ $transfer->transfer_date?->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('transfers.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @if ($transfer->status === \App\Enums\TransferStatus::Draft)
            <form method="POST" action="{{ route('transfers.request', $transfer) }}">
                @csrf
                <button class="btn-primary" type="submit">Ajukan (Request)</button>
            </form>
        @endif
        @if ($transfer->status === \App\Enums\TransferStatus::Requested)
            <form method="POST" action="{{ route('transfers.approve', $transfer) }}">
                @csrf
                <button class="btn-primary" type="submit">Approve</button>
            </form>
        @endif
        @if ($transfer->status === \App\Enums\TransferStatus::Approved)
            <form method="POST" action="{{ route('transfers.ship', $transfer) }}">
                @csrf
                <button class="btn-primary" type="submit">Ship</button>
            </form>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.4fr_.8fr]">
        <div class="card overflow-hidden">
            <form method="POST" action="{{ route('transfers.receive', $transfer) }}">
                @csrf
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty dikirim</th>
                                <th>Qty diterima</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transfer->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $item->product?->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $item->product?->unit?->code }}</p>
                                    </td>
                                    <td>{{ number_format($item->quantity, 2) }}</td>
                                    <td>
                                        @if ($transfer->status === \App\Enums\TransferStatus::Shipped)
                                            <input class="input !w-28" type="number" step="0.001" min="0" name="received[{{ $item->id }}]" value="{{ $item->quantity }}">
                                        @else
                                            {{ number_format($item->received_quantity ?? 0, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-16 text-center text-sm text-slate-400">Tidak ada item.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transfer->status === \App\Enums\TransferStatus::Shipped)
                    <div class="border-t border-line px-5 py-4">
                        <button class="btn-primary" type="submit">Receive</button>
                    </div>
                @endif
            </form>
        </div>

        <div class="card p-5 space-y-3 text-sm">
            <p class="font-medium">Informasi</p>
            <div class="flex justify-between text-slate-500"><span>Requester</span><span class="text-ink">{{ $transfer->requester?->name ?? '—' }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Approver</span><span class="text-ink">{{ $transfer->approver?->name ?? '—' }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Receiver</span><span class="text-ink">{{ $transfer->receiver?->name ?? '—' }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Shipped</span><span class="text-ink">{{ $transfer->shipped_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
            <div class="flex justify-between text-slate-500"><span>Received</span><span class="text-ink">{{ $transfer->received_at?->format('d/m/Y H:i') ?? '—' }}</span></div>
            @if ($transfer->notes)
                <p class="rounded-2xl bg-slate-50 px-4 py-3">{{ $transfer->notes }}</p>
            @endif
        </div>
    </div>
@endsection
