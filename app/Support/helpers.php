<?php

use App\Models\Outlet;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function money(float|int|string|null $amount): string
{
    return 'Rp '.number_format((float) ($amount ?? 0), 0, ',', '.');
}

/** Tampilkan waktu tersimpan UTC sebagai WIB. */
function wib(\DateTimeInterface|string|null $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $wall = $value instanceof \DateTimeInterface
        ? $value->format('Y-m-d H:i:s')
        : (string) $value;

    return Carbon::parse($wall, 'UTC')->timezone('Asia/Jakarta')->format($format);
}

/** Awal & akhir hari WIB sebagai UTC (untuk filter whereBetween). */
function wib_day_bounds(\DateTimeInterface|string|null $day = null): array
{
    $local = $day
        ? Carbon::parse($day, 'Asia/Jakarta')->timezone('Asia/Jakarta')
        : Carbon::now('Asia/Jakarta');

    return [
        $local->copy()->startOfDay()->utc(),
        $local->copy()->endOfDay()->utc(),
    ];
}

function money_decimal(float|int|string|null $amount): string
{
    return number_format((float) ($amount ?? 0), 2, ',', '.');
}

function qty_label(float|int|string|null $value, string $unit = ''): string
{
    $n = (float) ($value ?? 0);
    $label = abs($n - round($n)) < 0.0005
        ? (string) (int) round($n)
        : rtrim(rtrim(sprintf('%.3f', $n), '0'), '.');

    $unit = trim($unit);

    return $unit !== '' && $unit !== '—' ? $label.' '.$unit : $label;
}

function current_outlet_id(): ?int
{
    $id = session('current_outlet_id');

    return $id ? (int) $id : null;
}

function current_outlet(): ?Outlet
{
    $id = current_outlet_id();

    if (! $id) {
        return null;
    }

    return Outlet::query()->find($id);
}

function setting(string $key, mixed $default = null, ?int $outletId = null): mixed
{
    $outletId = $outletId ?? current_outlet_id();
    $cacheKey = "setting.{$outletId}.{$key}";

    return Cache::remember($cacheKey, 120, function () use ($key, $default, $outletId) {
        $row = Setting::query()
            ->where('key', $key)
            ->where(function ($q) use ($outletId) {
                $q->whereNull('outlet_id');
                if ($outletId) {
                    $q->orWhere('outlet_id', $outletId);
                }
            })
            ->orderByRaw('outlet_id is null')
            ->first();

        return $row?->value ?? $default;
    });
}

function tax_rate(): float
{
    return (float) setting('tax_rate', 11);
}

function service_charge_rate(): float
{
    return (float) setting('service_charge', 0);
}

function integration_setting_map(): array
{
    return [
        'qris_api_key' => 'qris.api_key',
        'qris_base_url' => 'qris.base_url',
        'qris_callback_token' => 'qris.callback_token',
        'zoho_client_id' => 'zoho.client_id',
        'zoho_client_secret' => 'zoho.client_secret',
        'zoho_refresh_token' => 'zoho.refresh_token',
        'zoho_organization_id' => 'zoho.organization_id',
        'zoho_token_url' => 'zoho.token_url',
        'zoho_base_url' => 'zoho.base_url',
        'zoho_customer_id' => 'zoho.customer_id',
        'zoho_cf_source' => 'zoho.cf_source',
    ];
}

function apply_integration_settings(): void
{
    foreach (integration_setting_map() as $settingKey => $configKey) {
        $value = setting($settingKey, '');
        if ($value === null || $value === '') {
            continue;
        }

        config([$configKey => $value]);
    }
}
