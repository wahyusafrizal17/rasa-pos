<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Reward;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('loyalty.view'), 403);

        return view('loyalty.index', [
            'customers' => Customer::query()->orderByDesc('points')->paginate(20),
            'rewards' => Reward::query()->where('is_active', true)->get(),
        ]);
    }
}
