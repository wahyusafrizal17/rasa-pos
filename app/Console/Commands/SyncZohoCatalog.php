<?php

namespace App\Console\Commands;

use App\Services\ZohoCatalogSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncZohoCatalog extends Command
{
    public const LOCK = 'zoho-catalog-sync';

    protected $signature = 'zoho:sync-catalog {--fresh : Hapus data tes lalu isi dari Zoho}';

    protected $description = 'Sync outlet, kategori, produk, dan stok dari Zoho Inventory';

    public function handle(ZohoCatalogSync $sync): int
    {
        set_time_limit(0);
        ignore_user_abort(true);
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $missing = collect(['client_id', 'client_secret', 'refresh_token', 'organization_id'])
            ->filter(fn (string $key) => trim((string) config('zoho.'.$key)) === '');
        if ($missing->isNotEmpty()) {
            $this->error('Isi dulu di .env: ZOHO_'.strtoupper($missing->implode(', ZOHO_')));

            return self::FAILURE;
        }

        $lock = Cache::lock(self::LOCK, 45 * 60);
        if (! $lock->get()) {
            $this->logLine('Sync dilewati, masih berjalan.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('fresh') && $this->input->isInteractive() && ! $this->confirm('Hapus data operasional lokal (order, outlet tes, katalog) lalu isi dari Zoho?', true)) {
                return self::SUCCESS;
            }

            $this->logLine('Mulai sync Zoho (~15 menit). Jangan Ctrl+C.');
            $summary = $sync->run(fresh: (bool) $this->option('fresh'), onProgress: fn (string $line) => $this->logLine($line));
            $this->logLine('Selesai. Outlets '.$summary['outlets'].' · products '.$summary['products'].' · stock rows '.$summary['stocks']);
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    protected function logLine(string $line): void
    {
        file_put_contents(
            storage_path('logs/zoho-sync.log'),
            now()->toDateTimeString().' '.$line.PHP_EOL,
            FILE_APPEND,
        );
        $this->line($line);
    }
}
