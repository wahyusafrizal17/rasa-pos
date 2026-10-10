@extends('layouts.app')
@section('title', 'Tables')
@section('breadcrumb', 'Operasional')
@section('content')
@php
    $openForm = $errors->any() ? old('_form') : null;
    $availableCount = $tables->where('status', \App\Enums\TableStatus::Available)->count();
    $occupiedCount = $tables->where('status', \App\Enums\TableStatus::Occupied)->count();
    $reservedCount = $tables->where('status', \App\Enums\TableStatus::Reserved)->count();
@endphp
<div
    class="tables-page"
    x-data="{
        createOpen: {{ $openForm === 'create' ? 'true' : 'false' }},
        editOpen: {{ $openForm === 'edit' ? 'true' : 'false' }},
        reserveOpen: {{ $openForm === 'reserve' ? 'true' : 'false' }},
        transferOpen: {{ $openForm === 'transfer' ? 'true' : 'false' }},
        mergeOpen: {{ $openForm === 'merge' ? 'true' : 'false' }},
        splitOpen: {{ $openForm === 'split' ? 'true' : 'false' }},
        splitSource: @js(old('source_id', '')),
        splitItems: [],
        editing: {
            id: @js(old('_table_id')),
            code: @js(old('code', '')),
            name: @js(old('name', '')),
            capacity: @js((int) old('capacity', 2)),
            shape: @js(old('shape', 'square')),
            zone: @js(old('zone', '')),
        },
        reserveTableId: @js(old('table_id', '')),
        openEdit(table) {
            this.editing = table;
            this.editOpen = true;
        },
        openReserve(id) {
            this.reserveTableId = id;
            this.reserveOpen = true;
        },
        async loadSplitItems() {
            if (!this.splitSource) { this.splitItems = []; return; }
            const res = await fetch('{{ route('tables.live') }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            const table = (data.tables || []).find(t => String(t.id) === String(this.splitSource));
            this.splitItems = table?.items || [];
        }
    }"
    x-init="if (splitSource) loadSplitItems()"
>
    <div class="mb-5 grid gap-4 md:grid-cols-3">
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Available</p>
                <p class="stat-value" id="count-available">{{ $availableCount }}</p>
                <p class="stat-hint">Siap dipakai</p>
            </div>
            <span class="stat-icon bg-[#e8fadf] text-[#28c76f]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Occupied</p>
                <p class="stat-value" id="count-occupied">{{ $occupiedCount }}</p>
                <p class="stat-hint">Sedang dipakai</p>
            </div>
            <span class="stat-icon bg-[#e0f9fc] text-[#00cfe8]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8h16M4 16h16M8 4v16M16 4v16"/></svg>
            </span>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-kicker">Reserved</p>
                <p class="stat-value" id="count-reserved">{{ $reservedCount }}</p>
                <p class="stat-hint">Sudah dibooking</p>
            </div>
            <span class="stat-icon bg-[#fff3e8] text-[#ff9f43]">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
            </span>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Daftar meja</h5>
                <p class="card-header-subtitle">Status terbarui otomatis. Reserve, transfer, merge, dan split tetap dari sini.</p>
            </div>
            <div class="card-header-actions">
                <button type="button" class="btn-ghost !py-2" @click="reserveOpen = true">Reservasi</button>
                <button type="button" class="btn-ghost !py-2" @click="transferOpen = true">Transfer</button>
                <button type="button" class="btn-ghost !py-2" @click="mergeOpen = true">Merge</button>
                @can('tables.manage')
                    <button type="button" class="btn-ghost !py-2" @click="splitOpen = true">Split</button>
                    <button type="button" class="btn-add" @click="createOpen = true">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                        Tambah meja
                    </button>
                @endcan
            </div>
        </div>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Zona</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th class="col-actions"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tables as $table)
                        @php
                            $guest = $table->reservations->first();
                            $order = $table->orders->first();
                        @endphp
                        <tr data-table-id="{{ $table->id }}">
                            <td class="col-no">{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('tables.show', $table) }}" class="font-semibold text-heading hover:underline">{{ $table->code }}</a>
                            </td>
                            <td>{{ $table->name }}</td>
                            <td class="text-muted">{{ $table->zone ?: '—' }}</td>
                            <td>{{ $table->capacity }} pax</td>
                            <td>
                                <x-status :value="$table->status->color()" data-table-status>{{ $table->status->label() }}</x-status>
                            </td>
                            <td>
                                @if ($guest)
                                    {{ $guest->guest_name }}
                                @elseif ($order)
                                    {{ (int) $order->items_count }} item{{ $table->open_minutes ? ' · '.$table->open_minutes.' menit' : '' }}
                                    @if ($order->notes)
                                        <span class="mt-0.5 block text-[12px] text-muted">{{ $order->notes }}</span>
                                    @endif
                                @elseif ($table->open_minutes)
                                    {{ $table->open_minutes }} menit
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="col-actions">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if ($table->status !== \App\Enums\TableStatus::Available)
                                        <form method="POST" action="{{ route('tables.ready', $table) }}">
                                            @csrf
                                            <button type="submit" class="btn-ghost !px-2.5 !py-1 !text-xs">Ready</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('tables.show', $table) }}" class="table-action" title="Lihat">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.3 12S6 6 12 6s9.7 6 9.7 6-3.7 6-9.7 6S2.3 12 2.3 12z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.8"/></svg>
                                    </a>
                                    <button type="button" class="table-action" title="Reservasi" @click="openReserve({{ $table->id }})">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                                    </button>
                                    @can('tables.manage')
                                        <button type="button" class="table-action" title="Edit" @click="openEdit(@js(['id' => $table->id, 'code' => $table->code, 'name' => $table->name, 'capacity' => $table->capacity, 'shape' => $table->shape, 'zone' => $table->zone]))">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('tables.destroy', $table) }}" onsubmit="return confirm('Hapus meja ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="table-action table-action-danger" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M9 7V5h6v2m-7 0v12a1 1 0 001 1h6a1 1 0 001-1V7"/></svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-sm text-slate-400">Belum ada meja.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h5 class="card-header-title">Daftar reservasi</h5>
                <p class="card-header-subtitle">Tamu yang sudah booking di outlet ini.</p>
            </div>
            <span class="badge-soft">{{ $reservations->count() }} aktif</span>
        </div>
        <div class="table-wrap">
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Tamu</th>
                        <th>Meja</th>
                        <th>Waktu</th>
                        <th>Pax</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr class="tables-reserve-row">
                            <td>
                                <div class="tables-guest">
                                    <span class="tables-guest-mark">{{ strtoupper(mb_substr($reservation->guest_name, 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $reservation->guest_name }}</strong>
                                        <span>{{ $reservation->guest_phone ?: 'Tanpa telepon' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('tables.show', $reservation->table_id) }}" class="font-semibold text-heading">{{ $reservation->table?->code ?? '—' }}</a>
                            </td>
                            <td>{{ $reservation->reserved_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $reservation->guest_count }} orang</td>
                            <td class="text-muted">{{ $reservation->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-sm text-slate-400">Belum ada reservasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="createOpen" x-cloak>
        <div class="card w-full max-w-lg p-6" @click.outside="createOpen = false">
            <h3 class="text-lg font-semibold text-heading">Tambah meja</h3>
            @if ($openForm === 'create')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('tables.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="create">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kode</label>
                        <input class="input" name="code" required maxlength="20" placeholder="T-01" value="{{ old('code') }}">
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="50" placeholder="Meja jendela" value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="label">Kapasitas</label>
                        <input class="input" type="number" name="capacity" min="1" value="{{ old('capacity', 2) }}" required>
                    </div>
                    <div>
                        <label class="label">Bentuk</label>
                        <select name="shape" class="input">
                            <option value="square" @selected(old('shape', 'square') === 'square')>Square</option>
                            <option value="round" @selected(old('shape') === 'round')>Round</option>
                            <option value="rect" @selected(old('shape') === 'rect')>Rect</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Zona</label>
                        <input class="input" name="zone" maxlength="50" placeholder="Indoor / Terrace" value="{{ old('zone') }}">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="createOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="editOpen" x-cloak>
        <div class="card w-full max-w-lg p-6" @click.outside="editOpen = false">
            <h3 class="text-lg font-semibold">Edit meja</h3>
            @if ($openForm === 'edit')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" :action="`{{ url('/tables') }}/${editing.id}`" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit">
                <input type="hidden" name="_table_id" :value="editing.id">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kode</label>
                        <input class="input" name="code" required maxlength="20" x-model="editing.code">
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="50" x-model="editing.name">
                    </div>
                    <div>
                        <label class="label">Kapasitas</label>
                        <input class="input" type="number" name="capacity" min="1" required x-model="editing.capacity">
                    </div>
                    <div>
                        <label class="label">Bentuk</label>
                        <select name="shape" class="input" x-model="editing.shape">
                            <option value="square">Square</option>
                            <option value="round">Round</option>
                            <option value="rect">Rect</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Zona</label>
                        <input class="input" name="zone" maxlength="50" x-model="editing.zone">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="editOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="reserveOpen" x-cloak>
        <div class="card w-full max-w-lg p-6" @click.outside="reserveOpen = false">
            <h3 class="text-lg font-semibold">Reservasi meja</h3>
            @if ($openForm === 'reserve')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('tables.reserve') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="reserve">
                <div>
                    <label class="label">Meja</label>
                    <select name="table_id" class="input" required x-model="reserveTableId">
                        <option value="">Pilih meja</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }} · {{ $table->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Nama tamu</label>
                        <input class="input" name="guest_name" required maxlength="100" value="{{ old('guest_name') }}">
                    </div>
                    <div>
                        <label class="label">Telepon</label>
                        <input class="input" name="guest_phone" maxlength="30" value="{{ old('guest_phone') }}">
                    </div>
                    <div>
                        <label class="label">Jumlah tamu</label>
                        <input class="input" type="number" name="guest_count" min="1" value="{{ old('guest_count', 2) }}" required>
                    </div>
                    <div>
                        <label class="label">Waktu</label>
                        <input class="input" type="datetime-local" name="reserved_at" required value="{{ old('reserved_at') }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Pelanggan (opsional)</label>
                        <select name="customer_id" class="input">
                            <option value="">Tidak terkait</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }} · {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Catatan</label>
                        <input class="input" name="notes" placeholder="Permintaan khusus" value="{{ old('notes') }}">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="reserveOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Simpan reservasi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="transferOpen" x-cloak>
        <div class="card w-full max-w-md p-6" @click.outside="transferOpen = false">
            <h3 class="text-lg font-semibold">Transfer meja</h3>
            @if ($openForm === 'transfer')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('tables.transfer') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="transfer">
                <div>
                    <label class="label">Dari</label>
                    <select name="from_id" class="input" required>
                        <option value="">Pilih meja sumber</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected((string) old('from_id') === (string) $table->id)>{{ $table->code }} · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Ke</label>
                    <select name="to_id" class="input" required>
                        <option value="">Pilih meja tujuan</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected((string) old('to_id') === (string) $table->id)>{{ $table->code }} · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="transferOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Pindahkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="splitOpen" x-cloak>
        <div class="card w-full max-w-md p-6" @click.outside="splitOpen = false">
            <h3 class="text-lg font-semibold">Split meja</h3>
            @if ($openForm === 'split')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('tables.split') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="split">
                <div>
                    <label class="label">Sumber</label>
                    <select name="source_id" class="input" required x-model="splitSource" @change="loadSplitItems()">
                        <option value="">Pilih meja sumber</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }} · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Item yang dipindah</label>
                    <div class="max-h-40 space-y-2 overflow-y-auto rounded-xl border border-line p-3">
                        <template x-for="item in splitItems" :key="item.id">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="item_ids[]" :value="item.id">
                                <span x-text="`${item.quantity} × ${item.name}`"></span>
                            </label>
                        </template>
                        <p class="text-xs text-muted" x-show="!splitItems.length">Pilih meja sumber yang sedang terisi.</p>
                    </div>
                </div>
                <div>
                    <label class="label">Tujuan (meja kosong)</label>
                    <select name="target_id" class="input" required>
                        <option value="">Pilih meja tujuan</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @selected((string) old('target_id') === (string) $table->id)>{{ $table->code }} · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="splitOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Pisahkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="mergeOpen" x-cloak>
        <div class="card w-full max-w-md p-6" @click.outside="mergeOpen = false">
            <h3 class="text-lg font-semibold">Merge meja</h3>
            <p class="mt-1 text-sm text-muted">Order meja sumber pindah ke meja target. Meja sumber jadi Available.</p>
            @if ($openForm === 'merge')
                <p class="mt-2 text-sm text-brand">{{ $errors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('tables.merge') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="merge">
                @php $mergeable = $tables->filter(fn ($table) => $table->orders->isNotEmpty()); @endphp
                <div>
                    <label class="label">Sumber (dikosongkan)</label>
                    <select name="source_id" class="input" required>
                        <option value="">Pilih meja sumber</option>
                        @forelse ($mergeable as $table)
                            <option value="{{ $table->id }}" @selected((string) old('source_id') === (string) $table->id)>{{ $table->code }} · {{ (int) $table->orders->first()?->items_count }} item</option>
                        @empty
                            <option value="" disabled>Tidak ada meja berorder</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="label">Target (terima order)</label>
                    <select name="target_id" class="input" required>
                        <option value="">Pilih meja target</option>
                        @forelse ($mergeable as $table)
                            <option value="{{ $table->id }}" @selected((string) old('target_id') === (string) $table->id)>{{ $table->code }} · {{ (int) $table->orders->first()?->items_count }} item</option>
                        @empty
                            <option value="" disabled>Tidak ada meja berorder</option>
                        @endforelse
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-ghost flex-1" @click="mergeOpen = false">Batal</button>
                    <button class="btn-primary flex-1" type="submit">Gabungkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const statusClass = {
        available: 'badge bg-[#e8fadf] text-[#28c76f]',
        occupied: 'badge bg-[#e0f9fc] text-[#00cfe8]',
        reserved: 'badge bg-[#fff3e8] text-[#ff9f43]',
    };

    async function pollTables() {
        try {
            const res = await fetch('{{ route('tables.live') }}', { headers: { 'Accept': 'application/json' } });
            if (! res.ok) return;
            const data = await res.json();
            (data.tables || []).forEach((table) => {
                const row = document.querySelector(`[data-table-id="${table.id}"]`);
                if (! row) return;
                const badge = row.querySelector('[data-table-status]');
                if (badge) {
                    badge.className = statusClass[table.status] || statusClass.available;
                    badge.textContent = table.label;
                }
            });
            if (data.counts) {
                const available = document.getElementById('count-available');
                const occupied = document.getElementById('count-occupied');
                const reserved = document.getElementById('count-reserved');
                if (available) available.textContent = data.counts.available;
                if (occupied) occupied.textContent = data.counts.occupied;
                if (reserved) reserved.textContent = data.counts.reserved;
            }
        } catch (e) {}
        setTimeout(pollTables, 8000);
    }
    pollTables();
</script>
@endpush
