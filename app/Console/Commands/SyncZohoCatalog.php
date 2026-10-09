<?php

namespace App\Console\Commands;

use App\Services\ZohoCatalogSync;
use Illuminate\Console\Command;

class SyncZohoCatalog extends Command
{
    protected $signature = 'zoho:sync-catalog {--fresh : Hapus data tes lalu isi dari Zoho}';

    protected $description = 'Sync outlet, kategori, produk, dan stok dari Zoho Inventory';

    public function handle(ZohoCatalogSync $sync): int
    {
        if ($this->option('fresh') && $this->input->isInteractive() && ! $this->confirm('Hapus data operasional lokal (order, outlet tes, katalog) lalu isi dari Zoho?', true)) {
            return self::SUCCESS;
        }

        $summary = $sync->run(fresh: (bool) $this->option('fresh'));
        $this->info('Outlets '.$summary['outlets'].' · products '.$summary['products'].' · stock rows '.$summary['stocks']);

        return self::SUCCESS;
    }
}
