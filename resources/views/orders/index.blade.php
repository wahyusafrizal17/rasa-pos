@extends('layouts.app')
@section('title', 'Orders')
@section('breadcrumb', 'Front of house')
@section('actions')
    @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
        <a href="{{ route('orders.index') }}" class="btn-ghost">Reset filter</a>
    @endif
@endsection
@section('content')
    <form method="GET" action="{{ route('orders.index') }}" class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Tanggal</th>
                        <th>Outlet</th>
                        <th>Pelanggan</th>
                        <th>Meja</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Bayar</th>
                        <th>Total</th>
                        <th>Kasir</th>
                        <th></th>
                    </tr>
                    <tr>
                        <th>
                            <input class="col-filter" type="search" name="order_number" value="{{ $filters['order_number'] ?? '' }}" placeholder="No. order" onchange="this.form.submit()">
                        </th>
                        <th>
                            <input class="col-filter" type="date" name="date" value="{{ $filters['date'] ?? '' }}" onchange="this.form.submit()">
                        </th>
                        <th>
                            <select class="col-filter" name="outlet_id" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected((string) ($filters['outlet_id'] ?? '') === (string) $outlet->id)>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <input class="col-filter" type="search" name="customer" value="{{ $filters['customer'] ?? '' }}" placeholder="Nama" onchange="this.form.submit()">
                        </th>
                        <th>
                            <input class="col-filter" type="search" name="table" value="{{ $filters['table'] ?? '' }}" placeholder="Kode" onchange="this.form.submit()">
                        </th>
                        <th>
                            <select class="col-filter" name="order_type" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (\App\Enums\OrderType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected(($filters['order_type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <select class="col-filter" name="status" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (['draft', 'held', 'new', 'processing', 'preparing', 'ready', 'completed', 'cancelled'] as $status)
                                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \App\Enums\OrderStatus::from($status)->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th>
                            <select class="col-filter" name="payment_status" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                @foreach (\App\Enums\PaymentStatus::cases() as $pay)
                                    <option value="{{ $pay->value }}" @selected(($filters['payment_status'] ?? '') === $pay->value)>{{ $pay->label() }}</option>
                                @endforeach
                            </select>
                        </th>
                        <th></th>
                        <th>
                            <input class="col-filter" type="search" name="cashier" value="{{ $filters['cashier'] ?? '' }}" placeholder="Nama" onchange="this.form.submit()">
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order) }}" class="font-medium hover:underline">{{ $order->order_number }}</a>
                            </td>
                            <td class="text-slate-500">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $order->outlet?->name ?? '—' }}</td>
                            <td>{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td>{{ $order->table?->code ?? '—' }}</td>
                            <td>{{ $order->order_type?->label() ?? '—' }}</td>
                            <td><x-status :value="$order->status->color()">{{ $order->status->label() }}</x-status></td>
                            <td><x-status :value="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-status></td>
                            <td class="font-medium">{{ money($order->grand_total) }}</td>
                            <td>{{ $order->user?->name ?? '—' }}</td>
                            <td><a href="{{ route('orders.show', $order) }}" class="btn-ghost !px-3 !py-1.5 text-xs">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-16 text-center text-sm text-slate-400">Tidak ada order yang cocok dengan filter kolom ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $orders->links() }}</div>
        @endif
    </form>
@endsection
