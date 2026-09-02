<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\Reward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function discounts(): View
    {
        abort_unless(auth()->user()->hasPermission('marketing.view'), 403);

        return view('marketing.discounts', [
            'discounts' => Discount::query()->with(['outlets', 'items'])->latest()->paginate(20),
            'products' => Product::query()->sellable()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->get(),
        ]);
    }

    public function storeDiscount(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('marketing.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'type' => ['required', 'in:percentage,nominal'],
            'scope' => ['required', 'in:order,item,category'],
            'value' => ['required', 'numeric', 'min:0'],
            'minimum_transaction' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'outlet_ids' => ['nullable', 'array'],
            'product_ids' => ['nullable', 'array'],
            'category_ids' => ['nullable', 'array'],
        ]);

        $discount = Discount::query()->create($data + ['is_active' => true]);
        $discount->outlets()->sync($request->input('outlet_ids', []));
        foreach ($request->input('product_ids', []) as $id) {
            $discount->items()->create(['product_id' => $id]);
        }
        foreach ($request->input('category_ids', []) as $id) {
            $discount->items()->create(['category_id' => $id]);
        }

        return back()->with('success', 'Diskon dibuat.');
    }

    public function toggleDiscount(Discount $discount): RedirectResponse
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('success', 'Status diskon diperbarui.');
    }

    public function bundles(): View
    {
        abort_unless(auth()->user()->hasPermission('marketing.view'), 403);

        return view('marketing.bundles', [
            'bundles' => Bundle::query()->with('items.product')->latest()->paginate(20),
            'products' => Product::query()->sellable()->orderBy('name')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->get(),
        ]);
    }

    public function storeBundle(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('marketing.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'outlet_ids' => ['nullable', 'array'],
        ]);
        $items = $data['items'];
        unset($data['items'], $data['outlet_ids']);
        $bundle = Bundle::query()->create($data + ['is_active' => true]);
        foreach ($items as $item) {
            $bundle->items()->create($item);
        }
        $bundle->outlets()->sync($request->input('outlet_ids', []));

        return back()->with('success', 'Bundle dibuat.');
    }

    public function rewards(): View
    {
        abort_unless(auth()->user()->hasPermission('loyalty.view'), 403);

        return view('loyalty.rewards', [
            'rewards' => Reward::query()->latest()->paginate(20),
        ]);
    }

    public function storeReward(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('loyalty.manage'), 403);
        Reward::query()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'points_required' => ['required', 'integer', 'min:1'],
            'value' => ['nullable', 'numeric', 'min:0'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Reward ditambahkan.');
    }
}
