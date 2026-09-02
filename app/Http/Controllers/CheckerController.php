<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PrinterStation;
use App\Models\OrderItem;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckerController extends Controller
{
    public function kitchen(Request $request): View
    {
        return $this->board($request, PrinterStation::Kitchen->value, 'Kitchen Checker');
    }

    public function bar(Request $request): View
    {
        return $this->board($request, PrinterStation::Bar->value, 'Bar Checker');
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

    protected function board(Request $request, string $station, string $title): View
    {
        abort_unless($request->user()->hasPermission('orders.check'), 403);

        $outletId = current_outlet_id();

        $grouped = OrderItem::query()
            ->with(['order.table', 'order.customer', 'product', 'variant', 'confirmedBy'])
            ->whereHas('order', function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId)
                    ->whereNotIn('status', [
                        OrderStatus::Draft->value,
                        OrderStatus::Held->value,
                        OrderStatus::Completed->value,
                        OrderStatus::Cancelled->value,
                    ]);
            })
            ->where(function ($query) use ($station) {
                $query->where('station', $station)
                    ->orWhereHas('product', fn ($product) => $product->where('station', $station));
            })
            ->whereIn('status', ['new', 'preparing', 'ready'])
            ->orderBy('id')
            ->get()
            ->groupBy('order_id')
            ->sortBy(fn ($items) => $items->first()?->order?->created_at);

        $columns = [
            'new' => collect(),
            'preparing' => collect(),
            'ready' => collect(),
        ];

        foreach ($grouped as $orderId => $items) {
            $bucket = $items->contains(fn ($item) => $item->status === 'new')
                ? 'new'
                : ($items->contains(fn ($item) => $item->status === 'preparing') ? 'preparing' : 'ready');

            $columns[$bucket]->put($orderId, $items);
        }

        return view('checkers.board', [
            'title' => $title,
            'station' => $station,
            'columns' => $columns,
            'counts' => [
                'new' => $columns['new']->count(),
                'preparing' => $columns['preparing']->count(),
                'ready' => $columns['ready']->count(),
                'items' => $grouped->flatten()->count(),
            ],
        ]);
    }
}
