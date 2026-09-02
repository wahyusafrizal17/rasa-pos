<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('inventory.view'), 403);

        return view('opname.index', [
            'opnames' => StockOpname::query()->with(['outlet', 'creator'])->latest()->paginate(20),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockOpnameService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['outlet_id'] = current_outlet_id();
        $opname = $service->create($data);

        return redirect()->route('opnames.show', $opname)->with('success', 'Stock opname dibuat.');
    }

    public function show(StockOpname $opname): View
    {
        return view('opname.show', ['opname' => $opname->load(['items.product.unit', 'outlet'])]);
    }

    public function update(Request $request, StockOpname $opname, StockOpnameService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.physical_qty' => ['required', 'numeric'],
            'items.*.reason' => ['nullable', 'string'],
        ]);
        $service->updateItems($opname, $data['items']);

        return back()->with('success', 'Perhitungan fisik disimpan.');
    }

    public function finalize(StockOpname $opname, StockOpnameService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('inventory.approve'), 403);
        $service->finalize($opname);

        return back()->with('success', 'Stock opname difinalisasi. Adjustment sudah dibuat.');
    }
}
