@extends('layouts.pos')
@section('content')
    <div class="flex flex-1 items-center justify-center overflow-y-auto bg-[#faf7f3] p-4">
        <form method="POST" action="{{ route('shifts.store') }}" class="pay-modal w-full max-w-md">
            @csrf
            <div class="pay-modal-hero">
                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-white/45">Shift belum dibuka</p>
                <p class="mt-1 text-[18px] font-semibold text-white">Buka shift untuk mulai transaksi</p>
                @if ($lastShift)
                    <p class="mt-2 text-[12px] text-white/50">
                        Terakhir: {{ $lastShift->label }} · {{ wib($lastShift->opened_at) }}
                        @if ($lastShift->closed_at) – {{ wib($lastShift->closed_at, 'H:i') }} oleh {{ $lastShift->closer?->name ?? 'Sistem' }} @endif
                    </p>
                @endif
                @if ($defaultLabel)
                    <p class="mt-1 text-[12px] text-white/50">Shift sebelumnya ditutup lebih awal. {{ $defaultLabel }} masih dalam jam jadwal, jadi bisa dibuka ulang di sini. Shift berikutnya terbuka otomatis sesuai jadwal.</p>
                @endif
            </div>
            <div class="space-y-4 px-6 py-5">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted">Pilih shift</p>
                    <div class="mt-2.5 grid grid-cols-3 gap-2" x-data="{ label: @js(old('label', $defaultLabel ?? $labels[0])) }">
                        @foreach ($labels as $label)
                            <label class="pay-method cursor-pointer text-center" :class="label === @js($label) ? 'pay-method-active' : ''">
                                <input type="radio" name="label" value="{{ $label }}" class="sr-only" x-model="label">
                                <span class="block text-[13px] font-semibold">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="label" for="opening_note">Catatan (opsional)</label>
                    <input id="opening_note" name="opening_note" class="input" maxlength="255" value="{{ old('opening_note') }}" placeholder="mis. Kasir: Andi, Rina">
                </div>
                <p class="text-xs text-muted">Dibuka oleh {{ auth()->user()->name }} · {{ wib(now()) }}</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('shifts.index') }}" class="btn-ghost !rounded-xl text-center">Riwayat shift</a>
                    <button type="submit" class="btn-brand !rounded-xl">Buka shift</button>
                </div>
            </div>
        </form>
    </div>
@endsection
