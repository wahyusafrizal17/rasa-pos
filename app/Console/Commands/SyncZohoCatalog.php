<?php

namespace App\Console\Commands;

use App\Services\ZohoCatalogSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncZohoCatalog extends Command
{
    public const LOCK = 'zoho-catalog-sync';

    protected $signature = 'zoho:sync-catalog {--fresh : Hapus data tes lalu isi dari Zoho} {--trigger=cli : Sumber sync: cli|web|schedule}';

    protected $description = 'Sync outlet, kategori, produk, dan stok dari Zoho Inventory';

    public static function acquireLock(int $seconds = 45 * 60): ?\Illuminate\Contracts\Cache\Lock
    {
        $lock = Cache::lock(self::LOCK, $seconds);
        if ($lock->get()) {
            return $lock;
        }

        // ambil alih hanya kalau sync yang memegang kunci tidak memberi tanda hidup 10 menit
        // (sync hidup menyentuh file ini tiap ~40 detik; log biasa ikut berubah oleh percobaan yang dilewati)
        $heartbeat = self::heartbeatPath();
        if (is_file($heartbeat) && filemtime($heartbeat) >= time() - 600) {
            return null;
        }

        $lock->forceRelease();

        return $lock->get() ? $lock : null;
    }

    public static function heartbeatPath(): string
    {
        return storage_path('logs/zoho-sync.heartbeat');
    }

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

        $lock = self::acquireLock();
        if (! $lock) {
            // jangan tulis ke zoho-sync.log: log yang terus berubah membuat kunci sync mati tidak pernah dilepas
            $this->warn('Sync dilewati, masih berjalan. Cek: tail -f storage/logs/zoho-sync.log');

            return self::SUCCESS;
        }

        try {
            if ($this->option('fresh') && $this->input->isInteractive() && ! $this->confirm('Hapus data operasional lokal (order, outlet tes, katalog) lalu isi dari Zoho?', true)) {
                return self::SUCCESS;
            }

            $this->logLine('Mulai sync Zoho (~15 menit). Jangan Ctrl+C.');
            try {
                $summary = $sync->run(
                    fresh: (bool) $this->option('fresh'),
                    onProgress: fn (string $line) => $this->logLine($line),
                    trigger: (string) $this->option('trigger'),
                );
                $this->logLine(
                    'Selesai. Outlets '.$summary['outlets']
                    .' · products '.$summary['products']
                    .' · +'.$summary['created']
                    .' · ubah '.$summary['updated']
                    .' · hapus '.$summary['removed']
                    .' · stock rows '.$summary['stocks']
                );
            } catch (\Throwable $e) {
                $this->logLine('Gagal: '.$e->getMessage());

                return self::FAILURE;
            }
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    protected function logLine(string $line): void
    {
        @touch(self::heartbeatPath());
        $path = storage_path('logs/zoho-sync.log');
        try {
            if ((! is_file($path) && is_writable(dirname($path))) || (is_file($path) && is_writable($path))) {
                file_put_contents($path, now()->toDateTimeString().' '.$line.PHP_EOL, FILE_APPEND);
            }
        } catch (\Throwable) {
            // ignore permission issues on shared hosts
        }
        $this->line($line);
    }
}
