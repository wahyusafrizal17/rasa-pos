<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('products.view'), 403);

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $request->all(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        return view('products.form', [
            'product' => new Product(['is_active' => true, 'is_sellable' => true, 'is_stockable' => true, 'type' => ProductType::Finished]),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        Product::query()->create($this->payload($request));

        return redirect()->route('products.index')->with('success', 'Produk ditambahkan.');
    }

    public function edit(Product $product): View
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);

        return view('products.form', [
            'product' => $product->load('variants'),
            'categories' => Category::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $product->update($this->payload($request, $product));

        $variants = $request->input('variants', []);
        $keep = [];
        foreach ($variants as $row) {
            if (empty($row['name'])) {
                continue;
            }
            $variant = $product->variants()->updateOrCreate(
                ['id' => $row['id'] ?? 0],
                [
                    'name' => $row['name'],
                    'sku' => $row['sku'] ?? null,
                    'price_adjustment' => $row['price_adjustment'] ?? 0,
                    'is_active' => true,
                ]
            );
            $keep[] = $variant->id;
        }
        $product->variants()->whereNotIn('id', $keep ?: [0])->delete();

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);
        $product->delete();

        return back()->with('success', 'Produk dihapus.');
    }

    protected function payload(Request $request, ?Product $product = null): array
    {
        $data = $this->validated($request, $product?->id);
        unset($data['image_file']);

        if ($request->hasFile('image_file')) {
            $data['image'] = $request->file('image_file')->store('products', 'public');
        }

        return $data;
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:products,sku,'.($id ?? 'NULL')],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'type' => ['required', 'in:raw,semi_finished,finished,package'],
            'bom_level' => ['nullable', 'integer', 'min:0', 'max:4'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'max:4096'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_stockable' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'maximum_stock' => ['nullable', 'numeric', 'min:0'],
            'station' => ['nullable', 'in:kitchen,bar,cashier'],
            'prep_minutes' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_sellable' => $request->boolean('is_sellable'),
            'is_stockable' => $request->boolean('is_stockable'),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
