<?php

namespace App\Http\Controllers;

use App\Console\Commands\SyncZohoCatalog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('products.view'), 403);

        $filters = $request->only(['sku', 'name', 'category_id', 'type', 'status']);
        $query = Product::query()->with(['category', 'unit', 'variants', 'addons']);

        if (filled($filters['sku'] ?? null)) {
            $query->where('sku', 'like', '%'.$filters['sku'].'%');
        }
        if (filled($filters['name'] ?? null)) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }
        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }
        if (filled($filters['type'] ?? null)) {
            $query->where('type', $filters['type']);
        }
        if (($filters['status'] ?? '') === 'active') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? '') === 'inactive') {
            $query->where('is_active', false);
        }

        $focusProduct = $request->filled('product')
            ? Product::query()->with(['category', 'unit', 'variants', 'addons'])->find($request->integer('product'))
            : null;

        return view('products.index', [
            'products' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'categories' => Category::query()->orderBy('name')->get(),
            'stats' => [
                'total' => Product::query()->count(),
                'sellable' => Product::query()->where('is_sellable', true)->where('is_active', true)->count(),
                'inactive' => Product::query()->where('is_active', false)->count(),
            ],
            'focusPayload' => $focusProduct?->toModalArray(),
        ]);
    }

    public function sync(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);

        $lock = Cache::lock(SyncZohoCatalog::LOCK, 45 * 60);
        if (! $lock->get()) {
            return back()->with('success', 'Sinkronisasi Zoho masih berjalan.');
        }
        $lock->release();

        $php = PHP_BINARY;
        if ($php === '' || str_contains(strtolower($php), 'fpm')) {
            $php = 'php';
        }
        $command = escapeshellarg($php).' '.escapeshellarg(base_path('artisan')).' zoho:sync-catalog --no-interaction > /dev/null 2>&1 &';
        if (app()->runningUnitTests()) {
            Cache::put('zoho-catalog-sync.spawned', $command, 60);
        } else {
            exec($command);
        }

        return back()->with('success', 'Sinkronisasi Zoho dimulai. Katalog selesai dalam beberapa menit.');
    }

    public function create(): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        return redirect()->route('products.index', ['modal' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $product = Product::query()->create($this->payload($request));
        $this->syncVariants($product, $request->input('variants', []));
        $this->syncAddons($product, $request);

        return redirect()->route('products.index')->with('success', 'Produk ditambahkan.');
    }

    public function edit(Product $product): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        return redirect()->route('products.index', ['modal' => 'edit', 'product' => $product->id]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $product->update($this->payload($request, $product));
        $this->syncVariants($product, $request->input('variants', []));
        $this->syncAddons($product, $request);

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }

    protected function payload(Request $request, ?Product $product = null): array
    {
        $data = $this->validated($request, $product?->id);
        unset($data['image_file'], $data['variants'], $data['addon_ids']);

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        return $data;
    }

    protected function syncVariants(Product $product, array $variants): void
    {
        $keep = [];

        foreach ($variants as $row) {
            if (! filled($row['name'] ?? null)) {
                continue;
            }

            $payload = [
                'name' => $row['name'],
                'sku' => filled($row['sku'] ?? null) ? $row['sku'] : null,
                'price_adjustment' => $row['price_adjustment'] ?? 0,
                'is_active' => true,
            ];

            $id = (int) ($row['id'] ?? 0);
            $variant = $id > 0
                ? $product->variants()->updateOrCreate(['id' => $id], $payload)
                : $product->variants()->create($payload);

            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep ?: [0])->delete();
    }

    protected function syncAddons(Product $product, Request $request): void
    {
        if ($product->is_addon) {
            $product->addons()->sync([]);

            return;
        }

        $ids = Product::query()
            ->where('is_addon', true)
            ->whereIn('id', $request->input('addon_ids', []))
            ->pluck('id');

        $product->addons()->sync($ids);
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,'.($id ?? 'NULL')],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw,semi_finished,finished,package'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_addon' => ['sometimes', 'boolean'],
            'is_stockable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', 'exists:products,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable'],
            'variants.*.name' => ['nullable', 'string', 'max:80'],
            'variants.*.sku' => ['nullable', 'string', 'max:50'],
            'variants.*.price_adjustment' => ['nullable', 'numeric'],
        ], [
            'sku.required' => 'SKU wajib diisi.',
            'sku.unique' => 'SKU sudah dipakai produk lain.',
            'name.required' => 'Nama wajib diisi.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'type.required' => 'Tipe wajib dipilih.',
            'price.required' => 'Harga jual wajib diisi.',
        ]) + [
            'is_sellable' => $request->boolean('is_sellable'),
            'is_addon' => $request->boolean('is_addon'),
            'is_stockable' => $request->boolean('is_stockable'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
