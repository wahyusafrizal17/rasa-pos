@extends('layouts.app')
@section('title', 'Jadwal Shift')
@section('breadcrumb', 'Shift')
@section('content')
    @php
        $initial = $rows ?: [
            ['label' => 'Shift 1', 'start' => '07:00', 'end' => '15:00'],
            ['label' => 'Shift 2', 'start' => '15:00', 'end' => '23:00'],
            ['label' => 'Shift 3', 'start' => '23:00', 'end' => '07:00'],
        ];
    @endphp
    <div class="card overflow-hidden" x-data="{ rows: @js(array_values($initial)) }">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Jadwal shift</h5>
                <p class="card-header-subtitle">
                    Jam dalam WIB. Pada jam selesai, shift ditutup otomatis dan shift berikutnya langsung dibuka.
                    Kasir tetap bisa menutup lebih awal. Shift yang melewati tengah malam (mis. 23:00–07:00) didukung.
                </p>
            </div>
            <div class="card-header-actions">
                <form method="GET" action="{{ route('shifts.schedule') }}">
                    <select name="outlet" class="input !w-auto" onchange="this.form.submit()">
                        <option value="">Default (semua outlet)</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($outletId === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('shifts.schedule.update') }}" class="space-y-4 px-6 pb-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="outlet_id" value="{{ $outletId }}">

            @if (! $outletId && ! $rows)
                <p class="rounded-lg bg-[#fff3e8] px-4 py-3 text-sm text-[#b26a1f]">Belum ada jadwal default. Shift belum otomatis sampai jadwal disimpan. Contoh jadwal sudah diisi di bawah.</p>
            @elseif ($usesDefault)
                <p class="rounded-lg bg-[#f6f6f6] px-4 py-3 text-sm text-muted">
                    Outlet ini memakai jadwal default
                    @if ($default) ({{ collect($default)->map(fn ($r) => $r['label'].' '.$r['start'].'–'.$r['end'])->join(', ') }}) @else (belum diatur) @endif.
                    Simpan di bawah untuk membuat jadwal khusus outlet ini.
                </p>
            @endif

            <div class="table-wrap">
                <table class="list-table">
                    <thead><tr><th>Nama shift</th><th>Jam mulai</th><th>Jam selesai</th><th class="col-actions"></th></tr></thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="i">
                            <tr>
                                <td><input class="input" :name="`rows[${i}][label]`" x-model="row.label" maxlength="40" required></td>
                                <td><input class="input" type="time" :name="`rows[${i}][start]`" x-model="row.start" required></td>
                                <td><input class="input" type="time" :name="`rows[${i}][end]`" x-model="row.end" required></td>
                                <td class="col-actions">
                                    <button type="button" class="table-action" title="Hapus" @click="rows.splice(i, 1)" x-show="rows.length > 1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M9 7V5h6v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn-ghost" @click="rows.push({ label: `Shift ${rows.length + 1}`, start: '', end: '' })" x-show="rows.length < 6">+ Tambah shift</button>
                <div class="flex-1"></div>
                @if ($outletId && ! $usesDefault)
                    <button type="submit" name="use_default" value="1" class="btn-ghost" formnovalidate>Pakai jadwal default</button>
                @endif
                <button type="submit" class="btn-brand">Simpan jadwal</button>
            </div>
        </form>
    </div>
@endsection
