<?php

namespace App\Http\Middleware;

use App\Models\Outlet;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentOutlet
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->filled('switch_outlet') && $user->canAccessOutlet((int) $request->input('switch_outlet'))) {
            session(['current_outlet_id' => (int) $request->input('switch_outlet')]);
        }

        $current = current_outlet_id();
        $exists = $current && Outlet::query()->whereKey($current)->exists();
        if (! $exists) {
            $outlet = $user->defaultOutlet()
                ?? Outlet::query()->where('is_active', true)->first();
            session(['current_outlet_id' => $outlet?->id]);
        } elseif (! $user->canAccessOutlet($current) && ! $user->isSuperAdmin() && ! $user->hasRole('admin')) {
            session(['current_outlet_id' => $user->defaultOutlet()?->id]);
        }

        return $next($request);
    }
}
