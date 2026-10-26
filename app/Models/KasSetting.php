<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable(['outlet_id', 'nama', 'saldo_awal', 'batas_minimum'])]
class KasSetting extends Model
{
    use AppliesFillableAttribute;

    protected function casts(): array
    {
        return [
            'saldo_awal' => 'integer',
            'batas_minimum' => 'integer',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public static function current(): self
    {
        $outletId = current_outlet_id();
        abort_unless($outletId, 403);

        return static::query()->firstOrCreate(['outlet_id' => $outletId], [
            'nama' => 'Kas',
            'saldo_awal' => 0,
            'batas_minimum' => 200000,
        ]);
    }
}
