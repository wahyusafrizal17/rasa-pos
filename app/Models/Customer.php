<?php

namespace App\Models;

use App\Enums\MembershipLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'phone', 'email', 'birthday', 'gender', 'address',
    'membership_level', 'points', 'total_transaction', 'last_transaction_at', 'is_active',
])]
class Customer extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'membership_level' => MembershipLevel::class,
            'total_transaction' => 'decimal:2',
            'last_transaction_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(CustomerPoint::class);
    }

    public function refreshMembership(): void
    {
        $spending = (float) $this->total_transaction;
        $level = MembershipLevel::Regular;

        foreach ([MembershipLevel::Platinum, MembershipLevel::Gold, MembershipLevel::Silver] as $candidate) {
            if ($spending >= $candidate->minSpending()) {
                $level = $candidate;
                break;
            }
        }

        $this->update(['membership_level' => $level]);
    }
}
