@extends('layouts.app')
@section('title', 'Rewards')
@section('breadcrumb', 'Loyalty')
@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-slate-500">Hadiah yang dapat ditukar dengan poin</p>
        <a href="{{ route('loyalty.index') }}" class="btn-ghost">Papan loyalty</a>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Tambah reward</p>
            <form method="POST" action="{{ route('loyalty.rewards.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="120">
                </div>
                <div>
                    <label class="label">Deskripsi</label>
                    <textarea class="input min-h-24" name="description"></textarea>
                </div>
                <div>
                    <label class="label">Poin dibutuhkan</label>
                    <input class="input" type="number" min="1" name="points_required" required>
                </div>
                <div>
                    <label class="label">Nilai</label>
                    <input class="input" type="number" step="0.01" min="0" name="value">
                </div>
                <button class="btn-primary w-full" type="submit">Simpan reward</button>
            </form>
        </div>

        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reward</th>
                            <th>Poin</th>
                            <th>Nilai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rewards as $reward)
                            <tr>
                                <td>
                                    <p class="font-medium">{{ $reward->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $reward->description }}</p>
                                </td>
                                <td>{{ number_format($reward->points_required) }}</td>
                                <td>{{ money($reward->value) }}</td>
                                <td>
                                    @if ($reward->is_active)
                                        <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                                    @else
                                        <span class="badge bg-slate-100 text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-sm text-slate-400">Belum ada reward.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rewards->hasPages())
                <div class="border-t border-line px-4 py-4">{{ $rewards->links() }}</div>
            @endif
        </div>
    </div>
@endsection
