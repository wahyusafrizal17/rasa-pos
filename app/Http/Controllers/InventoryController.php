<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        abort_unless($request->user()->hasPermission('inventory.view'), 403);
        $outletId = current_outlet_id();

        $stocks = Inventory::query()
            ->with(['product.unit', 'product.category'])
            ->where('outlet_id', $outletId)
            ->when($request->search, fn ($q, $s) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%")))
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', [
            'stocks' => $stocks,
            'lowStock' => $inventory->lowStock($outletId),
            'outOfStock' => $inventory->outOfStock($outletId),
        ]);
    }

    public function movements(Request $request): View
    {
        abort_unless($request->user()->hasPermission('inventory.view'), 403);

        $movements = InventoryMovement::query()
            ->with(['product', 'user', 'outlet'])
            ->when(current_outlet_id(), fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('inventory.movements', ['movements' => $movements]);
    }

    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $inventory->adjust(
            current_outlet_id(),
            (int) $data['product_id'],
            (float) $data['quantity'],
            \App\Enums\StockMovementType::Adjustment,
            $data['reason'],
            null,
            null,
            true,
        );

        return back()->with('success', 'Stok disesuaikan.');
    }
}
