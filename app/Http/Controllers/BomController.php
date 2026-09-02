<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BomController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('production.view'), 403);

        return view('boms.index', [
            'boms' => Bom::query()->with(['product', 'items'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('boms.form', [
            'bom' => new Bom(['is_active' => true, 'yield_percentage' => 100, 'version' => '1.0']),
            'products' => Product::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('production.manage'), 403);
        $data = $this->validated($request);
        $items = $data['items'];
        unset($data['items']);
        $bom = Bom::query()->create($data);
        foreach ($items as $item) {
            $bom->items()->create($item);
        }

        return redirect()->route('boms.show', $bom)->with('success', 'BOM dibuat.');
    }

    public function show(Bom $bom, ProductionService $production): View
    {
        $bom->load(['product', 'items.component.unit', 'items.unit']);

        return view('boms.show', [
            'bom' => $bom,
            'exploded' => $production->explode($bom, 1),
        ]);
    }

    public function edit(Bom $bom): View
    {
        return view('boms.form', [
            'bom' => $bom->load('items'),
            'products' => Product::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Bom $bom): RedirectResponse
    {
        $data = $this->validated($request);
        $items = $data['items'];
        unset($data['items']);
        $bom->update($data);
        $bom->items()->delete();
        foreach ($items as $item) {
            $bom->items()->create($item);
        }

        return redirect()->route('boms.show', $bom)->with('success', 'BOM diperbarui.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'version' => ['required', 'string', 'max:20'],
            'yield_percentage' => ['required', 'numeric', 'min:1', 'max:200'],
            'waste_percentage' => ['nullable', 'numeric', 'min:0'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.component_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['nullable', 'exists:units,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.waste_percentage' => ['nullable', 'numeric', 'min:0'],
            'items.*.yield_percentage' => ['nullable', 'numeric', 'min:1'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
