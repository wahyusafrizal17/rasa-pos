<?php

namespace App\Models;

use App\Enums\PrinterStation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['outlet_id', 'name', 'station', 'ip_address', 'port', 'is_active'])]
class Printer extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'station' => PrinterStation::class,
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(PrinterRoute::class);
    }
}
