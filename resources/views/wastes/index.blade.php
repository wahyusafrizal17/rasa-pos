@extends('layouts.app')
@section('title', 'Waste')
@section('breadcrumb', 'Inventory')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Pencatatan waste yang langsung mengurangi stok</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Catat waste</p>
            <form method="POST" action="{{ route('wastes.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Produk</label>
                    <select name="product_id" class="input" required>
                        <option value="">Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Quantity</label>
                    <input class="input" type="number" step="0.001" min="0.001" name="quantity" required>
                </div>
                <div>
                    <label class="label">Alasan</label>
                    <select name="reason" class="input" required>
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Catatan</label>
                    <textarea class="input min-h-24" name="notes"></textarea>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan waste</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Alasan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($wastes as $waste)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $waste->number }}</p>
                                    <p class="text-xs text-slate-400">{{ $waste->created_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td>{{ $waste->product?->name }}</td>
                                <td>{{ number_format($waste->quantity, 2) }} {{ $waste->product?->unit?->code }}</td>
                                <td>{{ $waste->reason?->label() }}</td>
                                <td>{{ $waste->user?->name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada catatan waste.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($wastes->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $wastes->links() }}</div>
            @endif
        </div>
    </div>
@endsection
