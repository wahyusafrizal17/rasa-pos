<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Reward;
use App\Services\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('customers.view'), 403);

        $customers = Customer::query()
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        return view('customers.form', ['customer' => new Customer(['membership_level' => 'regular', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('customers.manage'), 403);
        $data = $this->validated($request);
        $data['code'] = 'CUS-'.strtoupper(Str::random(6));
        Customer::query()->create($data);

        return redirect()->route('customers.index')->with('success', 'Pelanggan ditambahkan.');
    }

    public function show(Customer $customer): View
    {
        abort_unless(auth()->user()->hasPermission('customers.view'), 403);

        $favorites = $customer->orders()
            ->where('payment_status', 'paid')
            ->with('items')
            ->get()
            ->flatMap->items
            ->groupBy('name')
            ->map(fn ($items) => $items->sum('quantity'))
            ->sortDesc()
            ->take(5);

        return view('customers.show', [
            'customer' => $customer->load(['orders' => fn ($q) => $q->latest()->limit(20), 'pointLedgers' => fn ($q) => $q->latest()->limit(20)]),
            'favorites' => $favorites,
            'rewards' => Reward::query()->where('is_active', true)->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        return view('customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('customers.manage'), 403);
        $customer->update($this->validated($request, $customer->id));

        return redirect()->route('customers.show', $customer)->with('success', 'Pelanggan diperbarui.');
    }

    public function adjustPoints(Request $request, Customer $customer, LoyaltyService $loyalty): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('loyalty.manage'), 403);
        $data = $request->validate([
            'points' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $loyalty->adjust($customer, (int) $data['points'], 'adjustment', $data['reason']);

        return back()->with('success', 'Poin diperbarui.');
    }

    public function redeemReward(Request $request, Customer $customer, LoyaltyService $loyalty): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('loyalty.manage'), 403);
        $data = $request->validate(['reward_id' => ['required', 'exists:rewards,id']]);
        $loyalty->redeemReward($customer, Reward::query()->findOrFail($data['reward_id']));

        return back()->with('success', 'Reward ditukarkan.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'address' => ['nullable', 'string'],
            'membership_level' => ['nullable', 'in:regular,silver,gold,platinum'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
