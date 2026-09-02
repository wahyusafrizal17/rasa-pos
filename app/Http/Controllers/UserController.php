<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('users.view'), 403);

        return view('users.index', [
            'users' => User::query()->with(['roles', 'outlets'])->latest()->paginate(20),
            'roles' => Role::query()->orderBy('label')->get(),
            'outlets' => Outlet::query()->where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'outlet_ids' => ['nullable', 'array'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);
        $user->roles()->sync([$data['role_id']]);
        $user->outlets()->sync($request->input('outlet_ids', []));

        return back()->with('success', 'Pengguna dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
            'outlet_ids' => ['nullable', 'array'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active', $user->is_active),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->roles()->sync([$data['role_id']]);
        $user->outlets()->sync($request->input('outlet_ids', []));

        return back()->with('success', 'Pengguna diperbarui.');
    }
}
