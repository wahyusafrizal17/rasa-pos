<?php

namespace App\Models;

use App\Enums\ProductionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'number', 'outlet_id', 'product_id', 'bom_id', 'user_id', 'quantity_planned',
    'quantity_produced', 'yield_percentage', 'production_date', 'batch_number',
    'status', 'notes', 'started_at', 'completed_at',
])]
class ProductionOrder extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ProductionStatus::class,
            'quantity_planned' => 'decimal:3',
            'quantity_produced' => 'decimal:3',
            'yield_percentage' => 'decimal:2',
            'production_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(ProductionBatch::class);
    }
}
