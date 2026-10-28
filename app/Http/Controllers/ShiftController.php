<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\Shift;
use App\Services\ShiftSchedule;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('orders.view'), 403);

        $outletId = current_outlet_id();

        return view('shifts.index', [
            'active' => Shift::activeFor($outletId)?->load('opener'),
            'shifts' => Shift::query()
                ->where('outlet_id', $outletId)
                ->with(['opener', 'closer'])
                ->latest('opened_at')
                ->paginate(20),
        ]);
    }

    public function show(Request $request, Shift $shift, ShiftService $shifts): View
    {
        abort_unless($request->user()->hasPermission('orders.view'), 403);
        abort_unless($request->user()->canAccessOutlet($shift->outlet_id), 403);

        return view('shifts.show', [
            'shift' => $shift->load(['outlet', 'opener', 'closer']),
            'report' => $shifts->summary($shift),
        ]);
    }

    public function store(Request $request, ShiftService $shifts): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('pos.access'), 403);

        $data = $request->validate([
            'label' => ['required', Rule::in($shifts->labels(current_outlet_id()))],
            'opening_note' => ['nullable', 'string', 'max:255'],
        ]);

        $shift = $shifts->open(current_outlet_id(), $data['label'], $data['opening_note'] ?? null);

        return redirect()->route('pos.index')->with('success', $shift->label.' dibuka.');
    }

    public function schedule(Request $request, ShiftSchedule $schedule): View
    {
        abort_unless($this->canManageSchedule($request), 403);

        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $outletId = $request->filled('outlet') ? $request->integer('outlet') : null;
        if ($outletId && ! $outlets->contains('id', $outletId)) {
            $outletId = null;
        }

        $default = $schedule->stored(null);
        $own = $outletId ? $schedule->stored($outletId) : $default;

        return view('shifts.schedule', [
            'outlets' => $outlets,
            'outletId' => $outletId,
            'rows' => old('rows', $own ?? $default ?? []),
            'usesDefault' => $outletId && $own === null,
            'default' => $default ?? [],
        ]);
    }

    public function saveSchedule(Request $request, ShiftSchedule $schedule): RedirectResponse
    {
        abort_unless($this->canManageSchedule($request), 403);

        $outletId = $request->filled('outlet_id') ? $request->integer('outlet_id') : null;
        if ($outletId) {
            abort_unless(Outlet::query()->whereKey($outletId)->exists(), 404);
        }
        $back = redirect()->route('shifts.schedule', array_filter(['outlet' => $outletId]));

        if ($outletId && $request->boolean('use_default')) {
            $schedule->reset($outletId);

            return $back->with('success', 'Outlet ini sekarang memakai jadwal default.');
        }

        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:6'],
            'rows.*.label' => ['required', 'string', 'max:40', 'distinct'],
            'rows.*.start' => ['required', 'date_format:H:i'],
            'rows.*.end' => ['required', 'date_format:H:i'],
        ], [
            'rows.required' => 'Minimal satu shift.',
            'rows.*.label.required' => 'Nama shift wajib diisi.',
            'rows.*.label.distinct' => 'Nama shift tidak boleh sama.',
            'rows.*.start.date_format' => 'Jam mulai harus format JJ:MM.',
            'rows.*.end.date_format' => 'Jam selesai harus format JJ:MM.',
        ]);

        $rows = collect($data['rows'])->map(fn ($row) => [
            'label' => trim($row['label']),
            'start' => $row['start'],
            'end' => $row['end'],
        ])->sortBy('start')->values()->all();

        foreach ($rows as $index => $row) {
            if ($row['start'] === $row['end']) {
                return $back->withInput()->withErrors(['rows' => $row['label'].': jam mulai dan selesai tidak boleh sama.']);
            }
            $others = array_values(array_diff_key($rows, [$index => true]));
            $startAt = Carbon::parse('today '.$row['start'], 'Asia/Jakarta');
            if ($clash = $schedule->windowAt($others, $startAt)) {
                return $back->withInput()->withErrors(['rows' => $row['label'].' bertabrakan dengan '.$clash['label'].'.']);
            }
        }

        $schedule->save($outletId, $rows);

        return $back->with('success', 'Jadwal shift disimpan.');
    }

    protected function canManageSchedule(Request $request): bool
    {
        return $request->user()->hasPermission('settings.manage') || $request->user()->hasPermission('outlets.manage');
    }

    public function close(Request $request, Shift $shift, ShiftService $shifts): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('pos.access'), 403);
        abort_unless((int) $shift->outlet_id === (int) current_outlet_id(), 403);

        $data = $request->validate([
            'closing_note' => ['nullable', 'string', 'max:255'],
        ]);

        $shift = $shifts->close($shift, $data['closing_note'] ?? null);

        return redirect()->route('shifts.show', $shift)->with('success', $shift->label.' ditutup.');
    }
}
