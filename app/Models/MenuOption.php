<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable(['group', 'name', 'price', 'sort_order'])]
class MenuOption extends Model
{
    use AppliesFillableAttribute;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function toModalArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'update_url' => route('catalog.choices.update', [$this->group, $this]),
            'delete_url' => route('catalog.choices.destroy', [$this->group, $this]),
        ];
    }
}
