<?php

namespace App\Http\Controllers;

use App\Enums\WasteReason;
use App\Models\Product;
use App\Models\Waste;
use App\Services\WasteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WasteController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('inventory.view'), 403);

        return view('wastes.index', [
            'wastes' => Waste::query()->with(['product.unit', 'user', 'outlet'])->latest()->paginate(20),
            'products' => Product::query()->where('is_stockable', true)->orderBy('name')->get(),
            'reasons' => WasteReason::cases(),
        ]);
    }

    public function store(Request $request, WasteService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'reason' => ['required', 'in:damaged,expired,spoiled,production_waste,wrong_preparation,other'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['outlet_id'] = current_outlet_id();
        $service->record($data);

        return back()->with('success', 'Waste tercatat dan stok berkurang.');
    }
}
