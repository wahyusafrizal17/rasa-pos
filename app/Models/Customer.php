<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable([
    'code', 'name', 'phone', 'email', 'birthday', 'gender', 'address',
    'total_transaction', 'last_transaction_at', 'is_active',
])]
class Customer extends Model
{
    use AppliesFillableAttribute, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'total_transaction' => 'decimal:2',
            'last_transaction_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toModalArray(): array
    {
        $genderLabels = ['male' => 'Laki-laki', 'female' => 'Perempuan', 'other' => 'Lainnya'];

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone ?? '',
            'email' => $this->email ?? '',
            'birthday' => $this->birthday?->format('Y-m-d') ?? '',
            'birthday_label' => $this->birthday?->format('d/m/Y') ?? '—',
            'gender' => $this->gender ?? '',
            'gender_label' => $genderLabels[$this->gender ?? ''] ?? '—',
            'address' => $this->address ?? '',
            'is_active' => (bool) $this->is_active,
            'total_transaction_label' => money($this->total_transaction),
            'last_transaction_label' => $this->last_transaction_at?->format('d/m/Y') ?? '—',
            'update_url' => route('customers.update', $this),
            'delete_url' => route('customers.destroy', $this),
        ];
    }
}
