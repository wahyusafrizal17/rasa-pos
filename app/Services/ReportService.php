<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\Waste;
use Illuminate\Support\Carbon;

class ReportService
{
    public function sales(array $filters)
    {
        return Order::query()
            ->with(['outlet', 'customer', 'user'])
            ->where('payment_status', PaymentStatus::Paid->value)
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function productSales(array $filters)
    {
        return OrderItem::query()
            ->selectRaw('order_items.product_id, order_items.name, SUM(order_items.quantity) as qty, SUM(order_items.total) as total')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('orders.outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '<=', $d))
            ->groupBy('order_items.product_id', 'order_items.name')
            ->orderByDesc('qty')
            ->paginate(20)
            ->withQueryString();
    }

    public function inventoryValuation(?int $outletId = null)
    {
        return Inventory::query()
            ->with(['product.unit', 'outlet'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->whereHas('product', fn ($q) => $q->where('is_stockable', true))
            ->orderBy('product_id')
            ->paginate(30)
            ->withQueryString();
    }

    public function movements(array $filters)
    {
        return InventoryMovement::query()
            ->with(['product', 'outlet', 'user', 'unit'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('reference_number', 'like', "%{$s}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"));
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();
    }

    public function waste(array $filters)
    {
        return Waste::query()
            ->with(['product', 'outlet', 'user'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function transfers(array $filters)
    {
        return StockTransfer::query()
            ->with(['sourceOutlet', 'destinationOutlet', 'items'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where(function ($q) use ($id) {
                $q->where('source_outlet_id', $id)->orWhere('destination_outlet_id', $id);
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function opnames(array $filters)
    {
        return StockOpname::query()
            ->with(['outlet', 'creator'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function production(array $filters)
    {
        return ProductionOrder::query()
            ->with(['product', 'outlet'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('production_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('production_date', '<=', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function batches(array $filters)
    {
        return ProductionBatch::query()
            ->with(['product', 'outlet', 'productionOrder'])
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('outlet_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();
    }

    public function customers(array $filters)
    {
        return Customer::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%");
            }))
            ->orderByDesc('total_transaction')
            ->paginate(20)
            ->withQueryString();
    }

    public function categorySales(array $filters)
    {
        return OrderItem::query()
            ->selectRaw('categories.id as category_id, COALESCE(categories.name, "Tanpa kategori") as name, SUM(order_items.quantity) as qty, SUM(order_items.total) as total')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('orders.outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '<=', $d))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->paginate(20)
            ->withQueryString();
    }

    public function promoPerformance(array $filters): array
    {
        $discounts = Order::query()
            ->selectRaw('discounts.id, discounts.name, discounts.type, COUNT(orders.id) as usage_count, SUM(orders.discount_amount) as discount_total, SUM(orders.grand_total) as sales_total')
            ->join('discounts', 'discounts.id', '=', 'orders.discount_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('orders.outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '<=', $d))
            ->groupBy('discounts.id', 'discounts.name', 'discounts.type')
            ->orderByDesc('usage_count')
            ->get();

        $bundles = OrderItem::query()
            ->selectRaw('bundles.id, bundles.name, SUM(order_items.quantity) as qty, SUM(order_items.total) as sales_total')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('bundles', 'bundles.id', '=', 'order_items.bundle_id')
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->whereNotNull('order_items.bundle_id')
            ->when($filters['outlet_id'] ?? null, fn ($q, $id) => $q->where('orders.outlet_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('orders.created_at', '<=', $d))
            ->groupBy('bundles.id', 'bundles.name')
            ->orderByDesc('qty')
            ->get();

        return [
            'discounts' => $discounts,
            'bundles' => $bundles,
        ];
    }

    public function range(?string $from, ?string $to, ?string $period = null): array
    {
        [$fromDate, $toDate] = match ($period) {
            'today' => [Carbon::now()->toDateString(), Carbon::now()->toDateString()],
            'week' => [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->toDateString()],
            'month' => [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->toDateString()],
            default => [
                $from ?: Carbon::now()->startOfMonth()->toDateString(),
                $to ?: Carbon::now()->toDateString(),
            ],
        };

        return [
            'from' => $fromDate,
            'to' => $toDate,
            'period' => $period,
        ];
    }
}
