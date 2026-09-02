<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Printer;
use App\Models\PrinterRoute;

class PrinterRoutingService
{
    /**
     * Resolve print jobs for an order. Physical printer integration is intentionally
     * abstracted so a driver can be plugged in later.
     *
     * @return array<int, array{printer:Printer, items:array}>
     */
    public function route(Order $order): array
    {
        $order->loadMissing('items.product.category', 'outlet');

        $printers = Printer::query()
            ->with('routes')
            ->where('outlet_id', $order->outlet_id)
            ->where('is_active', true)
            ->get();

        $jobs = [];

        foreach ($printers as $printer) {
            $items = $order->items->filter(function ($item) use ($printer) {
                $station = $item->station ?? $item->product?->station ?? $item->product?->category?->station;
                $categoryId = $item->product?->category_id;

                if ($printer->routes->isEmpty()) {
                    return $station && $station === $printer->station->value;
                }

                return $printer->routes->contains(function (PrinterRoute $route) use ($station, $categoryId) {
                    if ($route->category_id && $route->category_id === $categoryId) {
                        return true;
                    }

                    return $route->station && $route->station === $station;
                });
            });

            if ($items->isNotEmpty()) {
                $jobs[] = [
                    'printer' => $printer,
                    'items' => $items->values()->all(),
                    'payload' => $this->payload($order, $printer, $items->all()),
                ];
            }
        }

        return $jobs;
    }

    public function payload(Order $order, Printer $printer, array $items): array
    {
        return [
            'type' => 'ticket',
            'station' => $printer->station->value,
            'printer' => [
                'name' => $printer->name,
                'ip' => $printer->ip_address,
                'port' => $printer->port,
            ],
            'order_number' => $order->order_number,
            'table' => $order->table?->code,
            'items' => collect($items)->map(fn ($item) => [
                'name' => $item->name,
                'qty' => $item->quantity,
                'notes' => $item->notes,
            ])->all(),
        ];
    }
}
