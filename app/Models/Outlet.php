<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'city', 'address', 'phone', 'is_central_kitchen', 'is_active', 'opens_at', 'closes_at'])]
class Outlet extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_central_kitchen' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'outlet_users')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'outlet_product')
            ->withPivot(['price', 'is_available']);
    }
}
