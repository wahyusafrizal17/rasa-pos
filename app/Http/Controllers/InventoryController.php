<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function movements(Request $request): View
    {
        abort_unless($request->user()->hasPermission('inventory.view'), 403);

        $outletId = current_outlet_id();
        $filters = $request->only(['reference', 'product', 'type']);
        $query = InventoryMovement::query()
            ->with(['product.unit', 'unit', 'user', 'outlet'])
            ->when($outletId, fn ($q, $id) => $q->where('outlet_id', $id));

        if (filled($filters['reference'] ?? null)) {
            $query->where('reference_number', 'like', '%'.$filters['reference'].'%');
        }
        if (filled($filters['product'] ?? null)) {
            $query->whereHas('product', function ($product) use ($filters) {
                $product->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%'.$filters['product'].'%')
                        ->orWhere('sku', 'like', '%'.$filters['product'].'%');
                });
            });
        }
        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }

        $base = InventoryMovement::query()->when($outletId, fn ($q, $id) => $q->where('outlet_id', $id));

        return view('inventory.movements', [
            'movements' => $query
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'stats' => [
                'total' => (clone $base)->count(),
                'in' => (clone $base)->where('quantity', '>', 0)->count(),
                'out' => (clone $base)->where('quantity', '<', 0)->count(),
            ],
        ]);
    }
}
