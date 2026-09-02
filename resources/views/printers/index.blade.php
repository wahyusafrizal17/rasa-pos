@extends('layouts.app')
@section('title', 'Printers')
@section('breadcrumb', 'Settings')
@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Routing printer kasir, kitchen, dan bar</p>
    </div>

    <div class="grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="card p-5">
            <p class="text-sm font-medium">Tambah printer</p>
            <form method="POST" action="{{ route('printers.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="label">Nama</label>
                    <input class="input" name="name" required maxlength="80">
                </div>
                <div>
                    <label class="label">Station</label>
                    <select name="station" class="input" required>
                        @foreach ($stations as $station)
                            <option value="{{ $station->value }}">{{ $station->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">IP address</label>
                    <input class="input" name="ip_address" placeholder="192.168.1.50">
                </div>
                <div>
                    <label class="label">Port</label>
                    <input class="input" type="number" name="port" min="1" value="9100">
                </div>
                <div>
                    <label class="label">Kategori routing</label>
                    <select name="category_ids[]" class="input min-h-28" multiple>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-primary w-full" type="submit">Simpan printer</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse ($printers as $printer)
                <div class="card p-5" x-data="{ open: false }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-medium">{{ $printer->name }}</p>
                            <p class="text-xs text-slate-400">{{ $printer->station?->label() }} · {{ $printer->ip_address ?: 'Tanpa IP' }}:{{ $printer->port }}</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                @forelse ($printer->routes as $route)
                                    <span class="badge bg-slate-100 text-slate-600">{{ $route->category?->name }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">Belum ada routing kategori</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-ghost !px-3 !py-1.5 text-xs" @click="open = !open">Edit</button>
                            <form method="POST" action="{{ route('printers.destroy', $printer) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn-danger !px-3 !py-1.5 text-xs" type="submit">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('printers.update', $printer) }}" class="mt-4 grid gap-3 sm:grid-cols-2" x-show="open" x-cloak>
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label">Nama</label>
                            <input class="input" name="name" value="{{ $printer->name }}" required>
                        </div>
                        <div>
                            <label class="label">Station</label>
                            <select name="station" class="input" required>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->value }}" @selected($printer->station?->value === $station->value)>{{ $station->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">IP</label>
                            <input class="input" name="ip_address" value="{{ $printer->ip_address }}">
                        </div>
                        <div>
                            <label class="label">Port</label>
                            <input class="input" type="number" name="port" value="{{ $printer->port }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" @checked($printer->is_active)> Aktif
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <button class="btn-primary" type="submit">Update</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="card px-4 py-16 text-center text-sm text-slate-400">Belum ada printer untuk outlet ini.</div>
            @endforelse
        </div>
    </div>
@endsection
