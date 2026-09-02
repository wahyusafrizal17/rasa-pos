<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $keys = ['tax_rate', 'service_charge', 'points_earn_per_amount', 'points_redeem_value', 'company_name', 'receipt_footer'];
        $settings = Setting::query()->whereNull('outlet_id')->whereIn('key', $keys)->pluck('value', 'key');

        return view('settings.index', ['settings' => $settings]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $data = $request->validate([
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge' => ['nullable', 'numeric', 'min:0'],
            'points_earn_per_amount' => ['required', 'integer', 'min:1'],
            'points_redeem_value' => ['required', 'integer', 'min:1'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(
                ['outlet_id' => null, 'key' => $key],
                ['value' => $value, 'group' => 'general']
            );
            Cache::forget("setting..{$key}");
            Cache::forget('setting.'.current_outlet_id().".{$key}");
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
