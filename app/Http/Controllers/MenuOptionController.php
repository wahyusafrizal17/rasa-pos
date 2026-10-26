<?php

namespace App\Http\Controllers;

use App\Models\MenuOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuOptionController extends Controller
{
    public function index(Request $request, string $group): View
    {
        abort_unless($request->user()->hasPermission('products.view'), 403);
        $group = $this->group($group);
        $options = MenuOption::query()->where('group', $group)->orderBy('sort_order')->orderBy('id')->get();

        return view('catalog.choices', [
            'group' => $group,
            'label' => $group === 'optional' ? 'Optional' : 'Expresso',
            'options' => $options,
        ]);
    }

    public function store(Request $request, string $group): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $group = $this->group($group);
        $data = $this->validated($request);
        $data['group'] = $group;
        $data['sort_order'] = (int) MenuOption::query()->where('group', $group)->max('sort_order') + 1;
        MenuOption::query()->create($data);

        return redirect()->route('catalog.choices', $group)->with('success', 'Pilihan ditambahkan.');
    }

    public function update(Request $request, string $group, MenuOption $option): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $group = $this->group($group);
        abort_unless($option->group === $group, 404);
        $option->update($this->validated($request));

        return redirect()->route('catalog.choices', $group)->with('success', 'Pilihan diperbarui.');
    }

    public function destroy(Request $request, string $group, MenuOption $option): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('products.manage'), 403);
        $group = $this->group($group);
        abort_unless($option->group === $group, 404);
        $option->delete();

        return redirect()->route('catalog.choices', $group)->with('success', 'Pilihan dihapus.');
    }

    protected function group(string $group): string
    {
        abort_unless(in_array($group, ['optional', 'expresso'], true), 404);

        return $group;
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
        $data['price'] = $data['price'] ?? 0;

        return $data;
    }
}
