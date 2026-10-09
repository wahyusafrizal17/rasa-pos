<?php

namespace App\Services;

use App\Enums\PrinterStation;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ZohoCatalogSync
{
    public function __construct(protected ZohoClient $zoho) {}

    public function run(bool $fresh = false): array
    {
        if ($fresh) {
            $this->wipeOperationalData();
        }

        $locations = $this->zoho->paginate('/inventory/v1/locations', 'locations');
        $outlets = $this->syncOutlets($locations);
        $this->attachUsers($outlets);

        $items = $this->zoho->paginate('/inventory/v1/items', 'items');
        $products = 0;
        $stocks = 0;

        foreach ($items as $row) {
            try {
                $detail = array_merge($row, $this->zoho->get('/inventory/v1/items/'.$row['item_id'])['item'] ?? []);
            } catch (\RuntimeException) {
                continue;
            }

            $product = $this->syncProduct($detail);
            $products++;
            $stocks += $this->syncStock($product, $outlets, $detail['locations'] ?? []);
        }

        return [
            'outlets' => $outlets->count(),
            'products' => $products,
            'stocks' => $stocks,
        ];
    }

    protected function syncOutlets(array $locations)
    {
        $outlets = collect();

        foreach ($locations as $location) {
            $name = (string) ($location['location_name'] ?? '');
            if ($name === '') {
                continue;
            }

            $address = $location['address'] ?? [];
            $outlet = Outlet::query()->updateOrCreate(
                ['zoho_location_id' => (string) $location['location_id']],
                [
                    'code' => $this->outletCode($name, (string) $location['location_id']),
                    'name' => $name,
                    'city' => $address['city'] ?? null,
                    'address' => $address['street_address1'] ?? null,
                    'phone' => $location['phone'] ?? null,
                    'is_central_kitchen' => (bool) ($location['is_primary_location'] ?? false),
                    'is_active' => (bool) ($location['is_location_active'] ?? true),
                ],
            );
            $outlets->put((string) $location['location_id'], $outlet);
        }

        return $outlets;
    }

    protected function syncProduct(array $item): Product
    {
        $itemId = (string) $item['item_id'];
        $sku = trim((string) ($item['sku'] ?? ''));
        if ($sku === '') {
            $sku = 'Z'.$itemId;
        }

        $skuTaken = Product::query()->where('sku', $sku)->where('zoho_item_id', '!=', $itemId)->exists();
        if ($skuTaken) {
            $sku = $sku.'-'.$itemId;
        }

        $category = $this->category($this->itemCategory($item));
        $unit = $this->unit((string) ($item['unit'] ?? 'PCS'));
        $sold = (bool) ($item['can_be_sold'] ?? true);
        $active = ($item['status'] ?? 'active') === 'active';
        $stockable = (bool) ($item['track_inventory'] ?? (($item['item_type'] ?? '') === 'inventory'));

        return Product::query()->updateOrCreate(
            ['zoho_item_id' => $itemId],
            [
                'sku' => $sku,
                'name' => $item['name'] ?? $sku,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'type' => $sold ? ProductType::Finished : ProductType::Raw,
                'description' => $item['description'] ?? null,
                'price' => (float) ($item['rate'] ?? 0),
                'cost' => (float) ($item['purchase_rate'] ?? 0),
                'is_sellable' => $sold && $active,
                'is_stockable' => $stockable,
                'is_active' => $active,
                'reorder_level' => (float) ($item['reorder_level'] ?? 0),
            ],
        );
    }

    protected function syncStock(Product $product, $outlets, array $locations): int
    {
        $count = 0;

        foreach ($outlets as $locationId => $outlet) {
            $row = collect($locations)->firstWhere('location_id', $locationId);
            $qty = (float) ($row['location_stock_on_hand'] ?? 0);
            Inventory::query()->updateOrCreate(
                ['outlet_id' => $outlet->id, 'product_id' => $product->id],
                ['quantity' => $qty, 'reserved_quantity' => 0],
            );
            $outlet->products()->syncWithoutDetaching([
                $product->id => ['price' => $product->price, 'is_available' => $product->is_sellable],
            ]);
            $count++;
        }

        return $count;
    }

    protected function category(string $name): Category
    {
        $name = trim($name) ?: 'Uncategorized';
        $slug = Str::slug($name) ?: 'uncategorized';

        return Category::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'station' => PrinterStation::Cashier->value,
                'is_active' => true,
            ],
        );
    }

    protected function itemCategory(array $item): string
    {
        foreach (['cf_category', 'cf_product_category'] as $key) {
            $value = trim((string) ($item[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($item['custom_fields'] ?? [] as $field) {
            $api = (string) ($field['api_name'] ?? $field['placeholder'] ?? '');
            if (! in_array($api, ['cf_category', 'cf_product_category'], true)) {
                continue;
            }
            $value = trim((string) ($field['value'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return trim((string) ($item['category_name'] ?? '')) ?: 'Uncategorized';
    }

    protected function unit(string $name): Unit
    {
        $name = trim($name) ?: 'PCS';
        $aliases = [
            'pcs' => 'PCS', 'pc' => 'PCS', 'piece' => 'PCS', 'pieces' => 'PCS',
            'kg' => 'KG', 'kilogram' => 'KG', 'kilograms' => 'KG',
            'g' => 'G', 'gram' => 'G', 'grams' => 'G',
            'l' => 'L', 'liter' => 'L', 'litre' => 'L', 'liters' => 'L',
            'ml' => 'ML', 'milliliter' => 'ML', 'millilitre' => 'ML',
        ];
        $code = $aliases[strtolower($name)] ?? (strtoupper(Str::slug($name, '')) ?: 'PCS');
        $code = substr($code, 0, 10);

        return Unit::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'family' => 'count', 'conversion_factor' => 1],
        );
    }

    protected function outletCode(string $name, string $locationId): string
    {
        $code = strtoupper(Str::slug($name, '-')) ?: 'LOC';
        $code = substr($code, 0, 24);

        $taken = Outlet::query()
            ->where('code', $code)
            ->where('zoho_location_id', '!=', $locationId)
            ->exists();

        return $taken ? substr($code, 0, 16).'-'.substr($locationId, -4) : $code;
    }

    protected function attachUsers($outlets): void
    {
        $ids = $outlets->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        $default = $outlets->first(fn (Outlet $outlet) => $outlet->is_central_kitchen)?->id ?? $ids->first();

        User::query()->each(function (User $user) use ($ids, $default) {
            $sync = [];
            foreach ($ids as $id) {
                $sync[$id] = ['is_default' => (int) $id === (int) $default];
            }
            $user->outlets()->sync($sync);
        });
    }

    protected function wipeOperationalData(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereNotNull('outlet_id')->delete();
        }

        foreach ([
            'payments', 'order_status_histories', 'order_items', 'orders',
            'table_sessions', 'table_reservations', 'tables',
            'inventory_movements', 'inventories',
            'production_order_items', 'production_batches', 'production_orders',
            'bom_items', 'boms',
            'stock_opname_items', 'stock_opnames',
            'stock_transfer_items', 'stock_transfers',
            'wastes',
            'bundle_items', 'bundle_outlet', 'bundles',
            'discount_items', 'discount_outlet', 'discounts',
            'customers',
            'product_addon', 'product_variants', 'outlet_product', 'products',
            'categories',
            'printer_routes', 'printers',
            'outlet_users', 'outlets',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
