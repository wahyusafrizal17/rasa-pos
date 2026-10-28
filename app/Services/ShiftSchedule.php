<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Jadwal shift per outlet (fallback ke default global), jam dalam WIB.
 * Disimpan di settings key "shift_schedule" sebagai JSON [{label, start, end}].
 */
class ShiftSchedule
{
    public const KEY = 'shift_schedule';

    protected const TZ = 'Asia/Jakarta';

    /** @return list<array{label: string, start: string, end: string}> */
    public function forOutlet(?int $outletId): array
    {
        $row = Setting::query()
            ->where('key', self::KEY)
            ->where(fn ($q) => $q->whereNull('outlet_id')->when($outletId, fn ($q) => $q->orWhere('outlet_id', $outletId)))
            ->orderByRaw('outlet_id is null')
            ->first();

        return $this->decode($row?->value);
    }

    /** null = belum diatur khusus (ikut default). */
    public function stored(?int $outletId): ?array
    {
        $value = Setting::query()->where('key', self::KEY)->where('outlet_id', $outletId)->value('value');

        return $value === null ? null : $this->decode($value);
    }

    public function save(?int $outletId, array $rows): void
    {
        Setting::query()->updateOrCreate(
            ['key' => self::KEY, 'outlet_id' => $outletId],
            ['value' => json_encode(array_values($rows)), 'group' => 'shift'],
        );
    }

    public function reset(int $outletId): void
    {
        Setting::query()->where('key', self::KEY)->where('outlet_id', $outletId)->delete();
    }

    /**
     * Shift yang sedang berlangsung pada $at. Mendukung shift lewat tengah malam (mis. 23:00–07:00).
     *
     * @return array{label: string, start: Carbon, end: Carbon}|null
     */
    public function windowAt(array $rows, Carbon $at): ?array
    {
        $local = $at->copy()->timezone(self::TZ);

        foreach ($rows as $row) {
            foreach ([$local->copy()->subDay(), $local] as $day) {
                $start = $this->at($day, $row['start']);
                $end = $this->at($day, $row['end']);
                if ($end->lte($start)) {
                    $end->addDay();
                }
                if ($local->gte($start) && $local->lt($end)) {
                    return ['label' => $row['label'], 'start' => $this->app($start), 'end' => $this->app($end)];
                }
            }
        }

        return null;
    }

    /** Jam selesai berikutnya untuk $label setelah $from. */
    public function endFor(array $rows, string $label, Carbon $from): ?Carbon
    {
        $row = collect($rows)->firstWhere('label', $label);
        if (! $row) {
            return null;
        }

        $local = $from->copy()->timezone(self::TZ);
        $end = $this->at($local, $row['end']);
        if ($end->lte($local)) {
            $end->addDay();
        }

        return $this->app($end);
    }

    protected function at(Carbon $day, string $time): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $day->copy()->setTime($hour, $minute);
    }

    protected function app(Carbon $value): Carbon
    {
        return $value->copy()->timezone(config('app.timezone'));
    }

    protected function decode(?string $value): array
    {
        $rows = json_decode((string) $value, true);

        return is_array($rows)
            ? array_values(array_filter($rows, fn ($row) => isset($row['label'], $row['start'], $row['end'])))
            : [];
    }
}
