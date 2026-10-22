<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable([
    'product_id', 'is_active', 'notes',
])]
class Bom extends Model
{
    use AppliesFillableAttribute, SoftDeletes;

    protected function casts(): array
    {
        return [
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

    public function toModalArray(): array
    {
        $this->loadMissing(['product', 'items.component.unit', 'items.unit']);

        $items = $this->items
            ->values()
            ->map(function (BomItem $item) {
                $unit = $item->unit?->code ?? $item->component?->unit?->code ?? '';
                $qty = (float) $item->quantity;

                return [
                    'id' => $item->id,
                    'component_id' => (string) $item->component_id,
                    'unit_id' => (string) ($item->unit_id ?? ''),
                    'quantity' => (string) $qty,
                    'waste_percentage' => (string) (float) ($item->waste_percentage ?? 0),
                    'yield_percentage' => (string) (float) ($item->yield_percentage ?? 100),
                    'component_name' => $item->component?->name ?? '—',
                    'sku' => $item->component?->sku ?? '—',
                    'unit' => $unit !== '' ? $unit : '—',
                    'quantity_label' => qty_label($qty, $unit),
                    'waste_label' => qty_label($item->waste_percentage ?? 0).'%',
                    'yield_label' => qty_label($item->yield_percentage ?? 100).'%',
                ];
            })
            ->all();

        $exploded = collect(app(\App\Services\ProductionService::class)->explode($this, 1))
            ->map(fn (array $row) => [
                'level' => (int) ($row['level'] ?? 1),
                'name' => $row['name'] ?? '—',
                'unit' => $row['unit'] ?? '—',
                'quantity_label' => qty_label((float) ($row['quantity'] ?? 0), (string) ($row['unit'] ?? '')),
            ])
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'product_id' => (string) $this->product_id,
            'product_name' => $this->product?->name ?? '—',
            'sku' => $this->product?->sku ?? '—',
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Aktif' : 'Nonaktif',
            'notes' => $this->notes ?: '',
            'notes_label' => $this->notes ?: '—',
            'items_count' => count($items),
            'items' => $items,
            'exploded' => $exploded,
            'update_url' => route('boms.update', $this),
        ];
    }
}
