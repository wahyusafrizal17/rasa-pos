<div class="mb-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('reports.sales', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.sales') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Sales</a>
    <a href="{{ route('reports.products', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.products') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Products</a>
    <a href="{{ route('reports.categories', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.categories') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Categories</a>
    <a href="{{ route('reports.promo', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.promo') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Promo</a>
    <a href="{{ route('reports.inventory') }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.inventory') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Inventory</a>
    <a href="{{ route('reports.movements', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.movements') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Movements</a>
    <a href="{{ route('reports.production', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.production') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Production</a>
    <a href="{{ route('reports.customers') }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.customers') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Customers</a>
    <a href="{{ route('reports.waste', request()->only(['from', 'to', 'period', 'outlet_id'])) }}" class="rounded-full px-3 py-1.5 {{ request()->routeIs('reports.waste') ? 'bg-ink text-white' : 'bg-slate-100 text-slate-600' }}">Waste</a>
</div>
