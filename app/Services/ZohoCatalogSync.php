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
use App\Models\ZohoSyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZohoCatalogSync
{
    public function __construct(protected ZohoClient $zoho) {}

    public function run(bool $fresh = false, ?callable $onProgress = null, string $trigger = 'cli'): array
    {
        $run = ZohoSyncRun::query()->create([
            'status' => 'running',
            'trigger' => $trigger,
            'started_at' => now(),
        ]);

        try {
            if ($fresh) {
                $this->wipeOperationalData();
            }

            $locations = $this->zoho->paginate('/inventory/v1/locations', 'locations');
            $outlets = $this->syncOutlets($locations);
            $this->attachUsers($outlets);
            $onProgress && $onProgress('Outlets '.$outlets->count());

            $items = $this->zoho->paginate('/inventory/v1/items', 'items');
            $total = count($items);
            $onProgress && $onProgress('Items '.$total);
            $products = 0;
            $stocks = 0;
            $seen = [];
            $created = [];
            $updated = [];

            foreach ($items as $row) {
                $itemId = (string) ($row['item_id'] ?? '');
                if ($itemId === '') {
                    continue;
                }

                try {
                    $detail = array_merge($row, $this->zoho->get('/inventory/v1/items/'.$itemId)['item'] ?? []);
                } catch (\RuntimeException) {
                    $detail = $row; // ponytail: list row still updates price/status; keep old stock if locations missing
                }

                [$product, $change] = $this->syncProduct($detail);
                if ($change['action'] === 'created') {
                    $created[] = $change;
                } elseif ($change['action'] === 'updated') {
                    $updated[] = $change;
                }
                $seen[] = $itemId;
                $products++;
                $stocks += $this->syncStock($product, $outlets, $detail['locations'] ?? []);
                if ($onProgress && $products % 25 === 0) {
                    $onProgress($products.'/'.$total);
                }
            }

            $removedRows = [];
            if ($seen !== []) {
                $gone = Product::query()
                    ->whereNotNull('zoho_item_id')
                    ->whereNotIn('zoho_item_id', $seen)
                    ->where(function ($q) {
                        $q->where('is_active', true)->orWhere('is_sellable', true);
                    })
                    ->get(['sku', 'name', 'zoho_item_id']);

                $removedRows = $gone->map(fn (Product $product) => [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'zoho_item_id' => $product->zoho_item_id,
                ])->all();

                if ($gone->isNotEmpty()) {
                    Product::query()
                        ->whereIn('zoho_item_id', $gone->pluck('zoho_item_id'))
                        ->update(['is_active' => false, 'is_sellable' => false]);
                }
            }
            $removedCategories = $seen !== [] ? $this->pruneEmptyCategories() : [];
            $onProgress && $removedCategories && $onProgress('Kategori kosong dihapus: '.implode(', ', $removedCategories));

            $summary = [
                'outlets' => $outlets->count(),
                'products' => $products,
                'created' => count($created),
                'updated' => count($updated),
                'removed' => count($removedRows),
                'stocks' => $stocks,
                'run_id' => $run->id,
            ];

            $run->update([
                'status' => 'success',
                'finished_at' => now(),
                'outlets' => $summary['outlets'],
                'products' => $summary['products'],
                'created_count' => $summary['created'],
                'updated_count' => $summary['updated'],
                'removed_count' => $summary['removed'],
                'stocks' => $summary['stocks'],
                'details' => [
                    'created' => $created,
                    'updated' => $updated,
                    'removed' => $removedRows,
                    'removed_categories' => $removedCategories,
                ],
            ]);

            return $summary;
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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

    /**
     * @return array{0: Product, 1: array{action: string, sku: string, name: string, changes?: array<string, array{0: mixed, 1: mixed}>}}
     */
    protected function syncProduct(array $item): array
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
        $existing = Product::query()->where('zoho_item_id', $itemId)->value('image');
        $image = $this->syncImage($itemId, $item, $existing);

        $data = [
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
        ];
        if ($image) {
            $data['image'] = $image;
        }

        $product = Product::withTrashed()->firstOrNew(['zoho_item_id' => $itemId]);
        $isNew = ! $product->exists;
        $before = $isNew ? null : $product->only(['sku', 'name', 'price', 'cost', 'is_sellable', 'is_active', 'category_id']);

        if ($product->trashed()) {
            $product->restore();
        }
        $product->fill($data)->save();

        if ($isNew) {
            return [$product, [
                'action' => 'created',
                'sku' => $product->sku,
                'name' => $product->name,
            ]];
        }

        $changes = [];
        foreach (['sku', 'name', 'price', 'cost', 'is_sellable', 'is_active', 'category_id'] as $field) {
            $old = $before[$field] ?? null;
            $new = $product->{$field};
            if ($field === 'price' || $field === 'cost') {
                $old = round((float) $old, 2);
                $new = round((float) $new, 2);
            }
            if ($old != $new) {
                $changes[$field] = [$old, $new];
            }
        }

        if ($changes === []) {
            return [$product, ['action' => 'unchanged', 'sku' => $product->sku, 'name' => $product->name]];
        }

        return [$product, [
            'action' => 'updated',
            'sku' => $product->sku,
            'name' => $product->name,
            'changes' => $changes,
        ]];
    }

    protected function syncImage(string $itemId, array $item, ?string $existing): ?string
    {
        foreach ([$item['image_url'] ?? null, $item['item_images'][0]['image_url'] ?? null] as $url) {
            if (is_string($url) && str_starts_with($url, 'http')) {
                return $url;
            }
        }

        if ($existing) {
            return $existing; // ponytail: skip redownload; drop this if Zoho image edits must show same day
        }

        if (! filled($item['image_id'] ?? null) && ! filled($item['image_name'] ?? null) && ! filled($item['item_images'][0]['image_id'] ?? null)) {
            return null;
        }

        $body = $this->zoho->download('/inventory/v1/items/'.$itemId.'/image');
        if ($body === null) {
            return null;
        }

        $ext = strtolower((string) pathinfo((string) ($item['image_name'] ?? ''), PATHINFO_EXTENSION));
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $ext = str_starts_with($body, "\x89PNG") ? 'png' : 'jpg';
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $path = 'products/zoho-'.$itemId.'.'.$ext;
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    protected function syncStock(Product $product, $outlets, array $locations): int
    {
        if ($locations === []) {
            return 0; // ponytail: no location list means Zoho does not track this item per outlet
        }

        $activeIds = collect($locations)
            ->filter(fn (array $row) => ($row['status'] ?? 'active') === 'active')
            ->mapWithKeys(fn (array $row) => [(string) $row['location_id'] => $row]);

        $count = 0;

        foreach ($outlets as $locationId => $outlet) {
            $row = $activeIds->get((string) $locationId);
            if ($row === null) {
                Inventory::query()->where('outlet_id', $outlet->id)->where('product_id', $product->id)->delete();
                $outlet->products()->detach($product->id);

                continue;
            }

            Inventory::query()->updateOrCreate(
                ['outlet_id' => $outlet->id, 'product_id' => $product->id],
                // Physical Stock di Zoho; location_stock_on_hand itu Accounting Stock (baru naik setelah bill)
                ['quantity' => (float) ($row['location_actual_available_stock'] ?? $row['location_stock_on_hand'] ?? 0), 'reserved_quantity' => 0],
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

        $category = Category::withTrashed()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'station' => PrinterStation::Cashier->value,
                'is_active' => true,
            ],
        );
        if ($category->trashed()) {
            $category->restore();
        }

        return $category;
    }

    /** Kategori tanpa item tersisa (item pindah/hapus di Zoho) dibuang supaya daftar kategori tidak menumpuk. */
    protected function pruneEmptyCategories(): array
    {
        $empty = Category::query()->whereDoesntHave('products')->get(['id', 'name']);
        $empty->each->delete();

        return $empty->pluck('name')->all();
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

        // ponytail: keep orders + outlets/products they reference; sync updateOrCreate handles catalog
        foreach ([
            'table_sessions', 'table_reservations',
            'inventory_movements', 'inventories',
            'production_order_items', 'production_batches', 'production_orders',
            'bom_items', 'boms',
            'stock_opname_items', 'stock_opnames',
            'stock_transfer_items', 'stock_transfers',
            'wastes',
            'bundle_items', 'bundle_outlet', 'bundles',
            'discount_items', 'discount_outlet', 'discounts',
            'product_addon', 'product_variants', 'outlet_product',
            'printer_routes', 'printers',
        ] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
