<?php

namespace App\Http\Controllers;

use App\Enums\PrinterStation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PrinterRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckerController extends Controller
{
    public function pendingJobs(Request $request, PrinterRoutingService $printers): JsonResponse
    {
        abort_unless($request->user()->hasPermission('orders.check'), 403);

        $station = $this->checkerStation($request->user());
        abort_unless($station, 403);

        $outletId = current_outlet_id();
        $printer = Printer::query()
            ->where('outlet_id', $outletId)
            ->where('station', $station)
            ->where('is_active', true)
            ->value('name');

        return response()->json([
            'station' => $station,
            'printer' => $printer ?: '',
            'jobs' => $printers->pendingJobs($outletId, $station),
        ]);
    }

    public function ackJob(Request $request, Order $order, PrinterRoutingService $printers): JsonResponse
    {
        abort_unless($request->user()->hasPermission('orders.check'), 403);

        $station = $this->checkerStation($request->user());
        abort_unless($station, 403);
        abort_unless((int) $order->outlet_id === (int) current_outlet_id(), 403);

        if ($printers->stationItems($order, $station)->isEmpty()) {
            abort(403);
        }

        $printers->markPrinted($order, $station);

        return response()->json(['ok' => true]);
    }

    public function updateItem(Request $request, OrderItem $item, OrderService $orders): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('orders.check'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,preparing,ready,served'],
        ]);

        $station = $item->station ?? $item->product?->station ?? $item->product?->category?->station;
        $user = $request->user();

        if ($user->hasRole('kitchen') && $station !== PrinterStation::Kitchen->value) {
            abort(403);
        }
        if ($user->hasRole('bar') && $station !== PrinterStation::Bar->value) {
            abort(403);
        }

        $orders->updateItemStatus($item, $data['status']);

        return back()->with('success', 'Item dikonfirmasi.');
    }

    protected function checkerStation(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        if ($user->hasRole('kitchen')) {
            return PrinterStation::Kitchen->value;
        }

        if ($user->hasRole('bar')) {
            return PrinterStation::Bar->value;
        }

        return null;
    }
}
