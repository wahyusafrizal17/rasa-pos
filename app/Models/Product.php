<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'sku', 'name', 'category_id', 'unit_id', 'type', 'bom_level', 'description', 'image',
    'price', 'cost', 'is_sellable', 'is_stockable', 'is_active',
    'minimum_stock', 'reorder_level', 'maximum_stock', 'station', 'prep_minutes',
])]
class Product extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_sellable' => 'boolean',
            'is_stockable' => 'boolean',
            'is_active' => 'boolean',
            'minimum_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'maximum_stock' => 'decimal:3',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function boms(): HasMany
    {
        return $this->hasMany(Bom::class);
    }

    public function activeBom(): ?Bom
    {
        return $this->boms()->where('is_active', true)->latest('id')->first();
    }

    public function scopeSellable(Builder $query): Builder
    {
        return $query->where('is_sellable', true)->where('is_active', true);
    }

    public function stockFor(?int $outletId): float
    {
        if (! $outletId) {
            return 0;
        }

        return (float) ($this->inventories()->where('outlet_id', $outletId)->value('quantity') ?? 0);
    }

    public function isLowStock(?int $outletId = null): bool
    {
        $outletId = $outletId ?? current_outlet_id();
        $stock = $this->stockFor($outletId);

        return $this->is_stockable && $this->reorder_level > 0 && $stock <= (float) $this->reorder_level;
    }

    public function imageUrl(): string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
                return $this->image;
            }

            return asset('storage/'.$this->image);
        }

        return asset('images/menu/placeholder.svg');
    }

    public function menuDescription(): string
    {
        $description = trim((string) $this->description);

        if ($description !== '' && strcasecmp($description, $this->name) !== 0) {
            return $description;
        }

        return $this->name;
    }
}
