<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    protected array $keys = [
        'tax_rate',
        'service_charge',
        'company_name',
        'receipt_footer',
        'qz_printer',
        'transfer_bank',
        'transfer_account_number',
        'transfer_account_name',
        'qris_api_key',
        'qris_base_url',
        'qris_callback_token',
        'zoho_client_id',
        'zoho_client_secret',
        'zoho_refresh_token',
        'zoho_organization_id',
        'zoho_token_url',
        'zoho_base_url',
        'zoho_customer_id',
        'zoho_cf_source',
    ];

    protected array $defaults = [
        'tax_rate' => 11,
        'service_charge' => 0,
        'company_name' => 'Rasa',
        'receipt_footer' => 'Terima kasih',
        'qz_printer' => '',
        'transfer_bank' => '',
        'transfer_account_number' => '',
        'transfer_account_name' => '',
        'qris_api_key' => '',
        'qris_base_url' => 'https://rasaconnect.com',
        'qris_callback_token' => '',
        'zoho_client_id' => '',
        'zoho_client_secret' => '',
        'zoho_refresh_token' => '',
        'zoho_organization_id' => '',
        'zoho_token_url' => 'https://accounts.zoho.com/oauth/v2/token',
        'zoho_base_url' => 'https://www.zohoapis.com',
        'zoho_customer_id' => '',
        'zoho_cf_source' => 'POS',
    ];

    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $defaults = $this->defaults;
        foreach (integration_setting_map() as $key => $configKey) {
            $cfg = config($configKey);
            $defaults[$key] = is_bool($cfg) ? ($cfg ? '1' : '0') : (string) ($cfg ?? $defaults[$key] ?? '');
        }

        $stored = Setting::query()->whereNull('outlet_id')->whereIn('key', $this->keys)->pluck('value', 'key');
        $settings = collect($this->keys)->mapWithKeys(fn ($key) => [
            $key => old($key, $stored[$key] ?? $defaults[$key] ?? ''),
        ]);

        return view('settings.index', [
            'settings' => $settings,
            'stats' => [
                'tax_rate' => (float) $settings['tax_rate'],
                'service_charge' => (float) $settings['service_charge'],
                'company_name' => (string) $settings['company_name'],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);
        $data = $request->validate([
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'service_charge' => ['nullable', 'numeric', 'min:0'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'qz_printer' => ['nullable', 'string', 'max:120'],
            'transfer_bank' => ['nullable', 'string', 'max:80'],
            'transfer_account_number' => ['nullable', 'string', 'max:40'],
            'transfer_account_name' => ['nullable', 'string', 'max:120'],
            'qris_api_key' => ['nullable', 'string', 'max:120'],
            'qris_base_url' => ['nullable', 'url', 'max:255'],
            'qris_callback_token' => ['nullable', 'string', 'max:120'],
            'zoho_client_id' => ['nullable', 'string', 'max:120'],
            'zoho_client_secret' => ['nullable', 'string', 'max:120'],
            'zoho_refresh_token' => ['nullable', 'string', 'max:255'],
            'zoho_organization_id' => ['nullable', 'string', 'max:40'],
            'zoho_token_url' => ['nullable', 'url', 'max:255'],
            'zoho_base_url' => ['nullable', 'url', 'max:255'],
            'zoho_customer_id' => ['nullable', 'string', 'max:40'],
            'zoho_cf_source' => ['nullable', 'string', 'max:40'],
        ], [
            'tax_rate.required' => 'Tarif pajak wajib diisi.',
            'tax_rate.max' => 'Tarif pajak maksimal 100%.',
        ]);

        foreach ($data as $key => $value) {
            Setting::query()->updateOrCreate(
                ['outlet_id' => null, 'key' => $key],
                ['value' => $value, 'group' => 'general']
            );
            Cache::forget("setting..{$key}");
            Cache::forget('setting.'.current_outlet_id().".{$key}");
        }

        apply_integration_settings();

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
