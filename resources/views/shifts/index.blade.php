@extends('layouts.app')
@section('title', 'Shift')
@section('breadcrumb', 'Operasional')
@section('content')
    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Riwayat shift</h5>
                <p class="card-header-subtitle">
                    @if ($active)
                        {{ $active->label }} sedang berjalan sejak {{ wib($active->opened_at) }} ({{ $active->opener?->name ?? 'Sistem' }}).
                    @else
                        Tidak ada shift yang sedang berjalan.
                    @endif
                </p>
            </div>
            <div class="card-header-actions">
                @if (auth()->user()->hasPermission('settings.manage') || auth()->user()->hasPermission('outlets.manage'))
                    <a href="{{ route('shifts.schedule') }}" class="btn-ghost">Jadwal shift</a>
                @endif
                @if ($active)
                    <a href="{{ route('shifts.show', $active) }}" class="btn-add">Laporan &amp; tutup shift</a>
                @else
                    @can('pos.access')<a href="{{ route('pos.index') }}" class="btn-add">Buka shift</a>@endcan
                @endif
            </div>
        </div>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Shift</th>
                        <th>Dibuka</th>
                        <th>Ditutup</th>
                        <th>Order lunas</th>
                        <th>Penjualan</th>
                        <th>Status</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr>
                            <td class="font-semibold">{{ $shift->label }}</td>
                            <td>{{ wib($shift->opened_at) }}<br><span class="text-xs text-muted">{{ $shift->opener?->name ?? 'Sistem' }}</span></td>
                            <td>
                                @if ($shift->closed_at)
                                    {{ wib($shift->closed_at) }}<br><span class="text-xs text-muted">{{ $shift->closer?->name ?? 'Sistem' }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $shift->summary ? number_format($shift->summary['orders']['paid'] ?? 0) : '—' }}</td>
                            <td class="font-semibold">{{ $shift->summary ? money($shift->summary['sales']['grand_total'] ?? 0) : '—' }}</td>
                            <td><x-status :value="$shift->isOpen() ? 'orange' : 'green'">{{ $shift->isOpen() ? 'Berjalan' : 'Ditutup' }}</x-status></td>
                            <td class="col-actions">
                                <a href="{{ route('shifts.show', $shift) }}" class="table-action" title="Laporan">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-sm text-slate-400">Belum ada shift di outlet ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shifts->hasPages())
            <div class="border-t border-line px-5 py-4">{{ $shifts->links() }}</div>
        @endif
    </div>
@endsection
