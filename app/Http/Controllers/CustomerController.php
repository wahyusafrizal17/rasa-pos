<?php

namespace App\Http\Controllers;

use App\Exports\GenericExport;
use App\Imports\CustomerImport;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View|BinaryFileResponse|\Illuminate\Http\Response
    {
        abort_unless($request->user()->hasPermission('customers.view'), 403);

        $filters = $request->only(['code', 'name', 'phone', 'email', 'address']);
        $query = $this->filteredQuery($request);

        if ($request->export === 'xlsx') {
            $rows = $query->get()->map(fn (Customer $customer) => [
                $customer->code,
                $customer->name,
                $customer->phone,
                $customer->email,
                $customer->address,
            ])->all();

            return Excel::download(new GenericExport(
                ['Kode', 'Nama', 'No. HP', 'Email', 'Alamat'],
                $rows,
            ), 'pelanggan.xlsx');
        }

        if ($request->export === 'pdf') {
            return Pdf::loadView('customers.pdf', ['rows' => $query->get()])->download('pelanggan.pdf');
        }

        $customers = $query
            ->paginate(20)
            ->withQueryString();

        $focusCustomer = $request->filled('customer')
            ? Customer::query()->find($request->integer('customer'))
            : null;

        return view('customers.index', [
            'customers' => $customers,
            'filters' => $filters,
            'stats' => [
                'total' => Customer::query()->count(),
                'phone' => Customer::query()->whereNotNull('phone')->where('phone', '!=', '')->count(),
                'email' => Customer::query()->whereNotNull('email')->where('email', '!=', '')->count(),
            ],
            'focusPayload' => $focusCustomer?->toModalArray(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('customers.manage'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new CustomerImport, $request->file('file'));

        return redirect()->route('customers.index')->with('success', 'Data pelanggan diimpor.');
    }

    public function template(): BinaryFileResponse
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        return Excel::download(new GenericExport(
            ['Kode', 'Nama', 'No. HP', 'Email', 'Alamat'],
            [
                ['CUS-010', 'Contoh Pelanggan', '081234567890', 'contoh@example.com', 'Jl. Contoh No. 1'],
                ['', 'Pelanggan Baru', '081234567891', '', ''],
            ],
        ), 'template-pelanggan.xlsx');
    }

    public function create(): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        return redirect()->route('customers.index', ['modal' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('customers.manage'), 403);
        $data = $this->validated($request);
        $data['code'] = 'CUS-'.strtoupper(Str::random(6));
        Customer::query()->create($data);

        return redirect()->route('customers.index')->with('success', 'Pelanggan ditambahkan.');
    }

    public function show(Customer $customer): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('customers.view'), 403);

        return redirect()->route('customers.index', ['modal' => 'view', 'customer' => $customer->id]);
    }

    public function edit(Customer $customer): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        return redirect()->route('customers.index', ['modal' => 'edit', 'customer' => $customer->id]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('customers.manage'), 403);
        $customer->update($this->validated($request, $customer->id));

        return redirect()->route('customers.index')->with('success', 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Pelanggan dihapus.');
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
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 150 karakter.',
            'email.email' => 'Format email tidak valid.',
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    protected function filteredQuery(Request $request): Builder
    {
        return Customer::query()
            ->when($request->filled('code'), fn ($q) => $q->where('code', 'like', '%'.$request->string('code').'%'))
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('phone'), fn ($q) => $q->where('phone', 'like', '%'.$request->string('phone').'%'))
            ->when($request->filled('email'), fn ($q) => $q->where('email', 'like', '%'.$request->string('email').'%'))
            ->when($request->filled('address'), fn ($q) => $q->where('address', 'like', '%'.$request->string('address').'%'))
            ->latest();
    }
}
