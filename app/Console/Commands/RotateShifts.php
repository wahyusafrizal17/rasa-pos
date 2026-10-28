<?php

namespace App\Console\Commands;

use App\Models\Outlet;
use App\Services\ShiftService;
use Illuminate\Console\Command;

class RotateShifts extends Command
{
    protected $signature = 'shifts:rotate';

    protected $description = 'Tutup shift yang lewat jadwal dan buka shift sesuai jadwal tiap outlet';

    public function handle(ShiftService $shifts): int
    {
        Outlet::query()->where('is_active', true)->pluck('id')->each(function (int $outletId) use ($shifts) {
            try {
                $shifts->rotate($outletId);
            } catch (\Throwable $e) {
                report($e);
                $this->error("Outlet {$outletId}: {$e->getMessage()}");
            }
        });

        return self::SUCCESS;
    }
}
