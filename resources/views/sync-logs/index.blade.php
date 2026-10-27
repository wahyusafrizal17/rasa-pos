@extends('layouts.app')
@section('title', 'Log Sinkronisasi')
@section('breadcrumb', 'Katalog')
@section('content')
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Total sync</p>
                <p class="stat-value">{{ number_format($stats['total']) }}</p>
                <p class="stat-hint">Semua proses tercatat</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Berhasil</p>
                <p class="stat-value">{{ number_format($stats['success']) }}</p>
                <p class="stat-hint">Status success</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Gagal</p>
                <p class="stat-value">{{ number_format($stats['failed']) }}</p>
                <p class="stat-hint">Perlu dicek ulang</p>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Log Sinkronisasi Zoho</h5>
                <p class="card-header-subtitle">Riwayat sync katalog: status, penambahan, perubahan, dan penghapusan item.</p>
            </div>
        </div>

        <form id="sync-log-filters" method="GET" action="{{ route('sync-logs.index') }}"></form>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Waktu</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th>Ringkasan</th>
                        <th>Produk</th>
                        <th class="col-actions"></th>
                    </tr>
                    <tr class="filter-row">
                        <th></th>
                        <th>
                            <input form="sync-log-filters" class="col-filter" type="date" name="date" value="{{ $filters['date'] ?? '' }}" onchange="this.form.submit()">
                        </th>
                        <th></th>
                        <th>
                            <select form="sync-log-filters" class="col-filter" name="status" onchange="this.form.submit()">
                                <option value="">Semua</option>
                                <option value="success" @selected(($filters['status'] ?? '') === 'success')>Berhasil</option>
                                <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Gagal</option>
                                <option value="running" @selected(($filters['status'] ?? '') === 'running')>Berjalan</option>
                            </select>
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td class="col-no">{{ $runs->firstItem() + $loop->index }}</td>
                            <td class="text-[13px] text-muted">{{ wib($run->started_at) }}</td>
                            <td>{{ $run->triggerLabel() }}</td>
                            <td>
                                @if ($run->status === 'success')
                                    <span class="badge-soft !bg-emerald-50 !text-emerald-700">{{ $run->statusLabel() }}</span>
                                @elseif ($run->status === 'failed')
                                    <span class="badge-soft !bg-red-50 !text-red-700">{{ $run->statusLabel() }}</span>
                                @else
                                    <span class="badge-soft">{{ $run->statusLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                <p class="font-medium">{{ $run->summaryLabel() }}</p>
                                @if ($run->error)
                                    <p class="mt-0.5 text-[12px] text-red-600">{{ \Illuminate\Support\Str::limit($run->error, 80) }}</p>
                                @endif
                            </td>
                            <td class="text-muted">{{ number_format($run->products) }}</td>
                            <td class="col-actions">
                                <a href="{{ route('sync-logs.show', $run) }}" class="table-action" title="Detail">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-muted">Belum ada log sinkronisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($runs->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $runs->links() }}</div>
        @endif
    </div>
@endsection
