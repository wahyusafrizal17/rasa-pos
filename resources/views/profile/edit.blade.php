@extends('layouts.app')
@section('title', 'Profil')
@section('breadcrumb', 'Akun')
@section('content')
    @php
        $roleLabel = $user->roles->pluck('label')->filter()->join(' · ') ?: 'Pengguna';
        $outletLabel = $user->outlets->pluck('name')->filter()->join(', ') ?: (current_outlet()?->name ?? 'Tanpa outlet');
    @endphp

    <div class="mx-auto grid max-w-4xl gap-4">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="card overflow-hidden" x-data="profilePhoto(@js($user->avatarUrl()))">
            @csrf
            @method('PUT')
            <input type="hidden" name="remove_avatar" :value="removeAvatar ? 1 : 0">
            <input class="sr-only" type="file" name="avatar" x-ref="file" accept="image/jpeg,image/png,image/webp" @change="onChange">

            <div class="profile-banner"></div>

            <div class="px-6 pb-6 pt-0 sm:px-8">
                <div class="-mt-14 flex flex-col gap-5 sm:flex-row sm:items-end">
                    <button type="button" class="profile-photo shrink-0 self-start" @click="pick()">
                        <img x-show="preview" x-cloak :src="preview" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        <span class="profile-photo-fallback" x-show="!preview">{{ $user->initials() }}</span>
                        <span class="profile-photo-overlay">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M8 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>Ubah foto</span>
                        </span>
                    </button>

                    <div class="min-w-0 flex-1 pb-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">Profil saya</p>
                        <h2 class="mt-1 truncate text-2xl font-semibold tracking-tight text-heading">{{ $user->name }}</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="badge bg-brand-soft text-brand">{{ $roleLabel }}</span>
                            <span class="text-sm text-muted">{{ $outletLabel }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 pb-1">
                        <button type="button" class="btn-ghost !rounded-xl !px-3.5 !py-2 text-xs" @click="pick()">Unggah foto</button>
                        <button type="button" class="btn-ghost !rounded-xl !px-3.5 !py-2 text-xs text-muted" x-show="preview" x-cloak @click="clear()">Hapus foto</button>
                    </div>
                </div>

                <p class="mt-4 text-xs text-muted">JPG, PNG, atau WEBP. Maksimal 2 MB. Foto ini tampil di header aplikasi.</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="255" value="{{ old('name', $user->name) }}">
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input class="input" type="email" name="email" required value="{{ old('email', $user->email) }}">
                    </div>
                    <div>
                        <label class="label">Telepon</label>
                        <input class="input" name="phone" maxlength="30" value="{{ old('phone', $user->phone) }}" placeholder="08…">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button class="btn-brand !rounded-xl" type="submit">Simpan profil</button>
                </div>
            </div>
        </form>

        <div class="card p-6 sm:p-8">
            <div class="flex items-start gap-3">
                <div class="icon-tile bg-brand-soft text-brand">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-heading">Keamanan</p>
                    <p class="mt-0.5 text-sm text-muted">Perbarui password untuk menjaga akun tetap aman.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <div class="sm:col-span-2">
                    <label class="label">Password saat ini</label>
                    <input class="input" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div>
                    <label class="label">Password baru</label>
                    <input class="input" type="password" name="password" required autocomplete="new-password">
                </div>
                <div>
                    <label class="label">Konfirmasi password</label>
                    <input class="input" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <div class="flex justify-end sm:col-span-2">
                    <button class="btn-primary !rounded-xl" type="submit">Ubah password</button>
                </div>
            </form>
        </div>

        <div class="card flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <p class="font-semibold text-heading">Sesi perangkat</p>
                <p class="mt-0.5 text-sm text-muted">Akhiri sesi di perangkat ini. Order yang sedang berjalan tidak terpengaruh.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-ghost !rounded-xl text-brand hover:bg-brand-soft" type="submit">Logout</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function profilePhoto(initial) {
        return {
            preview: initial,
            removeAvatar: false,
            pick() {
                this.$refs.file?.click();
            },
            onChange(event) {
                const file = event.target.files?.[0];
                if (! file) return;
                this.removeAvatar = false;
                if (this.preview && String(this.preview).startsWith('blob:')) {
                    URL.revokeObjectURL(this.preview);
                }
                this.preview = URL.createObjectURL(file);
            },
            clear() {
                this.removeAvatar = true;
                if (this.preview && String(this.preview).startsWith('blob:')) {
                    URL.revokeObjectURL(this.preview);
                }
                this.preview = null;
                if (this.$refs.file) this.$refs.file.value = '';
            },
        };
    }
</script>
@endpush
