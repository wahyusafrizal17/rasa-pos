<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'number', 'source_outlet_id', 'destination_outlet_id', 'requested_by', 'approved_by',
    'received_by', 'status', 'transfer_date', 'notes', 'shipped_at', 'received_at',
])]
class StockTransfer extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'transfer_date' => 'date',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function sourceOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'source_outlet_id');
    }

    public function destinationOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'destination_outlet_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
