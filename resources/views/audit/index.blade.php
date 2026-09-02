@extends('layouts.app')
@section('title', 'Audit Logs')
@section('breadcrumb', 'Settings')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Jejak aktivitas pengguna</p>
    </div>

    <form method="GET" action="{{ route('audit.index') }}" class="card mb-6 p-5">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <label class="label">Cari aksi</label>
                <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Action">
            </div>
            <div>
                <label class="label">Modul</label>
                <input class="input" name="module" value="{{ request('module') }}" placeholder="orders, inventory, ...">
            </div>
            <button class="btn-primary" type="submit">Filter</button>
            <a href="{{ route('audit.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Modul</th>
                        <th>Aksi</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-slate-500">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $log->module }}</span></td>
                            <td>
                                <p class="font-medium">{{ $log->action }}</p>
                                <p class="text-xs text-slate-400">{{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id }}</p>
                            </td>
                            <td class="text-xs text-slate-400">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada audit log.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="border-t border-line px-4 py-4">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
