<?php

namespace App\Http\Controllers;

use App\Models\ZohoSyncRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ZohoSyncLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('products.view'), 403);

        $filters = $request->only(['status', 'date']);
        $query = ZohoSyncRun::query()->latest('id');

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if (filled($filters['date'] ?? null)) {
            [$from, $to] = wib_day_bounds($filters['date']);
            $query->whereBetween('started_at', [$from, $to]);
        }

        return view('sync-logs.index', [
            'runs' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'stats' => [
                'total' => ZohoSyncRun::query()->count(),
                'success' => ZohoSyncRun::query()->where('status', 'success')->count(),
                'failed' => ZohoSyncRun::query()->where('status', 'failed')->count(),
            ],
        ]);
    }

    public function show(Request $request, ZohoSyncRun $syncLog): View
    {
        abort_unless($request->user()->hasPermission('products.view'), 403);

        return view('sync-logs.show', ['run' => $syncLog]);
    }
}
