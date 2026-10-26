@extends('layouts.app')
@section('title', 'Keuangan')
@section('breadcrumb', 'Keuangan')
@section('content')
    @php
        $bulanNama = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $tgl = fn ($d) => $d->day.' '.substr($bulanNama[$d->month - 1], 0, 3).' '.$d->year;
        $angka = fn ($n) => number_format((int) $n, 0, ',', '.');
        $menipis = $setting->batas_minimum > 0 && $sisa < $setting->batas_minimum;
        $formTipe = old('tipe', $edit?->tipe ?? 'keluar');
        $formAction = $edit ? route('kas.update', $edit) : route('kas.store');
        $seedItems = [[
            'item' => $edit && $edit->item !== 'Tambah saldo' ? $edit->item : '',
            'keterangan' => $edit?->keterangan ?? '',
            'jumlah' => $edit ? $angka($edit->jumlah) : '',
        ]];
        $formItems = old('items', $seedItems);
        if (! is_array($formItems) || $formItems === []) {
            $formItems = $seedItems;
        }
    @endphp

    <div class="kas">
    <form method="POST" action="{{ route('kas.setting') }}" class="kas-summary">
        @csrf
        @method('PUT')
        <div class="kas-balance">
            <input class="kas-name" name="nama" maxlength="60" value="{{ old('nama', $setting->nama) }}" aria-label="Nama buku kas" required>
            <p class="kas-kicker">Sisa saldo</p>
            <p class="kas-amount {{ $sisa < 0 ? 'is-neg' : ($menipis ? 'is-low' : '') }}">{{ money($sisa) }}</p>
            @if ($sisa < 0)
                <p class="kas-warn">Saldo minus. Segera isi ulang kas.</p>
            @elseif ($menipis)
                <p class="kas-warn">Di bawah batas. Saatnya isi ulang kas.</p>
            @endif
            <label class="kas-limit">
                Ingatkan di bawah
                <input name="batas_minimum" inputmode="numeric" value="{{ old('batas_minimum', $angka($setting->batas_minimum)) }}" aria-label="Batas pengingat saldo" required>
            </label>
        </div>
        <div class="kas-side">
            <label class="kas-stat">
                <span>Saldo awal</span>
                <input name="saldo_awal" inputmode="numeric" value="{{ old('saldo_awal', $angka($setting->saldo_awal)) }}" aria-label="Saldo awal" required>
            </label>
            <div class="kas-stat">
                <span>Tambah saldo</span>
                <strong class="kas-in">{{ money($totalMasuk) }}</strong>
            </div>
            <div class="kas-stat">
                <span>Uang keluar</span>
                <strong class="kas-out">{{ money($totalKeluar) }}</strong>
            </div>
            <button class="btn-add" type="submit">Simpan pengaturan</button>
        </div>
    </form>

    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="card kas-entry mb-4 overflow-hidden" x-data="{
        tipe: @js($formTipe),
        editing: @js((bool) $edit),
        rows: @js(array_values($formItems)),
        add() { this.rows.push({ item: '', keterangan: '', jumlah: '' }) },
        remove(i) { this.rows.splice(i, 1) },
        total() { return this.rows.reduce((sum, row) => sum + (parseInt(String(row.jumlah).replace(/\D/g, ''), 10) || 0), 0) },
        fmt(n) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0) }
    }">
        @csrf
        @if ($edit) @method('PUT') @endif
        <div class="card-header">
            <div>
                <h5 class="card-header-title">{{ $edit ? 'Ubah catatan' : 'Catat' }}</h5>
                <p class="card-header-subtitle">Satu simpan bisa berisi beberapa item. Tanggal dan bukti berlaku untuk semuanya.</p>
            </div>
            <div class="kas-toggle" role="radiogroup" aria-label="Jenis catatan">
                <label :class="tipe === 'keluar' && 'is-out'">
                    <input class="sr-only" type="radio" name="tipe" value="keluar" x-model="tipe"> Pembelian
                </label>
                <label :class="tipe === 'masuk' && 'is-in'">
                    <input class="sr-only" type="radio" name="tipe" value="masuk" x-model="tipe"> Tambah saldo
                </label>
            </div>
        </div>
        <div class="space-y-4 px-6 pb-5">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    @foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif
            <div class="kas-date">
                <label class="label">Tanggal</label>
                <input class="input" type="date" name="tanggal" required value="{{ old('tanggal', $edit?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
            </div>
            <div>
                <div class="kas-head">
                    <span class="label" x-text="tipe === 'masuk' ? 'Item (opsional)' : 'Item'"></span>
                    <span class="label">Keterangan</span>
                    <span class="label">Jumlah</span>
                    <span></span>
                </div>
                <div class="kas-lines">
                    <template x-for="(row, i) in rows" :key="i">
                        <div class="kas-line">
                            <input class="input" maxlength="120" :name="`items[${i}][item]`" x-model="row.item" :placeholder="tipe === 'masuk' ? 'Isi ulang kas' : 'Es batu'">
                            <input class="input" maxlength="255" :name="`items[${i}][keterangan]`" x-model="row.keterangan" :placeholder="tipe === 'masuk' ? 'Dari owner' : '2 karung'">
                            <input class="input" inputmode="numeric" :name="`items[${i}][jumlah]`" x-model="row.jumlah" placeholder="0">
                            <button type="button" class="kas-x" :disabled="rows.length < 2" @click="remove(i)" aria-label="Hapus baris">×</button>
                        </div>
                    </template>
                </div>
                <button type="button" class="kas-add" x-show="!editing" @click="add()">+ Tambah item</button>
            </div>
            <label class="kas-file" x-data="{ name: '' }">
                <span x-text="name || '{{ $edit?->bukti_path ? 'Ganti bukti' : 'Upload bukti / foto nota' }}'"></span>
                <span class="text-xs">JPG, PNG, atau PDF</span>
                <input class="sr-only" type="file" name="bukti" accept="image/*,application/pdf" @change="name = $event.target.files[0]?.name || ''">
            </label>
            @if ($edit?->bukti_path)
                <div class="flex items-center gap-4 text-sm">
                    <a class="font-medium text-brand hover:underline" href="{{ route('kas.bukti', $edit) }}" target="_blank">Lihat bukti tersimpan</a>
                    <label class="flex items-center gap-2 text-muted"><input type="checkbox" name="hapus_bukti" value="1"> Hapus bukti</label>
                </div>
            @endif
        </div>
        <div class="flex items-center justify-between gap-2 border-t border-line px-6 py-4">
            <p class="kas-total font-semibold text-heading">Total <span x-text="fmt(total())"></span></p>
            <div class="flex gap-2">
                @if ($edit)
                    <a class="btn-ghost" href="{{ route('kas.index', array_filter(['bulan' => $bulan])) }}">Batal</a>
                @endif
                <button class="btn-add" type="submit" x-text="{{ $edit ? "'Simpan perubahan'" : "(tipe === 'masuk' ? 'Simpan tambah saldo' : 'Simpan pembelian')" }}"></button>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Riwayat</h5>
                <p class="card-header-subtitle">Sisa saldo dihitung dari saldo awal, berurutan menurut tanggal.</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" action="{{ route('kas.index') }}">
                    <select class="input !w-auto" name="bulan" aria-label="Filter bulan" onchange="this.form.submit()">
                        <option value="">Semua bulan</option>
                        @foreach ($daftarBulan as $ym)
                            @php [$y, $m] = explode('-', $ym); @endphp
                            <option value="{{ $ym }}" @selected($bulan === $ym)>{{ $bulanNama[(int) $m - 1] }} {{ $y }}</option>
                        @endforeach
                    </select>
                </form>
                <a class="btn-ghost" href="{{ route('kas.export') }}">Unduh CSV</a>
            </div>
        </div>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Keterangan</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Sisa saldo</th>
                        <th>Bukti</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($semua->isEmpty())
                        <tr><td colspan="7" class="py-8 text-center text-muted">Belum ada catatan. Isi saldo awal, lalu catat pembelian pertama di atas.</td></tr>
                    @else
                        <tr>
                            <td></td>
                            <td class="font-medium">{{ $bulan ? 'Saldo dari bulan sebelumnya' : 'Saldo awal' }}</td>
                            <td></td><td></td>
                            <td class="text-right font-semibold">{{ money($saldoMulai) }}</td>
                            <td></td><td></td>
                        </tr>
                        @forelse ($baris as $row)
                            @php $t = $row['t']; @endphp
                            <tr>
                                <td>{{ $tgl($t->tanggal) }}</td>
                                <td>{{ $t->isMasuk() && $t->item === 'Tambah saldo' ? '' : $t->item }}</td>
                                <td class="text-muted">{{ $t->keterangan }}</td>
                                <td class="text-right"><span class="kas-pill {{ $t->isMasuk() ? 'in' : 'out' }}">{{ $t->isMasuk() ? '+' : '−' }}{{ $angka($t->jumlah) }}</span></td>
                                <td class="text-right font-semibold {{ $row['saldo'] < 0 ? 'text-red-600' : '' }}">{{ money($row['saldo']) }}</td>
                                <td>
                                    @if ($t->bukti_path)
                                        <a class="font-medium text-brand hover:underline" href="{{ route('kas.bukti', $t) }}" target="_blank">{{ $t->buktiIsPdf() ? 'PDF' : 'Lihat' }}</a>
                                    @else
                                        <form method="POST" action="{{ route('kas.bukti.upload', $t) }}" enctype="multipart/form-data">
                                            @csrf
                                            <label class="cursor-pointer text-sm font-medium text-brand hover:underline">
                                                + Bukti
                                                <input class="sr-only" type="file" name="bukti" accept="image/*,application/pdf" onchange="this.form.submit()" aria-label="Upload bukti untuk {{ $t->item }}">
                                            </label>
                                        </form>
                                    @endif
                                </td>
                                <td class="col-actions">
                                    <a class="text-sm font-medium text-brand hover:underline" href="{{ route('kas.index', array_filter(['edit' => $t->id, 'bulan' => $bulan])) }}">Ubah</a>
                                    <form class="inline" method="POST" action="{{ route('kas.destroy', $t) }}" onsubmit="return confirm('Hapus catatan ini?')">
                                        @csrf @method('DELETE')
                                        <button class="ml-2 text-sm font-medium text-red-600 hover:underline" type="submit">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-muted">Tidak ada catatan di bulan ini.</td></tr>
                        @endforelse
                    @endif
                </tbody>
                @if ($semua->isNotEmpty())
                    <tfoot>
                        <tr>
                            <td></td>
                            <td class="font-semibold">Total</td>
                            <td></td>
                            <td class="text-right text-sm">
                                @if ($periodeMasuk)<span class="text-emerald-700">+{{ $angka($periodeMasuk) }}</span><br>@endif
                                <span class="text-red-600">−{{ $angka($periodeKeluar) }}</span>
                            </td>
                            <td class="text-right font-semibold">{{ money($saldoAkhir) }}</td>
                            <td></td><td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    </div>
@endsection
