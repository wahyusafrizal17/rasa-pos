<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ZohoBooksPush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RepushZohoOrders extends Command
{
    public const LOCK = 'zoho-repush-orders';

    public const PROGRESS = 'zoho-repush-orders.progress';

    protected $signature = 'zoho:repush-orders 
        {--reset : Lupakan ID Zoho lama lalu kirim ulang order lunas}
        {--from= : Tanggal transaksi mulai (Y-m-d, WIB)}
        {--to= : Tanggal transaksi sampai (Y-m-d, WIB)}
        {--payment-status= : paid (default), partial, unpaid, atau all}';

    protected $description = 'Kirim ulang order POS yang lunas ke Zoho Books (kontak → SO → Invoice → Payment), satu per satu';

    public function handle(ZohoBooksPush $push): int
    {
        $lock = Cache::lock(self::LOCK, 6 * 3600);
        if (! $lock->get()) {
            $this->warn('Push ulang masih berjalan.');

            return self::FAILURE;
        }

        $filters = array_filter([
            'from' => $this->option('from'),
            'to' => $this->option('to'),
            'payment_status' => $this->option('payment-status'),
        ]);

        try {
            if ($this->option('reset')) {
                $this->info('ID Zoho direset untuk '.$push->resetAll($filters).' order.');
            }

            $ids = $push->pendingQuery($filters)->orderBy('id')->pluck('id');
            $progress = [
                'status' => 'running',
                'reset' => (bool) $this->option('reset'),
                'filters' => $filters,
                'total' => $ids->count(),
                'done' => 0,
                'failed' => 0,
                'errors' => [],
                'started_at' => now()->toIso8601String(),
                'finished_at' => null,
            ];
            Cache::forever(self::PROGRESS, $progress);

            foreach ($ids as $id) {
                $order = Order::query()->find($id);
                try {
                    $order && $push->push($order, true);
                    $progress['done']++;
                } catch (\Throwable $e) {
                    report($e);
                    $progress['failed']++;
                    $progress['errors'] = array_slice(array_merge($progress['errors'], [
                        ($order?->order_number ?? '#'.$id).': '.$e->getMessage(),
                    ]), -20);
                    $this->error(($order?->order_number ?? '#'.$id).': '.$e->getMessage());
                }
                Cache::forever(self::PROGRESS, $progress);
            }

            $progress['status'] = 'finished';
            $progress['finished_at'] = now()->toIso8601String();
            Cache::forever(self::PROGRESS, $progress);
            $this->info("Selesai: {$progress['done']} terkirim, {$progress['failed']} gagal dari {$progress['total']} order.");

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
