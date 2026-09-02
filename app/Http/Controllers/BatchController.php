<?php

namespace App\Http\Controllers;

use App\Models\ProductionBatch;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('production.view'), 403);

        return view('batches.index', [
            'batches' => ProductionBatch::query()->with(['product', 'outlet', 'productionOrder'])->latest()->paginate(20),
        ]);
    }

    public function show(ProductionBatch $batch): View
    {
        return view('batches.show', [
            'batch' => $batch->load(['product', 'outlet', 'destinationOutlet', 'productionOrder.items.product', 'productionOrder.bom.items.component', 'salesItems.order']),
        ]);
    }
}
