<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'sku', 'product_id', 'price', 'start_date', 'end_date', 'start_time', 'end_time', 'is_active'])]
class Bundle extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    public function outlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class);
    }

    public function isCurrentlyActive(?int $outletId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->toDateString();
        $time = now()->format('H:i:s');

        if ($this->start_date && $today < $this->start_date->toDateString()) {
            return false;
        }
        if ($this->end_date && $today > $this->end_date->toDateString()) {
            return false;
        }
        if ($this->start_time && $time < $this->start_time) {
            return false;
        }
        if ($this->end_time && $time > $this->end_time) {
            return false;
        }

        if ($outletId && $this->outlets()->exists()) {
            return $this->outlets()->where('outlets.id', $outletId)->exists();
        }

        return true;
    }
}
