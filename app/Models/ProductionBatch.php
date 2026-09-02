<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'batch_number', 'production_order_id', 'product_id', 'outlet_id',
    'quantity', 'yield_quantity', 'remaining_quantity', 'produced_at', 'expires_at', 'destination_outlet_id',
])]
class ProductionBatch extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'yield_quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'produced_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function destinationOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'destination_outlet_id');
    }

    public function salesItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'batch_id');
    }
}
