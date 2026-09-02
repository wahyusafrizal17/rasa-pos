@extends('layouts.app')
@section('title', 'Tables')
@section('breadcrumb', 'Front of house')
@section('content')
<div
    class="space-y-6"
    x-data="{
        createOpen: false,
        editOpen: false,
        reserveOpen: false,
        transferOpen: false,
        mergeOpen: false,
        splitOpen: false,
        splitSource: '',
        splitItems: [],
        editing: { id: null, code: '', name: '', capacity: 2, shape: 'square', zone: '' },
        reserveTableId: '',
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
>
    @php
        $availableCount = $tables->where('status', \App\Enums\TableStatus::Available)->count();
        $occupiedCount = $tables->where('status', \App\Enums\TableStatus::Occupied)->count();
        $reservedCount = $tables->where('status', \App\Enums\TableStatus::Reserved)->count();
    @endphp

    <div class="card px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted">Floor plan</p>
                <p class="mt-1 text-sm text-heading">Geser kartu untuk mengatur posisi meja</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-ghost !py-2" @click="reserveOpen = true">Reservasi</button>
                <button type="button" class="btn-ghost !py-2" @click="transferOpen = true">Transfer</button>
                <button type="button" class="btn-ghost !py-2" @click="mergeOpen = true">Merge</button>
                <button type="button" class="btn-ghost !py-2" @click="splitOpen = true">Split</button>
                <button type="button" class="btn-primary !py-2" @click="createOpen = true">Tambah meja</button>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-[#e8fadf] px-3 py-1 text-xs font-medium text-[#16a34a]">
                <span class="h-2 w-2 rounded-full bg-[#16a34a]"></span> Available · <span id="count-available">{{ $availableCount }}</span>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-brand-soft px-3 py-1 text-xs font-medium text-brand">
                <span class="h-2 w-2 rounded-full bg-brand"></span> Occupied · <span id="count-occupied">{{ $occupiedCount }}</span>
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-[#fff3e8] px-3 py-1 text-xs font-medium text-[#d97706]">
                <span class="h-2 w-2 rounded-full bg-[#f4a024]"></span> Reserved · <span id="count-reserved">{{ $reservedCount }}</span>
            </span>
        </div>
    </div>

    <div class="floor-canvas">
        @forelse ($tables as $index => $table)
            @php
                $statusClass = match ($table->status) {
                    \App\Enums\TableStatus::Available => 'table-tile-available',
                    \App\Enums\TableStatus::Occupied => 'table-tile-occupied',
                    \App\Enums\TableStatus::Reserved => 'table-tile-reserved',
                    default => '',
                };
                $badge = match ($table->status) {
                    \App\Enums\TableStatus::Available => 'bg-[#e8fadf] text-[#16a34a]',
                    \App\Enums\TableStatus::Occupied => 'bg-brand-soft text-brand',
                    \App\Enums\TableStatus::Reserved => 'bg-[#fff3e8] text-[#d97706]',
                    default => 'bg-slate-100 text-slate-500',
                };
                $accent = match ($table->status) {
                    \App\Enums\TableStatus::Available => 'bg-[#16a34a]',
                    \App\Enums\TableStatus::Occupied => 'bg-brand',
                    \App\Enums\TableStatus::Reserved => 'bg-[#f4a024]',
                    default => 'bg-slate-300',
                };
                $shapeClass = match ($table->shape) {
                    'round' => '!rounded-[36px]',
                    'rect' => '!rounded-2xl',
                    default => '',
                };
            @endphp
            <div
                class="absolute z-10"
                x-data="tableDrag({{ $table->id }}, {{ (int) ($table->pos_x ?? (32 + ($index % 5) * 196)) }}, {{ (int) ($table->pos_y ?? (32 + intdiv($index, 5) * 180)) }})"
                :style="`left:${x}px;top:${y}px`"
                @mousedown.prevent="start($event)"
                @mousemove.window="move($event)"
                @mouseup.window="end()"
            >
                <div class="table-tile {{ $statusClass }} {{ $shapeClass }}" data-table-id="{{ $table->id }}">
                    <div class="mb-3 h-1 w-10 rounded-full {{ $accent }}" data-table-accent></div>
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('tables.show', $table) }}" class="text-lg font-semibold leading-none text-heading" @mousedown.stop>{{ $table->code }}</a>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $badge }}" data-table-status>{{ $table->status->label() }}</span>
                    </div>
                    <p class="mt-2 text-xs text-muted">{{ $table->name }} · {{ $table->capacity }} pax</p>
                    @if ($table->zone)
                        <p class="mt-0.5 text-[11px] text-muted">{{ $table->zone }}</p>
                    @endif
                    @if ($table->activeSession)
                        <p class="mt-2 text-[11px] font-medium text-heading">{{ $table->activeSession->durationMinutes() }} menit</p>
                    @endif
                    <div class="mt-3 flex items-center gap-2 border-t border-[#f0f0f0] pt-2.5" @mousedown.stop>
                        <button type="button" class="text-[11px] font-medium text-heading hover:underline" @click="openEdit(@js(['id' => $table->id, 'code' => $table->code, 'name' => $table->name, 'capacity' => $table->capacity, 'shape' => $table->shape, 'zone' => $table->zone]))">Edit</button>
                        <span class="text-[#ddd]">·</span>
                        <button type="button" class="text-[11px] font-medium text-heading hover:underline" @click="openReserve({{ $table->id }})">Reserve</button>
                        <span class="text-[#ddd]">·</span>
                        <form method="POST" action="{{ route('tables.destroy', $table) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[11px] font-medium text-brand hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <p class="text-sm font-medium text-heading">Belum ada meja</p>
                <p class="mt-1 text-xs text-muted">Tambahkan meja untuk mulai mengatur floor plan.</p>
            </div>
        @endforelse
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="createOpen" x-cloak>
        <div class="card w-full max-w-lg p-6" @click.outside="createOpen = false">
            <h3 class="text-lg font-semibold text-heading">Tambah meja</h3>
            <form method="POST" action="{{ route('tables.store') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Kode</label>
                        <input class="input" name="code" required maxlength="20" placeholder="T-01">
                    </div>
                    <div>
                        <label class="label">Nama</label>
                        <input class="input" name="name" required maxlength="50" placeholder="Meja jendela">
                    </div>
                    <div>
                        <label class="label">Kapasitas</label>
                        <input class="input" type="number" name="capacity" min="1" value="2" required>
                    </div>
                    <div>
                        <label class="label">Bentuk</label>
                        <select name="shape" class="input">
                            <option value="square">Square</option>
                            <option value="round">Round</option>
                            <option value="rect">Rect</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Zona</label>
                        <input class="input" name="zone" maxlength="50" placeholder="Indoor / Terrace">
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
            <form method="POST" :action="`{{ url('/tables') }}/${editing.id}`" class="mt-5 space-y-4">
                @csrf
                @method('PUT')
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
            <form method="POST" action="{{ route('tables.reserve') }}" class="mt-5 space-y-4">
                @csrf
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
                        <input class="input" name="guest_name" required maxlength="100">
                    </div>
                    <div>
                        <label class="label">Telepon</label>
                        <input class="input" name="guest_phone" maxlength="30">
                    </div>
                    <div>
                        <label class="label">Jumlah tamu</label>
                        <input class="input" type="number" name="guest_count" min="1" value="2" required>
                    </div>
                    <div>
                        <label class="label">Waktu</label>
                        <input class="input" type="datetime-local" name="reserved_at" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Pelanggan (opsional)</label>
                        <select name="customer_id" class="input">
                            <option value="">Tidak terkait</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Catatan</label>
                        <input class="input" name="notes" placeholder="Permintaan khusus">
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
            <form method="POST" action="{{ route('tables.transfer') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label">Dari</label>
                    <select name="from_id" class="input" required>
                        <option value="">Pilih meja sumber</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }} · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Ke</label>
                    <select name="to_id" class="input" required>
                        <option value="">Pilih meja tujuan</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }} · {{ $table->status->label() }}</option>
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
            <form method="POST" action="{{ route('tables.split') }}" class="mt-5 space-y-4">
                @csrf
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
                            <option value="{{ $table->id }}">{{ $table->code }} · {{ $table->status->label() }}</option>
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
            <form method="POST" action="{{ route('tables.merge') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label">Sumber</label>
                    <select name="source_id" class="input" required>
                        <option value="">Pilih meja sumber</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Target</label>
                    <select name="target_id" class="input" required>
                        <option value="">Pilih meja target</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}">{{ $table->code }}</option>
                        @endforeach
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
    window.tableDrag = function (id, startX, startY) {
        return {
            x: startX,
            y: startY,
            dragging: false,
            startClientX: 0,
            startClientY: 0,
            originX: 0,
            originY: 0,
            start(event) {
                this.dragging = true;
                this.startClientX = event.clientX;
                this.startClientY = event.clientY;
                this.originX = this.x;
                this.originY = this.y;
            },
            move(event) {
                if (! this.dragging) return;
                this.x = Math.max(0, this.originX + (event.clientX - this.startClientX));
                this.y = Math.max(0, this.originY + (event.clientY - this.startClientY));
            },
            async end() {
                if (! this.dragging) return;
                this.dragging = false;
                await fetch('/tables/' + id + '/move', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({ pos_x: Math.round(this.x), pos_y: Math.round(this.y) }),
                });
            },
        };
    };

    async function pollTables() {
        try {
            const res = await fetch('{{ route('tables.live') }}', { headers: { 'Accept': 'application/json' } });
            if (! res.ok) return;
            const data = await res.json();
            const styles = {
                available: { tile: 'table-tile-available', badge: 'bg-[#e8fadf] text-[#16a34a]', accent: 'bg-[#16a34a]' },
                occupied: { tile: 'table-tile-occupied', badge: 'bg-brand-soft text-brand', accent: 'bg-brand' },
                reserved: { tile: 'table-tile-reserved', badge: 'bg-[#fff3e8] text-[#d97706]', accent: 'bg-[#f4a024]' },
            };
            (data.tables || []).forEach((table) => {
                const tile = document.querySelector(`[data-table-id="${table.id}"]`);
                if (! tile) return;
                tile.classList.remove('table-tile-available', 'table-tile-occupied', 'table-tile-reserved');
                tile.classList.add(styles[table.status]?.tile || 'table-tile-available');
                const badge = tile.querySelector('[data-table-status]');
                if (badge) {
                    badge.className = 'rounded-full px-2 py-0.5 text-[10px] font-semibold ' + (styles[table.status]?.badge || '');
                    badge.textContent = table.label;
                }
                const accent = tile.querySelector('[data-table-accent]');
                if (accent) {
                    accent.className = 'mb-3 h-1 w-10 rounded-full ' + (styles[table.status]?.accent || 'bg-slate-300');
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
