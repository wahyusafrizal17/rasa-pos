<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('outlets.view'), 403);

        return view('outlets.index', [
            'outlets' => Outlet::query()->withCount('users')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('outlets.manage'), 403);
        Outlet::query()->create($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:outlets,code'],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_central_kitchen' => ['sometimes', 'boolean'],
            'opens_at' => ['nullable'],
            'closes_at' => ['nullable'],
        ]) + [
            'is_central_kitchen' => $request->boolean('is_central_kitchen'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Outlet ditambahkan.');
    }

    public function update(Request $request, Outlet $outlet): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('outlets.manage'), 403);
        $outlet->update($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:outlets,code,'.$outlet->id],
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_central_kitchen' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'opens_at' => ['nullable'],
            'closes_at' => ['nullable'],
        ]) + [
            'is_central_kitchen' => $request->boolean('is_central_kitchen'),
            'is_active' => $request->boolean('is_active', $outlet->is_active),
        ]);

        return back()->with('success', 'Outlet diperbarui.');
    }
}
