@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'Tambah Customer')
@section('breadcrumb', 'CRM')
@section('content')
    <div class="mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $customer->exists ? 'Perbarui profil pelanggan' : 'Daftarkan pelanggan baru' }}</p>
        <a href="{{ route('customers.index') }}" class="btn-ghost">Kembali</a>
    </div>

    <form
        method="POST"
        action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
        class="card mx-auto max-w-3xl p-6"
    >
        @csrf
        @if ($customer->exists)
            @method('PUT')
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label">Nama</label>
                <input class="input" name="name" required maxlength="150" value="{{ old('name', $customer->name) }}">
            </div>
            <div>
                <label class="label">Telepon</label>
                <input class="input" name="phone" maxlength="30" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div>
                <label class="label">Email</label>
                <input class="input" type="email" name="email" value="{{ old('email', $customer->email) }}">
            </div>
            <div>
                <label class="label">Tanggal lahir</label>
                <input class="input" type="date" name="birthday" value="{{ old('birthday', $customer->birthday?->format('Y-m-d')) }}">
            </div>
            <div>
                <label class="label">Gender</label>
                <select name="gender" class="input">
                    <option value="">—</option>
                    @foreach (['male' => 'Laki-laki', 'female' => 'Perempuan', 'other' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $customer->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Membership</label>
                <select name="membership_level" class="input">
                    @foreach (\App\Enums\MembershipLevel::cases() as $level)
                        <option value="{{ $level->value }}" @selected(old('membership_level', $customer->membership_level?->value) === $level->value)>{{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Alamat</label>
                <textarea class="input min-h-24" name="address">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="flex items-center gap-3 text-sm">
                    <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-line" @checked(old('is_active', $customer->is_active ?? true))>
                    Aktif
                </label>
            </div>
        </div>
        <div class="mt-6 flex gap-2">
            <button class="btn-primary" type="submit">{{ $customer->exists ? 'Simpan perubahan' : 'Buat pelanggan' }}</button>
        </div>
    </form>
@endsection
