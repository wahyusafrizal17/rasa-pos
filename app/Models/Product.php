<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable([
    'sku', 'zoho_item_id', 'name', 'category_id', 'unit_id', 'type', 'bom_level', 'description', 'image',
    'price', 'cost', 'is_sellable', 'is_addon', 'has_optional', 'has_expresso', 'is_stockable', 'is_active',
    'minimum_stock', 'reorder_level', 'maximum_stock', 'station', 'prep_minutes',
])]
class Product extends Model
{
    use AppliesFillableAttribute, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'is_sellable' => 'boolean',
            'is_addon' => 'boolean',
            'has_optional' => 'boolean',
            'has_expresso' => 'boolean',
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

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_addon', 'product_id', 'addon_id');
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

    public function statusLabel(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function toModalArray(): array
    {
        $this->loadMissing(['category', 'unit', 'variants', 'addons']);

        return [
            'id' => $this->id,
            'sku' => $this->sku ?? '',
            'name' => $this->name ?? '',
            'category_id' => $this->category_id ? (string) $this->category_id : '',
            'category_label' => $this->category?->name ?? '—',
            'unit_id' => $this->unit_id ? (string) $this->unit_id : '',
            'unit_label' => $this->unit ? $this->unit->name.' ('.$this->unit->code.')' : '—',
            'type' => $this->type?->value ?? ProductType::Finished->value,
            'type_label' => $this->type?->label() ?? '—',
            'image' => $this->image ?? '',
            'image_url' => $this->imageUrl(),
            'price' => (float) $this->price,
            'price_label' => money($this->price),
            'cost' => (float) ($this->cost ?? 0),
            'cost_label' => money($this->cost),
            'is_sellable' => (bool) $this->is_sellable,
            'is_addon' => (bool) $this->is_addon,
            'addon_ids' => $this->addons->pluck('id')->map(fn ($id) => (string) $id)->values()->all(),
            'is_stockable' => (bool) $this->is_stockable,
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->statusLabel(),
            'reorder_level' => (float) ($this->reorder_level ?? 0),
            'variants' => $this->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku ?? '',
                'price_adjustment' => (float) $variant->price_adjustment,
                'price_adjustment_label' => money($variant->price_adjustment),
            ])->values()->all(),
            'update_url' => route('products.update', $this),
            'delete_url' => route('products.destroy', $this),
        ];
    }
}
