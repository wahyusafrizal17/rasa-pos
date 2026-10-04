<?php

use App\Models\Outlet;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

function money(float|int|string|null $amount): string
{
    return 'Rp '.number_format((float) ($amount ?? 0), 0, ',', '.');
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
