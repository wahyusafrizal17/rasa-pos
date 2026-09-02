<?php

namespace App\Http\Controllers;

use App\Enums\PrinterStation;
use App\Models\Category;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('printers.view'), 403);

        return view('printers.index', [
            'printers' => Printer::query()->with(['outlet', 'routes.category'])->where('outlet_id', current_outlet_id())->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'stations' => PrinterStation::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('printers.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'station' => ['required', 'in:cashier,kitchen,bar'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['outlet_id'] = current_outlet_id();
        $printer = Printer::query()->create($data);

        foreach ($request->input('category_ids', []) as $categoryId) {
            $printer->routes()->create(['category_id' => $categoryId, 'station' => $data['station']]);
        }

        return back()->with('success', 'Printer dikonfigurasi.');
    }

    public function update(Request $request, Printer $printer): RedirectResponse
    {
        $printer->update($request->validate([
            'name' => ['required', 'string', 'max:80'],
            'station' => ['required', 'in:cashier,kitchen,bar'],
            'ip_address' => ['nullable', 'ip'],
            'port' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', $printer->is_active)]);

        return back()->with('success', 'Printer diperbarui.');
    }

    public function destroy(Printer $printer): RedirectResponse
    {
        $printer->delete();

        return back()->with('success', 'Printer dihapus.');
    }
}
