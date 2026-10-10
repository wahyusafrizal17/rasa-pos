@extends('layouts.pos')
@section('content')
<div class="flex min-h-0 w-full flex-1 flex-col" x-data="posApp()">
    <div class="pos-shell">
        <section class="pos-catalog">
            <div class="flex items-center justify-between gap-3 border-b border-[#f0ebe6] px-4 py-2 text-xs" x-show="!online" x-cloak>
                <span class="font-medium text-[#d97706]">Mode offline — order akan dikirim saat koneksi kembali.</span>
            </div>
            @if ($lowStock->isNotEmpty())
                <div class="border-b border-brand-soft bg-brand-soft px-4 py-2 text-xs text-brand">
                    Stok menipis: {{ $lowStockNames }}@if ($lowStockExtra > 0) +{{ $lowStockExtra }} lagi @endif
                </div>
            @endif
            <div class="pos-toolbar">
                <div class="pos-chips">
                    <button type="button" class="pos-chip" :class="!category && 'pos-chip-active'" @click="category = null">Semua</button>
                    @foreach ($categories as $category)
                        <button type="button" class="pos-chip" :class="category == {{ $category->id }} && 'pos-chip-active'" @click="category = {{ $category->id }}">{{ $category->name }}</button>
                    @endforeach
                </div>
                <div class="relative shrink-0">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/></svg>
                    <input class="input pos-search" placeholder="Cari menu..." x-model="search">
                </div>
            </div>
            <div class="pos-grid">
                @foreach ($products as $product)
                    <article
                        class="menu-card"
                        x-show="(!category || category == {{ $product->category_id }}) && productMatch('{{ strtolower($product->name) }}')"
                        @click="addProduct({{ $product->id }}, null, null)"
                    >
                        <div class="menu-card-visual">
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="menu-card-photo" loading="lazy">
                        </div>
                        <div class="menu-card-body">
                            <h3 class="menu-card-name">{{ $product->name }}</h3>
                            <p class="menu-card-price">{{ money($product->price) }}</p>
                            @if ($product->variants->count())
                                <div class="mt-1.5 flex flex-wrap gap-1" @click.stop>
                                    @foreach ($product->variants as $variant)
                                        <button type="button" class="rounded-full bg-[#f6f1eb] px-2.5 py-1 text-[11px] font-medium text-heading" @click="addProduct({{ $product->id }}, {{ $variant->id }}, null)">{{ $variant->name }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
                @foreach ($bundles as $bundle)
                    <article class="menu-card" x-show="!category || category == {{ $bundle->product?->category_id ?? 0 }}" @click="addProduct({{ $bundle->product_id ?? $bundle->items->first()?->product_id }}, null, {{ $bundle->id }})">
                        <div class="menu-card-visual">
                            <img src="{{ $bundle->product?->imageUrl() ?? asset('images/menu/placeholder.svg') }}" alt="{{ $bundle->name }}" class="menu-card-photo" loading="lazy">
                        </div>
                        <div class="menu-card-body">
                            <h3 class="menu-card-name">{{ $bundle->name }}</h3>
                            <p class="menu-card-price">{{ money($bundle->price) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="order-panel">
            <div class="mx-5 space-y-2 border-b border-[#f0ebe6] py-4">
                <div class="flex items-center gap-2">
                    <select class="input !min-w-0 !flex-1 !rounded-full !border-[#efe8e1] !bg-[#faf7f3] !py-2 !text-xs" x-model="order_type">
                        <option value="dine_in">Dine-in</option>
                        <option value="pickup">Pickup</option>
                        <option value="online">Online</option>
                    </select>
                    <select class="input !min-w-0 !flex-1 !rounded-full !border-[#efe8e1] !bg-[#faf7f3] !py-2 !text-xs" x-model="table_id" x-show="order_type === 'dine_in'" x-cloak>
                        <option value="">Pilih meja</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @disabled($table->status !== \App\Enums\TableStatus::Available)>{{ $table->code }} · {{ $table->capacity }} pax · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex-1 space-y-2.5 overflow-y-auto px-5 py-4">
                <template x-if="!order || !parentItems().length">
                    <div class="flex h-full min-h-40 flex-col items-center justify-center rounded-2xl border border-dashed border-[#efe8e1] bg-[#faf7f3] px-4 text-center">
                        <p class="text-sm font-medium text-heading">Belum ada item</p>
                        <p class="mt-1 text-xs text-muted">Ketuk menu di kiri untuk menambah.</p>
                    </div>
                </template>
                <template x-for="item in parentItems()" :key="item.id">
                    <div class="order-item">
                        <div class="flex items-start gap-3">
                            <img :src="itemImage(item)" :alt="item.name" class="h-16 w-16 shrink-0 rounded-xl bg-white object-cover shadow-sm">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-heading" x-text="item.name"></p>
                                        <p class="mt-0.5 text-xs text-muted" x-text="formatMoney(item.unit_price)"></p>
                                        <template x-for="addon in addonsOf(item)" :key="addon.id">
                                            <p class="mt-1 text-[11px] text-muted" x-text="`+ ${addon.name} · ${formatMoney(addon.unit_price)}`"></p>
                                        </template>
                                    </div>
                                    <button class="shrink-0 text-[11px] font-medium text-brand hover:underline" @click="removeItem(item.id)">Hapus</button>
                                </div>
                                <div class="mt-3 flex items-center gap-2">
                                    <div class="order-qty">
                                        <button type="button" @click="changeQty(item, -1)">−</button>
                                        <span x-text="Number(item.quantity)"></span>
                                        <button type="button" @click="changeQty(item, 1)">+</button>
                                    </div>
                                    <input class="order-note" placeholder="Notes" x-model="item.notes" @change="updateItem(item)">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <div class="space-y-2.5 border-t border-[#f0ebe6] px-5 py-4">
                <div class="order-row text-muted">
                    <span>Subtotal</span>
                    <span class="font-medium text-heading" x-text="formatMoney(order?.subtotal || 0)"></span>
                </div>
                <div class="space-y-1.5">
                    <div class="order-row text-muted">
                        <span>Diskon</span>
                        <span class="font-medium" :class="Number(order?.discount_amount || 0) > 0 ? 'text-brand' : 'text-heading'" x-text="discountLine()"></span>
                    </div>
                    <select class="h-9 w-full rounded-full border border-[#efe8e1] bg-[#faf7f3] px-3 text-xs text-heading outline-none" x-model="discount_id" @change="applyDiscount()">
                        <option value="">Tanpa diskon</option>
                        @foreach ($discounts as $discount)
                            <option value="{{ $discount->id }}">{{ $discount->name }} · {{ $discount->valueLabel() }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-[#c2410c]" x-show="discountHint()" x-text="discountHint()" x-cloak></p>
                </div>
                <div class="order-row text-muted">
                    <span>Tax</span>
                    <span class="font-medium text-heading" x-text="formatMoney(order?.tax_amount || 0)"></span>
                </div>
                <div class="order-row text-muted">
                    <span>Service</span>
                    <span class="font-medium text-heading" x-text="formatMoney(order?.service_charge || 0)"></span>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-[#1a1211] px-4 py-3.5 text-white">
                    <span class="text-sm font-medium text-white/70">Total</span>
                    <span class="text-[22px] font-semibold tracking-tight" x-text="formatMoney(order?.grand_total || 0)"></span>
                </div>
                <p class="text-xs font-medium text-brand" x-show="notice" x-text="notice" x-cloak></p>
                <p class="text-[11px] text-muted" x-show="needsTable()" x-cloak>Pilih meja dulu untuk dine-in sebelum bayar.</p>
                <div class="grid grid-cols-[1fr_1.6fr] gap-2">
                    <div class="relative">
                        <button type="button" class="inline-flex h-12 w-full items-center justify-center rounded-2xl border border-[#efe8e1] bg-white text-sm font-medium text-heading transition hover:bg-[#faf7f3] disabled:cursor-not-allowed disabled:opacity-40" @click="hold()" :disabled="!canHold()">Hold</button>
                        <button type="button" class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand px-1.5 text-[10px] font-bold leading-none text-white" x-show="heldCount() > 0" x-text="heldCount()" x-cloak @click.stop="openHeldList()" title="Lihat order hold"></button>
                    </div>
                    <button type="button" class="pos-pay" @click="openPay()" :disabled="!canPay()">Bayar</button>
                </div>
            </div>
        </aside>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4" x-show="payOpen" x-cloak @click.self="payOpen = false">
        <div class="pay-modal">
            <div class="pay-modal-hero">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-white/45">Pembayaran</p>
                        <p class="mt-0.5 truncate text-[13px] text-white/70" x-text="order?.order_number || 'Draft'"></p>
                    </div>
                    <p class="shrink-0 text-[20px] font-semibold leading-none tracking-tight text-white" x-text="formatMoney(grandTotal())"></p>
                </div>
                <p class="mt-2 text-[12px] text-white/50" x-show="Number(order?.discount_amount || 0) > 0">
                    Diskon <span x-text="discountLine()"></span>
                </p>
            </div>
            <div class="px-6 py-5">
                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted">Metode bayar</p>
                <div class="mt-2.5 grid grid-cols-2 gap-2">
                    <template x-for="m in paymentMethods" :key="m.id">
                        <button type="button" class="pay-method" :class="method === m.id ? 'pay-method-active' : ''" @click="setMethod(m.id)">
                            <span class="block text-[13px] font-semibold" x-text="m.label"></span>
                            <span class="mt-0.5 block text-[11px] opacity-60" x-text="m.hint"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-5 space-y-3" x-show="method === 'cash'">
                    <div>
                        <label class="label">Uang diterima</label>
                        <input class="input !text-lg !font-semibold" type="text" inputmode="numeric" autocomplete="off" :value="formatRupiah(tendered)" @input="onTenderedInput($event)" placeholder="Rp 0">
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <template x-for="preset in cashPresets()" :key="preset">
                                <button type="button" class="rounded-full border border-[#e8e8e8] bg-[#fafafa] px-2.5 py-1 text-[11px] font-medium text-heading transition hover:border-heading hover:bg-white" @click="tendered = preset" x-text="preset === grandTotal() ? 'Pas' : formatMoney(preset)"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl bg-[#f6f6f6] px-4 py-3.5">
                        <span class="text-[13px] text-muted">Kembalian</span>
                        <span class="text-[22px] font-semibold tracking-tight text-heading" x-text="formatMoney(changeDue())"></span>
                    </div>
                    <p class="text-xs font-medium text-brand" x-show="cashShort()">Uang diterima masih kurang dari total.</p>
                </div>

                <p class="mt-4 text-xs text-muted" x-show="method !== 'cash' && method !== 'qris'">Nominal akan dicatat sesuai total order.</p>
                <div class="mt-5 text-center" x-show="method === 'qris' && qrisUrl" x-cloak>
                    <img :src="qrisUrl" alt="QRIS" class="mx-auto h-56 w-56 rounded-xl bg-white object-contain p-2">
                    <p class="mt-2 text-xs text-muted">Scan QRIS. Menunggu pembayaran Faspay…</p>
                </div>
                <p class="mt-3 text-xs font-medium text-brand" x-show="notice" x-text="notice"></p>

                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button type="button" class="btn-ghost !rounded-xl" @click="closePay()">Batal</button>
                    <button type="button" class="btn-brand !rounded-xl" @click="checkout()" :disabled="!canCompletePay() || !!qrisUrl" x-text="method === 'qris' ? (qrisUrl ? 'Menunggu…' : 'Tampilkan QR') : 'Selesaikan'"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="openHeld" x-cloak>
        <div class="card w-full max-w-lg p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold">Order Hold</h3>
                    <p class="mt-1 text-sm text-muted">Pilih order untuk dilanjutkan ke keranjang.</p>
                </div>
                <span class="rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand" x-text="heldCount()"></span>
            </div>
            <div class="mt-4 max-h-80 space-y-2 overflow-y-auto">
                <template x-for="held in visibleHeld()" :key="held.id">
                    <button type="button" class="flex w-full items-center justify-between gap-3 rounded-xl border border-line px-4 py-3 text-left transition hover:border-brand hover:bg-brand-soft" @click="recall(held.id)">
                        <span>
                            <span class="block text-sm font-semibold text-heading" x-text="held.order_number"></span>
                            <span class="mt-0.5 block text-xs text-muted" x-text="heldMeta(held)"></span>
                        </span>
                        <span class="text-right">
                            <span class="block text-sm font-semibold text-heading" x-text="formatMoney(held.grand_total)"></span>
                            <span class="mt-0.5 block text-xs font-medium text-brand">Lanjutkan</span>
                        </span>
                    </button>
                </template>
                <p class="py-8 text-center text-sm text-muted" x-show="heldCount() === 0">Tidak ada order yang sedang di-hold.</p>
            </div>
            <div class="mt-4 flex justify-end">
                <button class="btn-ghost" @click="openHeld = false">Tutup</button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="addonOpen" x-cloak @click.self="confirmAddons([])">
        <div class="card w-full max-w-md p-6">
            <h3 class="text-lg font-semibold">Tambahan</h3>
            <p class="mt-1 text-sm text-muted">Pilih add-on untuk item ini.</p>
            <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">
                <template x-for="addon in addons" :key="addon.id">
                    <label class="flex items-center justify-between gap-3 rounded-xl border border-line px-3 py-2 text-sm">
                        <span class="flex items-center gap-2">
                            <input type="checkbox" class="rounded border-line" :value="addon.id" x-model="selectedAddonIds">
                            <span x-text="addon.name"></span>
                        </span>
                        <span class="text-muted" x-text="formatMoney(addon.price)"></span>
                    </label>
                </template>
            </div>
            <div class="mt-6 flex gap-2">
                <button type="button" class="btn-ghost flex-1" @click="confirmAddons([])">Lewati</button>
                <button type="button" class="btn-primary flex-1" @click="confirmAddons(selectedAddonIds)">Tambah</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        order: null, search: '', category: null, order_type: 'dine_in', table_id: '',
        discount_id: '', method: 'cash', tendered: 0, payOpen: false, openHeld: false, addonOpen: false, busy: false, notice: '',
        qrisUrl: '', qrisTimer: null,
        pendingAdd: null, selectedAddonIds: [],
        productAddons: @json($productAddons),
        addons: [],
        paymentMethods: [
            { id: 'cash', label: 'Tunai', hint: 'Hitung kembalian' },
            { id: 'edc', label: 'EDC', hint: 'Mesin EDC' },
            { id: 'qris', label: 'QRIS', hint: 'Scan QR Faspay' },
            { id: 'transfer', label: 'Transfer', hint: 'Bank transfer' },
        ],
        heldOrders: @json($heldOrders),
        online: navigator.onLine,
        images: @json($productImages),
        placeholder: @json(asset('images/menu/placeholder.svg')),
        discountCatalog: @json($discountCatalog),
        init() {
            window.addEventListener('online', () => { this.online = true; this.flushQueue(); });
            window.addEventListener('offline', () => { this.online = false; });
            this.$watch('table_id', () => this.transferTable());
            this.$watch('payOpen', (open) => { if (!open) this.stopQrisPoll(); });
            this.restoreDraft();
            this.loadHeld();
        },
        visibleHeld() {
            return (this.heldOrders || []).filter((held) => held.id !== this.order?.id);
        },
        heldCount() {
            return this.visibleHeld().length;
        },
        heldMeta(held) {
            const parts = [held.order_type_label || held.order_type, held.table ? `Meja ${held.table}` : null, held.customer, `${held.items_count || 0} item`].filter(Boolean);
            return parts.join(' · ');
        },
        async openHeldList() {
            await this.loadHeld();
            this.openHeld = true;
        },
        async loadHeld() {
            try {
                this.heldOrders = await this.request('{{ route('pos.held', absolute: false) }}', { headers: await this.csrf() });
            } catch (e) {}
        },
        channel() {
            return this.order_type === 'pickup' ? 'pickup' : (this.order_type === 'online' ? 'online' : 'pos');
        },
        productMatch(name) { return !this.search || name.includes(this.search.toLowerCase()); },
        itemImage(item) { return item.image_url || this.images[item.product_id] || this.placeholder; },
        formatMoney(v) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v || 0); },
        selectedDiscount() {
            return (this.discountCatalog || []).find((d) => String(d.id) === String(this.discount_id)) || null;
        },
        discountLine() {
            const amount = Number(this.order?.discount_amount || 0);
            return amount > 0 ? '- ' + this.formatMoney(amount) : this.formatMoney(0);
        },
        discountHint() {
            const promo = this.selectedDiscount();
            if (!promo || !this.order) return '';
            const amount = Number(this.order.discount_amount || 0);
            const subtotal = Number(this.order.subtotal || 0);
            if (promo.minimum > 0 && subtotal < promo.minimum) {
                return 'Belum dapat diskon. Min. transaksi ' + this.formatMoney(promo.minimum);
            }
            if (amount > 0 && promo.maximum !== null && amount >= promo.maximum) {
                return promo.value_label + ' · dipotong maks ' + this.formatMoney(promo.maximum);
            }
            return amount > 0 ? 'Potongan ' + promo.value_label : '';
        },
        parseRupiah(value) {
            return Number(String(value ?? '').replace(/[^\d]/g, '')) || 0;
        },
        formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(this.parseRupiah(value));
        },
        onTenderedInput(event) {
            this.tendered = this.parseRupiah(event.target.value);
            event.target.value = this.formatRupiah(this.tendered);
        },
        grandTotal() { return Number(this.order?.grand_total || 0); },
        tenderedAmount() { return Number(this.tendered || 0); },
        changeDue() { return Math.max(0, this.tenderedAmount() - this.grandTotal()); },
        cashShort() { return this.method === 'cash' && this.tenderedAmount() < this.grandTotal(); },
        canPay() {
            return !!this.order?.items?.length && !this.busy && !this.needsTable();
        },
        canCompletePay() {
            if (!this.canPay()) return false;
            if (this.method === 'cash') return this.tenderedAmount() >= this.grandTotal();
            return true;
        },
        cashPresets() {
            const total = this.grandTotal();
            const steps = [50000, 100000, 150000, 200000, 300000, 500000];
            return [total, ...steps.filter((amount) => amount > total).slice(0, 3)];
        },
        openPay() {
            if (!this.canPay()) {
                this.notice = this.needsTable() ? 'Pilih meja terlebih dahulu.' : 'Tambah item dulu.';
                return;
            }
            this.notice = '';
            this.method = 'cash';
            this.tendered = this.grandTotal();
            this.payOpen = true;
        },
        setMethod(id) {
            this.stopQrisPoll();
            this.qrisUrl = '';
            this.method = id;
            this.notice = '';
            if (id !== 'cash') this.tendered = this.grandTotal();
        },
        closePay() {
            this.stopQrisPoll();
            this.qrisUrl = '';
            this.payOpen = false;
        },
        stopQrisPoll() {
            if (this.qrisTimer) {
                clearInterval(this.qrisTimer);
                this.qrisTimer = null;
            }
        },
        async finishPaid(data) {
            this.stopQrisPoll();
            this.payOpen = false;
            this.qrisUrl = '';
            if (!data.order) return;
            const qzOk = window.RasaQz?.printReceipt
                ? await window.RasaQz.printReceipt(data)
                : false;
            this.notice = qzOk
                ? 'Pembayaran berhasil, struk dicetak.'
                : 'Pembayaran berhasil. QZ Tray belum cetak — jalankan QZ Tray. Jangan print dari Chrome.';
            this.order = null;
            this.tendered = 0;
            localStorage.removeItem('pos_offline_draft');
        },
        async startQris() {
            this.busy = true;
            this.notice = '';
            try {
                const data = await this.request(`/pos/${this.order.id}/qris`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    order_type: this.order_type, table_id: this.table_id || null,
                })});
                this.qrisUrl = data.qr_url;
                this.stopQrisPoll();
                this.qrisTimer = setInterval(() => this.checkQris(), 2000);
            } catch (e) {
                this.notice = e.message || 'QRIS Faspay gagal.';
            } finally {
                this.busy = false;
            }
        },
        async checkQris() {
            if (!this.order?.id) return;
            try {
                const data = await this.request(`/pos/${this.order.id}/qris/status`, { headers: await this.csrf() });
                if (data.paid) await this.finishPaid(data);
            } catch (e) {}
        },
        persistDraft() {
            const payload = {
                order_type: this.order_type, table_id: this.table_id,
                items: (this.order?.items || []).map((item) => ({
                    product_id: item.product_id, product_variant_id: item.product_variant_id, bundle_id: item.bundle_id,
                    quantity: item.quantity, notes: item.notes,
                })),
            };
            localStorage.setItem('pos_offline_draft', JSON.stringify(payload));
        },
        restoreDraft() {
            const raw = localStorage.getItem('pos_offline_draft');
            if (!raw || this.order) return;
            try {
                const draft = JSON.parse(raw);
                this.order_type = draft.order_type || this.order_type;
                this.table_id = draft.table_id || '';
            } catch (e) {}
        },
        queue(action) {
            const items = JSON.parse(localStorage.getItem('pos_offline_queue') || '[]');
            items.push(action);
            localStorage.setItem('pos_offline_queue', JSON.stringify(items));
        },
        async flushQueue() {
            const items = JSON.parse(localStorage.getItem('pos_offline_queue') || '[]');
            if (!items.length) return;
            localStorage.removeItem('pos_offline_queue');
            for (const action of items) {
                if (action.type === 'add') {
                    await this.addProduct(action.product_id, action.variant_id, action.bundle_id, true, action.addon_ids || []);
                }
            }
        },
        async csrf() { return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' }; },
        appUrl(url) {
            if (!url.startsWith('/') || url.startsWith('//')) return url;
            const fromLaravel = @json(rtrim((string) request()->getBasePath(), '/'));
            const path = window.location.pathname;
            const posAt = path.indexOf('/pos');
            const fromWindow = posAt > 0 ? path.slice(0, posAt) : '';
            return (fromLaravel || fromWindow) + url;
        },
        async request(url, options) {
            if (!this.online) throw new Error('offline');
            const res = await fetch(this.appUrl(url), options);
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Request gagal');
            }
            return res.json();
        },
        async ensureOrder() {
            if (this.order) return this.order;
            this.order = await this.request('{{ route('pos.draft', absolute: false) }}', { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                order_type: this.order_type, table_id: this.table_id || null, channel: this.channel()
            })});
            return this.order;
        },
        parentItems() {
            return (this.order?.items || []).filter((item) => !item.parent_id);
        },
        canHold() {
            return !!this.order && this.parentItems().length > 0;
        },
        addonsOf(item) {
            return (this.order?.items || []).filter((addon) => addon.parent_id === item.id);
        },
        addonsFor(productId) {
            return this.productAddons[productId] || this.productAddons[String(productId)] || [];
        },
        async addProduct(productId, variantId, bundleId, fromQueue = false, addonIds = null) {
            const extras = this.addonsFor(productId);
            if (!fromQueue && !bundleId && extras.length && addonIds === null) {
                this.pendingAdd = { productId, variantId, bundleId };
                this.selectedAddonIds = [];
                this.addons = extras;
                this.addonOpen = true;
                return;
            }
            try {
                await this.ensureOrder();
                this.order = await this.request(`/pos/${this.order.id}/items`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    product_id: productId, product_variant_id: variantId, bundle_id: bundleId, quantity: 1, addon_ids: addonIds || []
                })});
                this.persistDraft();
            } catch (e) {
                if (!fromQueue) this.queue({ type: 'add', product_id: productId, variant_id: variantId, bundle_id: bundleId, addon_ids: addonIds || [] });
            }
        },
        confirmAddons(ids) {
            const pending = this.pendingAdd;
            this.addonOpen = false;
            this.pendingAdd = null;
            if (!pending) return;
            this.addProduct(pending.productId, pending.variantId, pending.bundleId, false, (ids || []).map(Number));
        },
        async changeQty(item, delta) {
            const qty = Number(item.quantity) + delta;
            if (qty <= 0) return this.removeItem(item.id);
            item.quantity = qty;
            await this.updateItem(item);
        },
        async updateItem(item) {
            this.order = await this.request(`/pos/${this.order.id}/items/${item.id}`, { method: 'PUT', headers: await this.csrf(), body: JSON.stringify({ quantity: item.quantity, notes: item.notes })});
            this.persistDraft();
        },
        async removeItem(id) {
            this.order = await this.request(`/pos/${this.order.id}/items/${id}`, { method: 'DELETE', headers: await this.csrf() });
            this.persistDraft();
        },
        async applyDiscount() {
            if (!this.order) return;
            this.order = await this.request(`/pos/${this.order.id}/discount`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({ discount_id: this.discount_id || null })});
        },
        async transferTable() {
            if (!this.order || this.order_type !== 'dine_in' || !this.table_id) return;
            if (String(this.order.table_id || '') === String(this.table_id)) return;
            this.order = await this.request(`/pos/${this.order.id}/transfer`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({ table_id: this.table_id })});
        },
        async hold() {
            if (!this.canHold()) return;
            const held = await this.request(`/pos/${this.order.id}/hold`, { method: 'POST', headers: await this.csrf() });
            this.heldOrders = [held, ...this.heldOrders.filter((item) => item.id !== held.id)];
            this.order = null;
            this.discount_id = '';
            localStorage.removeItem('pos_offline_draft');
        },
        async recall(id) {
            if (this.order && this.order.id !== id && this.order.items?.length) {
                await this.hold();
            }
            this.order = await this.request(`/pos/${id}/recall`, { headers: await this.csrf() });
            this.table_id = this.order.table_id || '';
            this.order_type = this.order.order_type || this.order_type;
            this.discount_id = this.order.discount_id || '';
            this.heldOrders = this.heldOrders.filter((item) => item.id !== id);
            this.openHeld = false;
        },
        alreadySent() {
            return ['new', 'processing', 'preparing', 'ready', 'completed'].includes(this.order?.status);
        },
        needsTable() {
            return this.order_type === 'dine_in' && !this.table_id && !!this.order?.items?.length && !this.alreadySent();
        },
        async checkout() {
            if (!this.canCompletePay()) {
                this.notice = this.cashShort() ? 'Uang diterima masih kurang dari total.' : 'Tidak bisa menyelesaikan pembayaran.';
                return;
            }
            if (this.method === 'qris') {
                await this.startQris();
                return;
            }
            this.busy = true;
            this.notice = '';
            try {
                const paid = this.method === 'cash' ? this.tenderedAmount() : this.grandTotal();
                const data = await this.request(`/pos/${this.order.id}/checkout`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    method: this.method, amount: this.grandTotal(), tendered: paid,
                    order_type: this.order_type, table_id: this.table_id || null,
                })});
                await this.finishPaid(data);
            } catch (e) {
                this.notice = e.message || 'Pembayaran gagal.';
            } finally {
                this.busy = false;
            }
        }
    }
}
</script>
@endpush
