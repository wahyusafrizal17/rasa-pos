<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable([
    'outlet_id', 'label', 'opened_by', 'opened_at', 'scheduled_end', 'opening_note',
    'closed_by', 'closed_at', 'closing_note', 'summary',
])]
class Shift extends Model
{
    use AppliesFillableAttribute;

    public const LABELS = ['Shift 1', 'Shift 2', 'Shift 3'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'scheduled_end' => 'datetime',
            'closed_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    public static function activeFor(?int $outletId): ?self
    {
        if (! $outletId) {
            return null;
        }

        return static::query()->where('outlet_id', $outletId)->open()->latest('opened_at')->first();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
