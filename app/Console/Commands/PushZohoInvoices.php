<?php

namespace App\Console\Commands;

use App\Services\ZohoBooksPush;
use Illuminate\Console\Command;

class PushZohoInvoices extends Command
{
    protected $signature = 'zoho:push-invoices';

    protected $description = 'Kirim order POS yang sudah lunas ke Zoho Books (SO → Invoice → Payment)';

    public function handle(ZohoBooksPush $push): int
    {
        $push->pending();
        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
