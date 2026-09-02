@extends('layouts.app')
@section('title', 'Users')
@section('breadcrumb', 'Settings')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Pengguna, role, dan akses outlet</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.85fr_1.15fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Tambah pengguna</p>
            <form method="POST" action="{{ route('users.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="150">
                </div>
                <div>
                    <label class="label">Email</label>
                    <input class="input" type="email" name="email" required>
                </div>
                <div>
                    <label class="label">Telepon</label>
                    <input class="input" name="phone" maxlength="30">
                </div>
                <div>
                    <label class="label">Password</label>
                    <input class="input" type="password" name="password" required minlength="8">
                </div>
                <div>
                    <label class="label">Role</label>
                    <select name="role_id" class="input" required>
                        <option value="">Pilih role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Outlet</label>
                    <select name="outlet_ids[]" class="input min-h-28" multiple>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary w-full" type="submit">Buat pengguna</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($users as $user)
                <div class="card p-5" x-data="{ open: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $user->email }} · {{ $user->phone }}</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <span class="badge bg-slate-100 text-slate-600">{{ $role->label }}</span>
                                @endforeach
                                @foreach ($user->outlets as $outlet)
                                    <span class="badge bg-slate-100 text-slate-600">{{ $outlet->name }}</span>
                                @endforeach
                                @unless ($user->is_active)
                                    <span class="badge bg-red-50 text-red-700">Nonaktif</span>
                                @endunless
                            </div>
                        </div>
                        <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="open = !open">Edit</button>
                    </div>
                    <form method="POST" action="{{ route('users.update', $user) }}" class="mt-4 grid gap-3 sm:grid-cols-2" x-show="open" x-cloak>
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label">Nama</label>
                            <input class="input" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input class="input" type="email" name="email" value="{{ $user->email }}" required>
                        </div>
                        <div>
                            <label class="label">Telepon</label>
                            <input class="input" name="phone" value="{{ $user->phone }}">
                        </div>
                        <div>
                            <label class="label">Password baru</label>
                            <input class="input" type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
                        </div>
                        <div>
                            <label class="label">Role</label>
                            <select name="role_id" class="input" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" @selected($user->roles->contains('id', $role->id))>{{ $role->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Outlet</label>
                            <select name="outlet_ids[]" class="input min-h-24" multiple>
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" @selected($user->outlets->contains('id', $outlet->id))>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Aktif
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <button class="btn-primary" type="submit">Update pengguna</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="card px-4 py-16 text-center text-sm text-slate-400">Belum ada pengguna.</div>
            @endforelse
            @if ($users->hasPages())
                <div>{{ $users->links() }}</div>
            @endif
        </div>
    </div>
@endsection
