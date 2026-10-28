@extends('layouts.app')
@section('title', 'Laporan '.$shift->label)
@section('breadcrumb', 'Shift')
@section('content')
    @php
        $orders = $report['orders'];
        $sales = $report['sales'];
        $compliment = $report['compliment'];
        $moveTypes = collect(\App\Enums\StockMovementType::cases())
            ->filter(fn ($type) => collect($report['stock'])->contains(fn ($row) => (float) ($row['moves'][$type->value] ?? 0) !== 0.0))
            ->values();
    @endphp
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            .shift-report { margin: 0 !important; }
            .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd; }
        }
    </style>

    <div class="shift-report space-y-4">
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="card-header-title">{{ $shift->label }} · {{ $shift->outlet?->name }}</h5>
                    <p class="card-header-subtitle">
                        Dibuka {{ wib($shift->opened_at) }} oleh {{ $shift->opener?->name ?? 'Sistem' }}
                        @if ($shift->closed_at)
                            · Ditutup {{ wib($shift->closed_at) }} oleh {{ $shift->closer?->name ?? 'Sistem' }}
                        @else
                            · <span class="font-semibold text-[#ff9f43]">Masih berjalan, angka masih bisa berubah</span>
                            @if ($shift->scheduled_end) · tutup otomatis {{ wib($shift->scheduled_end) }} @endif
                        @endif
                    </p>
                    @if ($shift->opening_note)<p class="mt-1 text-xs text-muted">Catatan buka: {{ $shift->opening_note }}</p>@endif
                    @if ($shift->closing_note)<p class="mt-1 text-xs text-muted">Catatan tutup: {{ $shift->closing_note }}</p>@endif
                </div>
                <div class="card-header-actions no-print">
                    <a href="{{ route('shifts.index') }}" class="btn-ghost">Riwayat</a>
                    <button type="button" class="btn-add" onclick="window.print()">Cetak laporan</button>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="stat-card"><div>
                <p class="stat-kicker">Order lunas</p>
                <p class="stat-value">{{ number_format($orders['paid']) }}</p>
                <p class="stat-hint">{{ number_format($orders['created']) }} dibuat · {{ number_format($orders['cancelled']) }} batal</p>
            </div></div>
            <div class="stat-card"><div>
                <p class="stat-kicker">Penjualan</p>
                <p class="stat-value">{{ money($sales['grand_total']) }}</p>
                <p class="stat-hint">Subtotal {{ money($sales['subtotal']) }}</p>
            </div></div>
            <div class="stat-card"><div>
                <p class="stat-kicker">Compliment</p>
                <p class="stat-value">{{ number_format($compliment['count']) }}</p>
                <p class="stat-hint">Nilai menu {{ money($compliment['value']) }}</p>
            </div></div>
            <div class="stat-card"><div>
                <p class="stat-kicker">Order belum selesai</p>
                <p class="stat-value">{{ number_format($orders['open']) }}</p>
                <p class="stat-hint">Belum lunas, terbawa ke shift berikutnya</p>
            </div></div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="card overflow-hidden">
                <div class="card-header"><div><h5 class="card-header-title">Ringkasan penjualan</h5></div></div>
                <div class="space-y-3 px-6 pb-6 text-sm">
                    <div class="flex justify-between text-muted"><span>Subtotal</span><span class="font-medium text-heading">{{ money($sales['subtotal']) }}</span></div>
                    <div class="flex justify-between text-muted"><span>Diskon</span><span class="font-medium text-heading">{{ money($sales['discount']) }}</span></div>
                    <div class="flex justify-between text-muted"><span>Compliment</span><span class="font-medium text-heading">{{ money($compliment['value']) }}</span></div>
                    <div class="flex justify-between text-muted"><span>Pajak</span><span class="font-medium text-heading">{{ money($sales['tax']) }}</span></div>
                    <div class="flex justify-between text-muted"><span>Service</span><span class="font-medium text-heading">{{ money($sales['service']) }}</span></div>
                    <div class="flex items-center justify-between rounded-lg bg-heading px-4 py-3 text-white"><span>Total</span><span class="font-semibold">{{ money($sales['grand_total']) }}</span></div>
                </div>
            </div>
            <div class="card overflow-hidden">
                <div class="card-header"><div><h5 class="card-header-title">Per metode bayar</h5></div></div>
                <div class="table-wrap">
                    <table class="list-table">
                        <thead><tr><th>Metode</th><th>Transaksi</th><th>Nominal</th></tr></thead>
                        <tbody>
                            @forelse ($report['payments'] as $row)
                                <tr><td class="font-semibold">{{ $row['label'] }}</td><td>{{ number_format($row['count']) }}</td><td>{{ money($row['amount']) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if ($compliment['orders'])
            <div class="card overflow-hidden">
                <div class="card-header"><div><h5 class="card-header-title">Daftar compliment</h5></div></div>
                <div class="table-wrap">
                    <table class="list-table">
                        <thead><tr><th>Order</th><th>Tamu</th><th>Alasan</th><th>Nilai menu</th></tr></thead>
                        <tbody>
                            @foreach ($compliment['orders'] as $row)
                                <tr><td>{{ $row['order_number'] }}</td><td>{{ $row['guest'] ?? '-' }}</td><td>{{ $row['reason'] ?? '-' }}</td><td>{{ money($row['value']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card overflow-hidden">
            <div class="card-header"><div>
                <h5 class="card-header-title">Produk terjual</h5>
                <p class="card-header-subtitle">Dari order yang lunas selama shift, termasuk compliment.</p>
            </div></div>
            <div class="table-wrap">
                <table class="list-table">
                    <thead><tr><th>Produk</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody>
                        @forelse ($report['products'] as $row)
                            <tr><td>{{ $row['name'] }}</td><td>{{ qty_label($row['qty']) }}</td><td>{{ money($row['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">Belum ada produk terjual.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header"><div>
                <h5 class="card-header-title">Pergerakan stok</h5>
                <p class="card-header-subtitle">Semua pergerakan stok outlet selama shift. Produk tanpa pergerakan tidak ditampilkan.</p>
            </div></div>
            <div class="table-wrap">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Stok awal</th>
                            @foreach ($moveTypes as $type)<th>{{ $type->label() }}</th>@endforeach
                            <th>Stok akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['stock'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ qty_label($row['opening'], $row['unit']) }}</td>
                                @foreach ($moveTypes as $type)
                                    @php $qty = (float) ($row['moves'][$type->value] ?? 0); @endphp
                                    <td class="{{ $qty < 0 ? 'text-brand' : '' }}">{{ $qty === 0.0 ? '—' : ($qty > 0 ? '+' : '').qty_label($qty) }}</td>
                                @endforeach
                                <td class="font-semibold">{{ qty_label($row['closing'], $row['unit']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ 3 + $moveTypes->count() }}" class="py-8 text-center text-sm text-slate-400">Tidak ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($shift->isOpen() && (int) $shift->outlet_id === (int) current_outlet_id())
            @can('pos.access')
                <form method="POST" action="{{ route('shifts.close', $shift) }}" class="card no-print"
                      onsubmit="return confirm('Tutup {{ $shift->label }}? Laporan akan dikunci dan POS terkunci sampai shift baru dibuka.')">
                    @csrf
                    <div class="card-header"><div>
                        <h5 class="card-header-title">Tutup shift</h5>
                        <p class="card-header-subtitle">
                            @if ($orders['open'] > 0)
                                Ada {{ $orders['open'] }} order yang belum lunas. Order ini tetap bisa diselesaikan di shift berikutnya.
                            @else
                                Semua order sudah selesai.
                            @endif
                        </p>
                    </div></div>
                    <div class="flex flex-col gap-3 px-6 pb-6 sm:flex-row">
                        <input name="closing_note" class="input flex-1" maxlength="255" placeholder="Catatan closing (opsional)">
                        <button type="submit" class="btn-brand">Tutup shift</button>
                    </div>
                </form>
            @endcan
        @endif
    </div>
@endsection
