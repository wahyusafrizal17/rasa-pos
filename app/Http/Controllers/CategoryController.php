<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);

        return view('categories.index', [
            'categories' => Category::query()->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'station' => ['nullable', 'in:kitchen,bar,cashier'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4));
        $data['is_active'] = true;
        Category::query()->create($data);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'station' => ['nullable', 'in:kitchen,bar,cashier'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', $category->is_active)]);

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('products.manage'), 403);
        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
