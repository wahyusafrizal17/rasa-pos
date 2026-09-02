<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id', 'version', 'yield_percentage', 'waste_percentage',
    'active_from', 'active_until', 'is_active', 'notes',
])]
class Bom extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'yield_percentage' => 'decimal:2',
            'waste_percentage' => 'decimal:2',
            'active_from' => 'date',
            'active_until' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }
}
