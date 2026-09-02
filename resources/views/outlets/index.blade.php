@extends('layouts.app')
@section('title', 'Outlets')
@section('breadcrumb', 'Settings')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Cabang dan central kitchen</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.85fr_1.15fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Tambah outlet</p>
            <form method="POST" action="{{ route('outlets.store') }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kode</label>
                        <input class="input" name="code" required maxlength="20">
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="120">
                    </div>
                    <div>
                        <label class="label">Kota</label>
                        <input class="input" name="city" maxlength="80">
                    </div>
                    <div>
                        <label class="label">Telepon</label>
                        <input class="input" name="phone" maxlength="30">
                    </div>
                    <div>
                        <label class="label">Buka</label>
                        <input class="input" type="time" name="opens_at">
                    </div>
                    <div>
                        <label class="label">Tutup</label>
                        <input class="input" type="time" name="closes_at">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Alamat</label>
                        <textarea class="input min-h-24" name="address"></textarea>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_central_kitchen" value="1"> Central kitchen
                </label>
                <button class="btn-primary w-full" type="submit">Simpan outlet</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($outlets as $outlet)
                <div class="card p-5" x-data="{ open: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $outlet->name }}</p>
                            <p class="text-xs text-slate-400">{{ $outlet->code }} · {{ $outlet->city }} · {{ $outlet->users_count }} user</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @if ($outlet->is_central_kitchen)
                                    <span class="badge bg-orange-50 text-orange-700">Central kitchen</span>
                                @endif
                                @if ($outlet->is_active)
                                    <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-600">Nonaktif</span>
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="open = !open">Edit</button>
                    </div>
                    <form method="POST" action="{{ route('outlets.update', $outlet) }}" class="mt-4 grid gap-3 sm:grid-cols-2" x-show="open" x-cloak>
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label">Kode</label>
                            <input class="input" name="code" value="{{ $outlet->code }}" required>
                        </div>
                        <div>
                            <label class="label">Nama</label>
                            <input class="input" name="name" value="{{ $outlet->name }}" required>
                        </div>
                        <div>
                            <label class="label">Kota</label>
                            <input class="input" name="city" value="{{ $outlet->city }}">
                        </div>
                        <div>
                            <label class="label">Telepon</label>
                            <input class="input" name="phone" value="{{ $outlet->phone }}">
                        </div>
                        <div>
                            <label class="label">Buka</label>
                            <input class="input" type="time" name="opens_at" value="{{ $outlet->opens_at }}">
                        </div>
                        <div>
                            <label class="label">Tutup</label>
                            <input class="input" type="time" name="closes_at" value="{{ $outlet->closes_at }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Alamat</label>
                            <textarea class="input min-h-20" name="address">{{ $outlet->address }}</textarea>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_central_kitchen" value="1" @checked($outlet->is_central_kitchen)> Central kitchen
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked($outlet->is_active)> Aktif
                        </label>
                        <div class="sm:col-span-2">
                            <button class="btn-primary" type="submit">Update outlet</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="card px-4 py-16 text-center text-sm text-slate-400">Belum ada outlet.</div>
            @endforelse
            @if ($outlets->hasPages())
                <div>{{ $outlets->links() }}</div>
            @endif
        </div>
    </div>
@endsection
