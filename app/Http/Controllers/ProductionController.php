<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('production.view'), 403);

        return view('production.index', [
            'orders' => ProductionOrder::query()->with(['product', 'outlet', 'user'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('production.form', [
            'products' => Product::query()->whereIn('type', ['semi_finished', 'finished'])->orderBy('name')->get(),
            'boms' => Bom::query()->where('is_active', true)->with('product')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, ProductionService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('production.manage'), 403);
        $data = $request->validate([
            'outlet_id' => ['required', 'exists:outlets,id'],
            'product_id' => ['required', 'exists:products,id'],
            'bom_id' => ['nullable', 'exists:boms,id'],
            'quantity_planned' => ['required', 'numeric', 'min:0.001'],
            'production_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
        $order = $service->create($data);

        return redirect()->route('production.show', $order)->with('success', 'Production order dibuat.');
    }

    public function show(ProductionOrder $production): View
    {
        return view('production.show', [
            'order' => $production->load(['items.product.unit', 'product', 'outlet', 'bom.items.component', 'batch', 'user']),
        ]);
    }

    public function start(ProductionOrder $production, ProductionService $service): RedirectResponse
    {
        $service->start($production);

        return back()->with('success', 'Produksi dimulai.');
    }

    public function complete(Request $request, ProductionOrder $production, ProductionService $service): RedirectResponse
    {
        $data = $request->validate([
            'quantity_produced' => ['required', 'numeric', 'min:0.001'],
        ]);
        $service->complete($production, (float) $data['quantity_produced']);

        return back()->with('success', 'Produksi selesai. Stok dan batch sudah tercatat.');
    }

    public function cancel(Request $request, ProductionOrder $production, ProductionService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $service->cancel($production, $data['reason']);

        return back()->with('success', 'Produksi dibatalkan.');
    }
}
