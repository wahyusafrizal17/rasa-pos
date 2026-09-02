<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('inventory.view'), 403);

        return view('transfers.index', [
            'transfers' => StockTransfer::query()->with(['sourceOutlet', 'destinationOutlet', 'requester'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('transfers.form', [
            'outlets' => Outlet::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('is_stockable', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, StockTransferService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $data = $request->validate([
            'source_outlet_id' => ['required', 'exists:outlets,id'],
            'destination_outlet_id' => ['required', 'exists:outlets,id', 'different:source_outlet_id'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);
        $transfer = $service->create($data);

        return redirect()->route('transfers.show', $transfer)->with('success', 'Transfer dibuat.');
    }

    public function show(StockTransfer $transfer): View
    {
        return view('transfers.show', [
            'transfer' => $transfer->load(['items.product.unit', 'sourceOutlet', 'destinationOutlet', 'requester', 'approver', 'receiver']),
        ]);
    }

    public function request(StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        $service->request($transfer);

        return back()->with('success', 'Transfer diajukan.');
    }

    public function approve(StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('inventory.approve'), 403);
        $service->approve($transfer);

        return back()->with('success', 'Transfer disetujui.');
    }

    public function ship(StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        $service->ship($transfer);

        return back()->with('success', 'Transfer dikirim. Stok sumber berkurang.');
    }

    public function receive(Request $request, StockTransfer $transfer, StockTransferService $service): RedirectResponse
    {
        $data = $request->validate([
            'received' => ['nullable', 'array'],
            'received.*' => ['numeric', 'min:0'],
        ]);
        $service->receive($transfer, $data['received'] ?? []);

        return back()->with('success', 'Transfer diterima. Stok tujuan bertambah.');
    }
}
