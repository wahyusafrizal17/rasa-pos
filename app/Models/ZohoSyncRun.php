<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable([
    'status', 'trigger', 'started_at', 'finished_at',
    'outlets', 'products', 'created_count', 'updated_count', 'removed_count', 'stocks',
    'details', 'error',
])]
class ZohoSyncRun extends Model
{
    use AppliesFillableAttribute;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'details' => 'array',
            'outlets' => 'integer',
            'products' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'removed_count' => 'integer',
            'stocks' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'running' => 'Berjalan',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            default => $this->status,
        };
    }

    public function triggerLabel(): string
    {
        return match ($this->trigger) {
            'web' => 'Tombol web',
            'schedule' => 'Jadwal',
            default => 'Terminal',
        };
    }

    public function summaryLabel(): string
    {
        return sprintf(
            '+%d · ubah %d · hapus %d',
            $this->created_count,
            $this->updated_count,
            $this->removed_count,
        );
    }
}
