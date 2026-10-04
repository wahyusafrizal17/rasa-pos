<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink" x-data="{ sidebar: false, collapsed: localStorage.getItem('sidebar') === '1' }" x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? '1' : '0'))">
    <div class="flex min-h-screen">
        <div class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" x-show="sidebar" x-cloak @click="sidebar = false"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-[260px] -translate-x-full flex-col bg-sidebar text-white transition-all duration-200 lg:static lg:translate-x-0"
               :class="{
                    'translate-x-0': sidebar,
                    'lg:w-[78px]': collapsed,
                    'lg:w-[260px]': !collapsed
               }">
            <div class="flex h-[92px] items-center justify-between gap-2 px-3">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 flex-1 items-center">
                    <img src="{{ asset('images/logo/rasa-logo.png') }}" alt="Rasa POS" class="h-[76px] w-auto max-w-full object-contain object-left" x-show="!collapsed">
                    <img src="{{ asset('images/logo/rasa-logo.png') }}" alt="Rasa POS" class="h-10 w-10 object-contain" x-show="collapsed" x-cloak>
                </a>
                <button class="hidden rounded-md p-1 text-[#8a8d9f] hover:bg-white/5 lg:inline-flex" @click="collapsed = !collapsed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h10M4 18h16"/></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-8">
                @php
                    $nav = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'perm' => 'dashboard.view', 'icon' => 'home'],
                        ['label' => 'Operasional', 'icon' => 'pos', 'children' => [
                            ['label' => 'POS', 'route' => 'pos.index', 'active' => 'pos.*', 'perm' => 'pos.access', 'icon' => 'pos'],
                            ['label' => 'Sales Orders', 'route' => 'orders.index', 'active' => 'orders.*', 'perm' => 'orders.view', 'icon' => 'orders'],
                            ['label' => 'Tables', 'route' => 'tables.index', 'active' => 'tables.*', 'perm' => 'tables.view', 'icon' => 'tables'],
                        ]],
                        ['label' => 'Marketing', 'icon' => 'customers', 'children' => [
                            ['label' => 'Customers', 'route' => 'customers.index', 'active' => 'customers.*', 'perm' => 'customers.view', 'icon' => 'customers'],
                            ['label' => 'Discounts', 'route' => 'marketing.discounts', 'perm' => 'marketing.view', 'icon' => 'tag'],
                        ]],
                        ['label' => 'Katalog', 'icon' => 'products', 'children' => [
                            ['label' => 'Products', 'route' => 'products.index', 'active' => 'products.*', 'perm' => 'products.view', 'icon' => 'products'],
                            ['label' => 'Categories', 'route' => 'categories.index', 'active' => 'categories.*', 'perm' => 'products.view', 'icon' => 'folder'],
                            ['label' => 'Bundles', 'route' => 'marketing.bundles', 'perm' => 'marketing.view', 'icon' => 'gift'],
                        ]],
                        ['label' => 'Inventori', 'icon' => 'inventory', 'children' => [
                            ['label' => 'Stock', 'route' => 'inventory.index', 'perm' => 'inventory.view', 'icon' => 'inventory'],
                            ['label' => 'Movements', 'route' => 'inventory.movements', 'perm' => 'inventory.view', 'icon' => 'arrows'],
                            ['label' => 'Transfers', 'route' => 'transfers.index', 'active' => 'transfers.*', 'perm' => 'inventory.view', 'icon' => 'swap'],
                            ['label' => 'Stock Opname', 'route' => 'opnames.index', 'active' => 'opnames.*', 'perm' => 'inventory.view', 'icon' => 'clipboard-check'],
                            ['label' => 'Waste', 'route' => 'wastes.index', 'active' => 'wastes.*', 'perm' => 'inventory.view', 'icon' => 'trash'],
                        ]],
                        ['label' => 'Produksi', 'icon' => 'production', 'children' => [
                            ['label' => 'BOM', 'route' => 'boms.index', 'active' => 'boms.*', 'perm' => 'production.view', 'icon' => 'list'],
                            ['label' => 'Production Orders', 'route' => 'production.index', 'active' => 'production.*', 'perm' => 'production.view', 'icon' => 'clipboard'],
                            ['label' => 'Batches', 'route' => 'batches.index', 'active' => 'batches.*', 'perm' => 'production.view', 'icon' => 'layers'],
                        ]],
                        ['label' => 'Laporan', 'icon' => 'reports', 'children' => [
                            ['label' => 'Sales', 'route' => 'reports.sales', 'perm' => 'reports.view', 'icon' => 'chart'],
                            ['label' => 'Products', 'route' => 'reports.products', 'perm' => 'reports.view', 'icon' => 'products'],
                            ['label' => 'Categories', 'route' => 'reports.categories', 'perm' => 'reports.view', 'icon' => 'folder'],
                            ['label' => 'Promo', 'route' => 'reports.promo', 'perm' => 'reports.view', 'icon' => 'megaphone'],
                        ]],
                        ['label' => 'Pengaturan', 'icon' => 'settings', 'children' => [
                            ['label' => 'General', 'route' => 'settings.index', 'perm' => 'settings.manage', 'icon' => 'settings'],
                            ['label' => 'Outlets', 'route' => 'outlets.index', 'active' => 'outlets.*', 'perm' => 'outlets.view', 'icon' => 'building'],
                            ['label' => 'Users', 'route' => 'users.index', 'active' => 'users.*', 'perm' => 'users.view', 'icon' => 'user'],
                            ['label' => 'Printers', 'route' => 'printers.index', 'active' => 'printers.*', 'perm' => 'printers.view', 'icon' => 'printers'],
                            ['label' => 'Audit Logs', 'route' => 'audit.index', 'perm' => 'audit.view', 'icon' => 'document'],
                        ]],
                    ];
                    $canSee = fn ($item) => empty($item['perm']) || auth()->user()->hasPermission($item['perm']);
                    $navIsActive = fn (array $item) => request()->routeIs($item['active'] ?? $item['route'] ?? '');
                @endphp

                @foreach ($nav as $item)
                    @php
                        $children = collect($item['children'] ?? [])->filter($canSee)->values();
                        $visible = $children->isNotEmpty() || ($children->isEmpty() && ! empty($item['route']) && $canSee($item));
                    @endphp
                    @if ($visible)
                        @php
                            $groupActive = $children->contains(fn ($child) => $navIsActive($child))
                                || (! empty($item['route']) && $navIsActive($item));
                            $leafActive = $children->isEmpty() && $groupActive;
                        @endphp
                        <div class="{{ $children->isNotEmpty() ? 'nav-group' : '' }}" data-nav="{{ $item['label'] }}" data-open="{{ $groupActive ? '1' : '0' }}" x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }" @if ($children->isNotEmpty()) :class="open && 'nav-group-open'" @endif>
                            @if ($children->isNotEmpty())
                                <button type="button" class="nav-item" @click="open = !open">
                                    @include('layouts.partials.icon', ['name' => $item['icon']])
                                    <span class="flex-1" x-show="!collapsed">{{ $item['label'] }}</span>
                                    <svg class="h-3.5 w-3.5 shrink-0 text-white/70 transition-transform" x-show="!collapsed" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9l6 6 6-6"/>
                                    </svg>
                                </button>
                                <div class="space-y-0.5 px-2 pb-2 pl-3" x-show="open && !collapsed">
                                    @foreach ($children as $child)
                                        <a href="{{ route($child['route']) }}" class="nav-subitem {{ $navIsActive($child) ? 'nav-subitem-active' : '' }}">
                                            @include('layouts.partials.icon', ['name' => $child['icon'] ?? 'clipboard', 'size' => 'h-4 w-4'])
                                            <span>{{ $child['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <a href="{{ route($item['route']) }}" class="nav-item {{ $leafActive ? 'nav-item-active' : '' }}">
                                    @include('layouts.partials.icon', ['name' => $item['icon']])
                                    <span class="flex-1" x-show="!collapsed">{{ $item['label'] }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                @endforeach
            </nav>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 mx-3 mt-3 flex h-16 items-center justify-between rounded-xl bg-white px-4 shadow-[0_4px_18px_rgba(47,43,61,0.10)] lg:mx-6 lg:px-5">
                <div class="flex items-center gap-3">
                    <button class="rounded-lg border border-line p-2 text-heading lg:hidden" @click="sidebar = !sidebar">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-2.5">
                    <div
                        class="relative"
                        x-data="lowStockAlerts()"
                        x-init="load()"
                    >
                        <button type="button" class="relative rounded-lg border border-line p-2 text-heading hover:bg-slate-50" @click="open = !open">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
                            <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand px-1 text-[10px] font-semibold text-white" x-show="count > 0" x-text="count" x-cloak></span>
                        </button>
                        <div class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-line bg-white p-3 shadow-lg" x-show="open" x-cloak @click.outside="open = false">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Stok menipis</p>
                            <div class="mt-2 max-h-64 space-y-2 overflow-y-auto">
                                <template x-for="item in items" :key="item.id">
                                    <div class="rounded-lg bg-brand-soft px-3 py-2 text-xs">
                                        <p class="font-medium text-heading" x-text="item.name"></p>
                                        <p class="text-muted" x-text="`Sisa ${item.quantity} ${item.unit || ''} · reorder ${item.reorder_level}`"></p>
                                    </div>
                                </template>
                                <p class="py-6 text-center text-xs text-muted" x-show="!items.length">Tidak ada stok di bawah reorder level.</p>
                            </div>
                        </div>
                    </div>
                    @if (auth()->user()->canSwitchOutlet())
                        <form method="GET" action="{{ url()->current() }}">
                            @foreach (request()->except('switch_outlet') as $k => $v)
                                @if (!is_array($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
                            @endforeach
                            <select name="switch_outlet" onchange="this.form.submit()" class="input !w-auto !rounded-lg !py-2 !text-[13px]">
                                @foreach (\App\Models\Outlet::query()->where('is_active', true)->get() as $outlet)
                                    <option value="{{ $outlet->id }}" @selected(current_outlet_id() === $outlet->id)>{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    @include('layouts.partials.user-menu')
                </div>
            </header>

            <main class="flex-1 px-3 py-5 lg:px-6">
                @include('layouts.partials.page-header')
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" x-data x-init="setTimeout(() => $el.remove(), 3200)">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ $errors->first() }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-line bg-white px-2 py-2 lg:hidden">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-1 text-[11px] {{ request()->routeIs('dashboard') ? 'text-brand' : 'text-muted' }}">Home</a>
        @if (auth()->user()?->can('pos.access'))
            <a href="{{ route('pos.index') }}" class="flex flex-col items-center gap-1 text-[11px] {{ request()->routeIs('pos.*') ? 'text-brand' : 'text-muted' }}">POS</a>
        @endif
        @can('orders.view')<a href="{{ route('orders.index') }}" class="flex flex-col items-center gap-1 text-[11px] {{ request()->routeIs('orders.*') ? 'text-brand' : 'text-muted' }}">Sales</a>@endcan
        @can('tables.view')<a href="{{ route('tables.index') }}" class="flex flex-col items-center gap-1 text-[11px] {{ request()->routeIs('tables.*') ? 'text-brand' : 'text-muted' }}">Tables</a>@endcan
        <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-1 text-[11px] {{ request()->routeIs('profile.*') ? 'text-brand' : 'text-muted' }}">Me</a>
    </nav>

    @include('layouts.partials.select2')
    @livewireScripts
    <script>
        document.querySelector('script[data-update-uri="/livewire/update"]')
            ?.setAttribute('data-update-uri', @json(parse_url(url('/livewire/update'), PHP_URL_PATH)));
    </script>
    <script>
        function lowStockAlerts() {
            return {
                open: false,
                count: 0,
                items: [],
                async load() {
                    try {
                        const res = await fetch('{{ route('alerts.low-stock') }}', { headers: { 'Accept': 'application/json' } });
                        if (! res.ok) return;
                        const data = await res.json();
                        this.count = data.count || 0;
                        this.items = data.items || [];
                    } catch (e) {}
                    setTimeout(() => this.load(), 60000);
                },
            };
        }
    </script>
    @stack('scripts')
</body>
</html>
