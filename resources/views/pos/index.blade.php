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
                @php
                    $categoryCounts = $products->countBy('category_id')->all();
                    foreach ($bundles as $bundle) {
                        $bundleCategoryId = $bundle->product?->category_id;
                        if ($bundleCategoryId) {
                            $categoryCounts[$bundleCategoryId] = ($categoryCounts[$bundleCategoryId] ?? 0) + 1;
                        }
                    }
                @endphp
                <div class="pos-chips">
                    <button type="button" class="pos-chip" :class="!category && 'pos-chip-active'" @click="category = null">Semua <span class="pos-chip-count">{{ array_sum($categoryCounts) }}</span></button>
                    @foreach ($categories as $category)
                        <button type="button" class="pos-chip" :class="category == {{ $category->id }} && 'pos-chip-active'" @click="category = {{ $category->id }}">{{ $category->name }} <span class="pos-chip-count">{{ $categoryCounts[$category->id] ?? 0 }}</span></button>
                    @endforeach
                </div>
                <div class="relative shrink-0">
                    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/></svg>
                    <input class="input pos-search" placeholder="Cari menu..." x-model="search">
                </div>
            </div>
            <div class="pos-grid">
                @foreach ($products as $product)
                    @php
                        $stockQty = (float) ($product->inventories->first()?->quantity ?? 0);
                        $outOfStock = $product->is_stockable && $stockQty <= 0;
                        $notSellable = ! $product->is_sellable;
                    @endphp
                    <article
                        class="menu-card"
                        @if ($outOfStock || $notSellable) style="opacity:.55;cursor:not-allowed" @endif
                        x-show="(!category || category == {{ $product->category_id }}) && productMatch('{{ strtolower($product->name) }}')"
                        @if ($notSellable)
                            @click="notice = 'Produk ini belum bisa dijual. Aktifkan penjualannya di Zoho lalu sinkronkan.'"
                        @else
                            @click="addProduct({{ $product->id }}, null, null, false, null, {{ $outOfStock ? '0' : 'null' }})"
                        @endif
                    >
                        <div class="menu-card-body">
                            <div class="menu-card-head">
                                <h3 class="menu-card-name">{{ $product->name }}</h3>
                                @if ($product->is_stockable)
                                    <span class="menu-stock-badge {{ $outOfStock ? 'is-out' : '' }}">{{ qty_label($stockQty, $product->unit?->code ?? '') }}</span>
                                @endif
                            </div>
                            <p class="menu-card-price">{{ money($product->price) }}</p>
                            @if ($notSellable)
                                <span class="menu-stock-badge is-out mt-1 inline-block">Belum bisa dijual</span>
                            @endif
                            @if ($product->has_optional || $product->has_expresso)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if ($product->has_optional)
                                        <span class="menu-choice-badge">Optional</span>
                                    @endif
                                    @if ($product->has_expresso)
                                        <span class="menu-choice-badge">Expresso</span>
                                    @endif
                                </div>
                            @endif
                            @if ($product->variants->count() && ! $notSellable)
                                <div class="mt-1.5 flex flex-wrap gap-1" @click.stop>
                                    @foreach ($product->variants as $variant)
                                        <button type="button" class="rounded-full bg-[#f6f1eb] px-2.5 py-1 text-[11px] font-medium text-heading" @click="addProduct({{ $product->id }}, {{ $variant->id }}, null, false, null, {{ $outOfStock ? '0' : 'null' }})">{{ $variant->name }}</button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
                @foreach ($bundles as $bundle)
                    @php
                        $bundleStock = (float) ($bundle->product?->inventories->first()?->quantity ?? 0);
                        $bundleOut = (bool) ($bundle->product?->is_stockable && $bundleStock <= 0);
                    @endphp
                    <article class="menu-card" @if ($bundleOut) style="opacity:.55;cursor:not-allowed" @endif x-show="!category || category == {{ $bundle->product?->category_id ?? 0 }}" @click="addProduct({{ $bundle->product_id ?? $bundle->items->first()?->product_id }}, null, {{ $bundle->id }}, false, null, {{ $bundleOut ? '0' : 'null' }})">
                        <div class="menu-card-body">
                            <div class="menu-card-head">
                                <h3 class="menu-card-name">{{ $bundle->name }}</h3>
                                @if ($bundle->product?->is_stockable)
                                    <span class="menu-stock-badge {{ $bundleOut ? 'is-out' : '' }}">{{ qty_label($bundleStock, $bundle->product->unit?->code ?? '') }}</span>
                                @endif
                            </div>
                            <p class="menu-card-price">{{ money($bundle->price) }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="order-panel">
            <div class="mx-5 space-y-2 border-b border-[#f0ebe6] py-4">
                <div class="flex items-center gap-2">
                    <select class="input !min-w-0 !flex-1 !rounded-full !border-[#efe8e1] !bg-[#faf7f3] !py-2 !text-xs" x-model="order_type" @change="if (order_type !== 'dine_in') table_id = ''; if (!usesGuest()) guest_name = ''; saveGuest()">
                        <option value="dine_in">Dine In</option>
                        <option value="take_away">Take Away</option>
                        <option value="pickup">Pickup</option>
                        <option value="online">Online</option>
                    </select>
                    <input x-show="usesGuest()" x-cloak x-model="guest_name" @change="saveGuest()" class="input !min-w-0 !flex-1 !rounded-full !border-[#efe8e1] !bg-[#faf7f3] !py-2 !text-xs" placeholder="Nama customer" maxlength="80">
                    <select class="input !min-w-0 !flex-1 !rounded-full !border-[#efe8e1] !bg-[#faf7f3] !py-2 !text-xs" x-model="table_id" x-show="order_type === 'dine_in'" x-cloak>
                        <option value="">Pilih meja</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table->id }}" @disabled($table->status !== \App\Enums\TableStatus::Available)>{{ $table->code }} · {{ $table->capacity }} pax · {{ $table->status->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex-1 space-y-2 overflow-y-auto px-5 py-4">
                <template x-if="!order || !parentItems().length">
                    <div class="flex h-full min-h-40 flex-col items-center justify-center rounded-2xl border border-dashed border-[#efe8e1] bg-[#faf7f3] px-4 text-center">
                        <p class="text-sm font-medium text-heading">Belum ada item</p>
                        <p class="mt-1 text-xs text-muted">Ketuk menu di kiri untuk menambah.</p>
                    </div>
                </template>
                <template x-for="item in parentItems()" :key="item.id">
                    <div class="order-item">
                        <div class="flex items-start gap-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="min-w-0 text-sm font-semibold text-heading" x-text="itemBaseName(item)"></p>
                                    <button class="shrink-0 text-[11px] font-medium text-brand hover:underline" @click="removeItem(item.id)">Hapus</button>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs font-medium text-heading" x-text="formatMoney(item.unit_price)"></span>
                                    <template x-for="choice in itemChoices(item)" :key="choice.key">
                                        <span class="order-choice">
                                            <span class="text-muted" x-show="choice.group" x-text="choice.group"></span>
                                            <span class="font-semibold text-heading" x-text="choice.label"></span>
                                        </span>
                                    </template>
                                </div>
                                <template x-for="addon in addonsOf(item)" :key="addon.id">
                                    <p class="mt-1 text-[11px] text-muted" x-text="`+ ${addon.name} · ${formatMoney(addon.unit_price)}`"></p>
                                </template>
                                <div class="mt-2 flex items-center gap-2">
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
                        <button type="button" class="inline-flex h-12 w-full items-center justify-center rounded-2xl border border-[#efe8e1] bg-white text-sm font-medium text-heading transition hover:bg-[#faf7f3] disabled:cursor-not-allowed disabled:opacity-40" @click="hold()" :disabled="!canHold() || busy">Hold</button>
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
                    <p class="shrink-0 text-[20px] font-semibold leading-none tracking-tight text-white" x-text="formatMoney(method === 'compliment' ? 0 : grandTotal())"></p>
                </div>
                <p class="mt-2 text-[12px] text-white/50" x-show="method === 'compliment'" x-cloak>
                    Compliment · nilai menu <span x-text="formatMoney(grandTotal())"></span> tetap tercatat
                </p>
                <p class="mt-2 text-[12px] text-white/50" x-show="method !== 'compliment' && Number(order?.discount_amount || 0) > 0">
                    Diskon <span x-text="discountLine()"></span>
                </p>
            </div>
            <div class="px-6 py-5">
                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted">Metode bayar</p>
                <div class="mt-2.5 grid grid-cols-3 gap-2">
                    <template x-for="m in paymentMethods" :key="m.id">
                        <button type="button" class="pay-method" :class="method === m.id ? 'pay-method-active' : ''" @click="setMethod(m.id)">
                            <span class="block text-[13px] font-semibold" x-text="m.label"></span>
                            <span class="mt-0.5 block text-[11px] opacity-60" x-text="m.hint"></span>
                        </button>
                    </template>
                </div>

                <p class="mt-4 text-xs text-muted" x-show="method === 'edc'">Nominal akan dicatat sesuai total order.</p>
                <div class="mt-4 space-y-2" x-show="method === 'compliment'" x-cloak>
                    <input class="input" placeholder="Nama tamu (wajib)" maxlength="80" x-model="compliment_guest">
                    <input class="input" placeholder="Alasan compliment (wajib), mis. Tamu VIP dari owner" maxlength="160" x-model="compliment_reason">
                    <p class="text-xs text-muted">Item tetap tercatat dan stok terpotong, total ditagih Rp 0.</p>
                </div>
                <p class="mt-3 text-xs font-medium text-brand" x-show="notice" x-text="notice"></p>

                <div class="mt-6 grid grid-cols-2 gap-2">
                    <button type="button" class="btn-ghost !rounded-xl" @click="closePay()">Batal</button>
                    <button type="button" class="btn-brand !rounded-xl" @click="checkout()" :disabled="!canCompletePay() || busy" x-text="busy ? 'Memproses…' : (method === 'qris' ? 'Tampilkan QR' : 'Selesaikan')"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/55 p-4" x-show="qrisOpen" x-cloak @click.self="closeQris()">
        <div class="pay-modal">
            <div class="pay-modal-hero">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-white/45">QRIS</p>
                        <p class="mt-0.5 truncate text-[13px] text-white/70" x-text="order?.order_number || 'Draft'"></p>
                    </div>
                    <p class="shrink-0 text-[20px] font-semibold leading-none tracking-tight text-white" x-text="formatMoney(grandTotal())"></p>
                </div>
            </div>
            <div class="px-6 py-5 text-center">
                <img :src="qrisUrl" alt="QRIS" class="mx-auto h-56 w-56 rounded-xl bg-[#f6f6f6] object-contain p-2" x-show="qrisUrl">
                <div class="mt-4 space-y-2 rounded-2xl bg-[#f6f6f6] px-4 py-3.5 text-left text-[13px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted">Nominal</span>
                        <span class="font-semibold text-heading" x-text="formatMoney(grandTotal())"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted">No. order</span>
                        <span class="font-medium text-heading" x-text="order?.order_number || '—'"></span>
                    </div>
                    <div class="flex items-center justify-between gap-3" x-show="qrisRef">
                        <span class="text-muted">Referensi</span>
                        <span class="font-medium text-heading" x-text="qrisRef"></span>
                    </div>
                </div>
                <p class="mt-3 inline-flex items-center gap-2 text-xs text-muted">
                    <svg class="h-3.5 w-3.5 animate-spin text-brand" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
                    </svg>
                    Scan QRIS. Menunggu pembayaran…
                </p>
                <button type="button" class="btn-ghost mt-5 w-full !rounded-xl" @click="closeQris()">Batal</button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4" x-show="receiptUrl" x-cloak @keydown.escape.window="receiptUrl = ''" @click.self="receiptUrl = ''">
        <div class="pay-modal flex flex-col">
            <div class="flex items-center justify-between px-5 py-3">
                <p class="text-sm font-semibold text-heading">Struk</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn-add !py-2" @click="printReceipt()">Cetak</button>
                    <button type="button" class="btn-ghost !py-2" @click="receiptUrl = ''">Tutup</button>
                </div>
            </div>
            <iframe class="w-full border-0 bg-white" style="height:75vh" :src="receiptUrl || 'about:blank'" title="Struk"></iframe>
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

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" x-show="choiceOpen" x-cloak @click.self="cancelChoice()">
        <div class="card w-full max-w-md p-6">
            <h3 class="text-lg font-semibold" x-text="pendingChoice?.name"></h3>
            <p class="mt-1 text-sm text-muted" x-text="formatMoney(pendingChoice?.price)"></p>
            <div class="mt-5" x-show="pendingChoice?.optional">
                <p class="text-sm font-semibold text-heading">Optional <span class="ml-1 text-xs font-normal text-muted">wajib, pilih satu</span></p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <template x-for="(option, key) in menuChoices.optional" :key="key">
                        <button type="button" class="rounded-xl border px-3 py-3 text-sm font-semibold" :class="choiceOptional === key ? 'border-brand bg-brand-soft text-heading' : 'border-line text-heading'" @click="choiceOptional = key">
                            <span x-text="option.label"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="mt-5" x-show="pendingChoice?.expresso">
                <p class="text-sm font-semibold text-heading">Expresso <span class="ml-1 text-xs font-normal text-muted">wajib, pilih satu</span></p>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <template x-for="(option, key) in menuChoices.expresso" :key="key">
                        <button type="button" class="rounded-xl border px-3 py-3 text-left text-sm font-semibold" :class="choiceExpresso === key ? 'border-brand bg-brand-soft text-heading' : 'border-line text-heading'" @click="choiceExpresso = key">
                            <span class="block" x-text="option.label"></span>
                            <span class="mt-0.5 block text-xs font-medium text-muted" x-show="option.price > 0" x-text="'+ ' + formatMoney(option.price)"></span>
                        </button>
                    </template>
                </div>
            </div>
            <div class="mt-6 flex gap-2">
                <button type="button" class="btn-ghost flex-1" @click="cancelChoice()">Batal</button>
                <button type="button" class="btn-primary flex-1" @click="confirmChoice()" :disabled="!canConfirmChoice() || busy" x-text="choiceButtonLabel()"></button>
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
                <button type="button" class="btn-ghost flex-1" @click="confirmAddons([])" :disabled="busy">Lewati</button>
                <button type="button" class="btn-primary flex-1" @click="confirmAddons(selectedAddonIds)" :disabled="busy">Tambah</button>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/35" x-show="busy" x-cloak>
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-[0_16px_40px_rgba(0,0,0,0.18)]">
            <svg class="h-5 w-5 animate-spin text-brand" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"></path>
            </svg>
            <p class="text-sm font-medium text-heading">Memproses…</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        order: null, search: '', category: null, order_type: 'dine_in', table_id: '', guest_name: '',
        discount_id: '', method: 'edc', tendered: 0, payOpen: false, openHeld: false, addonOpen: false, choiceOpen: false, busy: false, busyCount: 0, notice: '', receiptUrl: '',
        qrisUrl: '', qrisRef: '', qrisOpen: false, qrisTimer: null,
        pendingAdd: null, selectedAddonIds: [], pendingChoice: null, choiceOptional: '', choiceExpresso: '',
        productAddons: @json($productAddons),
        productChoices: @json($productChoices),
        menuChoices: @json($menuChoices),
        addons: [],
        paymentMethods: [
            { id: 'edc', label: 'EDC', hint: 'Mesin EDC' },
            { id: 'qris', label: 'QRIS', hint: 'Scan QR' },
            { id: 'compliment', label: 'Compliment', hint: 'Gratis, tetap dicatat' },
        ],
        compliment_guest: '', compliment_reason: '',
        heldOrders: @json($heldOrders),
        online: navigator.onLine,
        discountCatalog: @json($discountCatalog),
        transferAccount: @json($transferAccount),
        init() {
            window.addEventListener('online', () => { this.online = true; this.flushQueue(); });
            window.addEventListener('offline', () => { this.online = false; });
            this.$watch('table_id', () => this.transferTable());
            this.$watch('payOpen', (open) => { if (!open && !this.qrisOpen) this.stopQrisPoll(); });
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
            await this.loadHeld(false);
            this.openHeld = true;
        },
        async loadHeld(silent = true) {
            try {
                this.heldOrders = await this.request('{{ route('pos.held', absolute: false) }}', { headers: await this.csrf(), silent });
            } catch (e) {}
        },
        channel() {
            return this.order_type === 'pickup' ? 'pickup' : (this.order_type === 'online' ? 'online' : 'pos');
        },
        usesGuest() {
            return this.order_type === 'dine_in' || this.order_type === 'take_away';
        },
        guestPayload() {
            return this.usesGuest() ? this.guest_name : '';
        },
        productMatch(name) { return !this.search || name.includes(this.search.toLowerCase()); },
        itemBaseName(item) {
            return String(item.name || '').replace(/ \([^)]+\)$/, '');
        },
        itemChoices(item) {
            const match = String(item.name || '').match(/ \(([^)]+)\)$/);
            if (!match) return [];
            const parts = match[1].split(', ').map((part) => part.trim()).filter(Boolean);
            const used = new Set();
            const rows = [];
            [['Optional', this.menuChoices?.optional], ['Expresso', this.menuChoices?.expresso]].forEach(([group, options]) => {
                const labels = new Set(Object.values(options || {}).map((option) => option.label));
                const hit = parts.find((part) => labels.has(part));
                if (!hit) return;
                used.add(hit);
                rows.push({ key: group, group, label: hit });
            });
            parts.filter((part) => !used.has(part)).forEach((part) => rows.unshift({ key: 'v-' + part, group: '', label: part }));
            return rows;
        },
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
        canPay() {
            return !!this.order?.items?.length && !this.busy && !this.needsTable();
        },
        canCompletePay() {
            if (this.method === 'compliment' && (!this.compliment_guest.trim() || !this.compliment_reason.trim())) return false;
            return this.canPay();
        },
        openPay() {
            if (!this.canPay()) {
                this.notice = this.needsTable() ? 'Pilih meja terlebih dahulu.' : 'Tambah item dulu.';
                return;
            }
            this.notice = '';
            this.method = 'edc';
            this.tendered = this.grandTotal();
            this.compliment_guest = this.guest_name || '';
            this.compliment_reason = '';
            this.payOpen = true;
        },
        setMethod(id) {
            this.stopQrisPoll();
            this.qrisUrl = '';
            this.qrisRef = '';
            this.qrisOpen = false;
            this.method = id;
            this.notice = '';
            if (id !== 'cash') this.tendered = this.grandTotal();
        },
        closePay() {
            this.closeQris(false);
            this.payOpen = false;
        },
        closeQris(backToPay = true) {
            this.stopQrisPoll();
            this.qrisUrl = '';
            this.qrisRef = '';
            this.qrisOpen = false;
            if (backToPay) this.payOpen = true;
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
            this.qrisOpen = false;
            this.qrisUrl = '';
            this.qrisRef = '';
            const orderId = data.order?.id;
            if (!orderId) return;
            this.receiptUrl = `/pos/${orderId}/receipt`;
            this.notice = 'Pembayaran berhasil.';
            this.order = null;
            this.guest_name = '';
            this.tendered = 0;
            localStorage.removeItem('pos_offline_draft');
        },
        async printReceipt() {
            const frame = document.querySelector('iframe[title="Struk"]');
            const escpos = frame?.contentWindow?.__receiptEscpos;
            try {
                if (escpos && window.RasaPrint?.printRaw) {
                    await window.RasaPrint.printRaw('', escpos, true);
                    this.notice = 'Struk terkirim ke printer Bluetooth.';
                    return;
                }
            } catch (e) {
                this.notice = e?.message || 'Gagal cetak Bluetooth.';
                return;
            }
            frame?.contentWindow?.focus();
            frame?.contentWindow?.print();
        },
        async startQris() {
            this.notice = '';
            try {
                await this.saveGuest();
                const data = await this.request(`/pos/${this.order.id}/qris`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    order_type: this.order_type, table_id: this.table_id || null, guest_name: this.guestPayload(),
                })});
                this.qrisUrl = data.qr_url;
                this.qrisRef = data.reference_no || data.faspay_reference_no || '';
                this.qrisOpen = true;
                this.payOpen = false;
                this.stopQrisPoll();
                this.qrisTimer = setInterval(() => this.checkQris(), 2000);
            } catch (e) {
                this.notice = e.message || 'QRIS gagal.';
            }
        },
        async checkQris() {
            if (!this.order?.id) return;
            try {
                const data = await this.request(`/pos/${this.order.id}/qris/status`, { headers: await this.csrf(), silent: true });
                if (data.paid) await this.finishPaid(data);
            } catch (e) {}
        },
        persistDraft() {
            const payload = {
                order_type: this.order_type, table_id: this.table_id, guest_name: this.guest_name,
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
                this.guest_name = draft.guest_name || '';
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
                    await this.addProduct(action.product_id, action.variant_id, action.bundle_id, true, action.addon_ids || [], null, action.modifiers || null);
                }
            }
        },
        async saveGuest() {
            if (!this.order) return;
            this.order = await this.request(`/pos/${this.order.id}`, { method: 'PATCH', headers: await this.csrf(), body: JSON.stringify({
                order_type: this.order_type,
                guest_name: this.guestPayload(),
            })});
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
        async request(url, options = {}) {
            if (!this.online) throw new Error('offline');
            const silent = options.silent === true;
            if (!silent) {
                this.busyCount += 1;
                this.busy = true;
            }
            try {
                const res = await fetch(this.appUrl(url), options);
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const error = new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'Request gagal');
                    error.status = res.status;
                    throw error;
                }
                return res.json();
            } finally {
                if (!silent) {
                    this.busyCount = Math.max(0, this.busyCount - 1);
                    this.busy = this.busyCount > 0;
                }
            }
        },
        async ensureOrder() {
            if (this.order) return this.order;
            this.order = await this.request('{{ route('pos.draft', absolute: false) }}', { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                order_type: this.order_type, table_id: this.table_id || null, guest_name: this.guestPayload(), channel: this.channel()
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
        choiceOf(productId) {
            return this.productChoices[productId] || this.productChoices[String(productId)] || null;
        },
        canConfirmChoice() {
            if (!this.pendingChoice) return false;
            if (this.pendingChoice.optional && !this.choiceOptional) return false;
            if (this.pendingChoice.expresso && !this.choiceExpresso) return false;
            return true;
        },
        choiceButtonLabel() {
            const parts = [];
            if (this.pendingChoice?.optional) parts.push('Optional');
            if (this.pendingChoice?.expresso) parts.push('Expresso');
            return parts.length ? 'Pilih ' + parts.join(' & ') : 'Pilih';
        },
        cancelChoice() {
            this.choiceOpen = false;
            this.pendingAdd = null;
            this.pendingChoice = null;
        },
        confirmChoice() {
            if (!this.canConfirmChoice()) return;
            const pending = this.pendingAdd;
            const modifiers = {};
            if (this.pendingChoice.optional) modifiers.optional = this.choiceOptional;
            if (this.pendingChoice.expresso) modifiers.expresso = this.choiceExpresso;
            this.choiceOpen = false;
            this.pendingAdd = null;
            this.pendingChoice = null;
            if (!pending) return;
            this.addProduct(pending.productId, pending.variantId, pending.bundleId, false, null, pending.stock, modifiers);
        },
        async addProduct(productId, variantId, bundleId, fromQueue = false, addonIds = null, stock = null, modifiers = null) {
            if (stock !== null && Number(stock) <= 0) {
                this.notice = 'Stok habis.';
                return;
            }
            if (!fromQueue && !bundleId && modifiers === null && this.choiceOf(productId)) {
                this.pendingAdd = { productId, variantId, bundleId, stock };
                this.pendingChoice = this.choiceOf(productId);
                this.choiceOptional = '';
                this.choiceExpresso = '';
                this.choiceOpen = true;
                return;
            }
            const extras = this.addonsFor(productId);
            if (!fromQueue && !bundleId && extras.length && addonIds === null) {
                this.pendingAdd = { productId, variantId, bundleId, stock, modifiers };
                this.selectedAddonIds = [];
                this.addons = extras;
                this.addonOpen = true;
                return;
            }
            try {
                await this.ensureOrder();
                this.order = await this.request(`/pos/${this.order.id}/items`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    product_id: productId, product_variant_id: variantId, bundle_id: bundleId, quantity: 1, addon_ids: addonIds || [],
                    ...(modifiers ? { modifiers } : {}),
                })});
                this.persistDraft();
            } catch (e) {
                if (e.status) {
                    this.notice = e.message || 'Tidak bisa menambah item.';
                    return;
                }
                if (!fromQueue) this.queue({ type: 'add', product_id: productId, variant_id: variantId, bundle_id: bundleId, addon_ids: addonIds || [], modifiers });
            }
        },
        confirmAddons(ids) {
            const pending = this.pendingAdd;
            this.addonOpen = false;
            this.pendingAdd = null;
            if (!pending) return;
            this.addProduct(pending.productId, pending.variantId, pending.bundleId, false, (ids || []).map(Number), pending.stock, pending.modifiers || null);
        },
        async changeQty(item, delta) {
            const qty = Number(item.quantity) + delta;
            if (qty <= 0) return this.removeItem(item.id);
            const previous = item.quantity;
            item.quantity = qty;
            try {
                await this.updateItem(item);
            } catch (e) {
                item.quantity = previous;
                this.notice = e.message || 'Tidak bisa menambah item.';
            }
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
            this.guest_name = '';
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
            this.guest_name = this.order.guest_name || '';
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
                this.notice = this.method === 'compliment'
                    ? 'Isi nama tamu dan alasan compliment.'
                    : (this.cashShort() ? 'Uang diterima masih kurang dari total.' : 'Tidak bisa menyelesaikan pembayaran.');
                return;
            }
            if (this.method === 'qris') {
                await this.startQris();
                return;
            }
            this.notice = '';
            try {
                await this.saveGuest();
                const paid = this.method === 'cash' ? this.tenderedAmount() : this.grandTotal();
                const data = await this.request(`/pos/${this.order.id}/checkout`, { method: 'POST', headers: await this.csrf(), body: JSON.stringify({
                    method: this.method, amount: this.grandTotal(), tendered: paid,
                    order_type: this.order_type, table_id: this.table_id || null, guest_name: this.guestPayload(),
                    ...(this.method === 'compliment' ? { compliment_guest: this.compliment_guest.trim(), compliment_reason: this.compliment_reason.trim() } : {}),
                })});
                await this.finishPaid(data);
            } catch (e) {
                this.notice = e.message || 'Pembayaran gagal.';
            }
        }
    }
}
</script>
@endpush
