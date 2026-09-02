<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('audit.view'), 403);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->module, fn ($q, $m) => $q->where('module', $m))
            ->when($request->search, fn ($q, $s) => $q->where('action', 'like', "%{$s}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('audit.index', ['logs' => $logs]);
    }
}
