<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\AppliesFillableAttribute;

#[Fillable(['outlet_id', 'user_id', 'tanggal', 'tipe', 'item', 'keterangan', 'jumlah', 'bukti_path', 'bukti_mime'])]
class KasTransaksi extends Model
{
    use AppliesFillableAttribute;

    protected $table = 'kas_transaksi';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function isMasuk(): bool
    {
        return $this->tipe === 'masuk';
    }

    public function delta(): int
    {
        return $this->isMasuk() ? $this->jumlah : -$this->jumlah;
    }

    public function buktiIsPdf(): bool
    {
        return $this->bukti_mime === 'application/pdf';
    }
}
