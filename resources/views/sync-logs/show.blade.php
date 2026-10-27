@extends('layouts.app')
@section('title', 'Detail Sync #'.$run->id)
@section('breadcrumb', 'Katalog')
@section('content')
    @php
        $details = $run->details ?? [];
        $created = $details['created'] ?? [];
        $updated = $details['updated'] ?? [];
        $removed = $details['removed'] ?? [];
    @endphp

    <div class="mb-4">
        <a href="{{ route('sync-logs.index') }}" class="text-sm font-medium text-muted hover:text-heading">← Kembali ke log</a>
    </div>

    <div class="mb-5 grid gap-4 md:grid-cols-4">
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Status</p>
                <p class="stat-value text-[22px]">{{ $run->statusLabel() }}</p>
                <p class="stat-hint">{{ $run->triggerLabel() }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Ditambah</p>
                <p class="stat-value">{{ number_format($run->created_count) }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Diubah</p>
                <p class="stat-value">{{ number_format($run->updated_count) }}</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Dihapus / nonaktif</p>
                <p class="stat-value">{{ number_format($run->removed_count) }}</p>
            </div>
        </div>
    </div>

    <div class="card mb-5 overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Ringkasan</h5>
                <p class="card-header-subtitle">
                    Mulai {{ wib($run->started_at) }}
                    @if ($run->finished_at)
                        · selesai {{ wib($run->finished_at) }}
                    @endif
                    · {{ number_format($run->products) }} produk diproses · {{ number_format($run->outlets) }} outlet · {{ number_format($run->stocks) }} baris stok
                </p>
            </div>
        </div>
        @if ($run->error)
            <div class="border-t border-line px-5 py-4 text-sm text-red-600">{{ $run->error }}</div>
        @endif
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h5 class="card-header-title">Ditambah ({{ count($created) }})</h5>
            </div>
            <div class="max-h-[28rem] overflow-y-auto px-5 py-3">
                @forelse ($created as $row)
                    <div class="border-b border-line py-2 last:border-0">
                        <p class="font-medium">{{ $row['name'] ?? '—' }}</p>
                        <p class="text-[12px] text-muted">{{ $row['sku'] ?? '—' }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-muted">Tidak ada item baru.</p>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <h5 class="card-header-title">Diubah ({{ count($updated) }})</h5>
            </div>
            <div class="max-h-[28rem] overflow-y-auto px-5 py-3">
                @forelse ($updated as $row)
                    <div class="border-b border-line py-2 last:border-0">
                        <p class="font-medium">{{ $row['name'] ?? '—' }}</p>
                        <p class="text-[12px] text-muted">{{ $row['sku'] ?? '—' }}</p>
                        @foreach (($row['changes'] ?? []) as $field => $pair)
                            <p class="mt-1 text-[12px]">
                                <span class="text-muted">{{ $field }}:</span>
                                {{ is_bool($pair[0] ?? null) ? (($pair[0] ?? false) ? 'ya' : 'tidak') : ($pair[0] ?? '—') }}
                                →
                                {{ is_bool($pair[1] ?? null) ? (($pair[1] ?? false) ? 'ya' : 'tidak') : ($pair[1] ?? '—') }}
                            </p>
                        @endforeach
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-muted">Tidak ada perubahan field.</p>
                @endforelse
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <h5 class="card-header-title">Nonaktif / hilang ({{ count($removed) }})</h5>
            </div>
            <div class="max-h-[28rem] overflow-y-auto px-5 py-3">
                @forelse ($removed as $row)
                    <div class="border-b border-line py-2 last:border-0">
                        <p class="font-medium">{{ $row['name'] ?? '—' }}</p>
                        <p class="text-[12px] text-muted">{{ $row['sku'] ?? '—' }}</p>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-muted">Tidak ada item dinonaktifkan.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
