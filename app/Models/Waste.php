<?php

namespace App\Models;

use App\Enums\WasteReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['number', 'outlet_id', 'product_id', 'unit_id', 'user_id', 'quantity', 'reason', 'notes', 'status'])]
class Waste extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'reason' => WasteReason::class,
            'quantity' => 'decimal:3',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
