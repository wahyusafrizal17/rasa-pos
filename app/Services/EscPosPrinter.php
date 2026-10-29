<?php

namespace App\Services;

use App\Models\Order;

class EscPosPrinter
{
    public const WIDTH = 48;

    public function receipts(Order $order): string
    {
        return $this->receipt($order, 'CUSTOMER').$this->receipt($order, 'BAR');
    }

    /** Dua lembar terpisah: satu untuk customer, satu diserahkan ke bar. */
    public function receiptsHtml(Order $order): string
    {
        return $this->renderReceipts($order, ['CUSTOMER', 'BAR']);
    }

    public function receiptsHeightMm(Order $order): int
    {
        return min(320, $this->receiptHeightMm($order) * 2);
    }

    public function receipt(Order $order, ?string $copy = null): string
    {
        $order->loadMissing(['items', 'payments', 'outlet', 'customer', 'table', 'user']);

        $out = $this->init();
        $out .= $this->align(1);
        $out .= $this->bold(true);
        $out .= $this->line($order->outlet?->name ?: config('app.name'));
        $out .= $this->bold(false);
        if ($copy) {
            $out .= $this->bold(true);
            $out .= $this->line($copy);
            $out .= $this->bold(false);
        }
        $out .= $this->line($order->order_number);
        $out .= $this->line(wib($order->created_at));
        $type = $order->order_type?->label() ?? '';
        if ($order->table?->code) {
            $type .= ' · '.$order->table->code;
        }
        if ($order->guest_name) {
            $type .= ' · '.$order->guest_name;
        }
        $out .= $this->line($type);
        $out .= $this->line('Kasir: '.($order->user?->name ?? '-'));
        $out .= $this->rule();
        $out .= $this->align(0);

        foreach ($order->items as $item) {
            $out .= $this->cols($this->qty((float) $item->quantity).' x '.$item->name, money($item->total));
            if ($item->notes) {
                $out .= $this->line('  '.$item->notes);
            }
        }

        $out .= $this->rule();
        $out .= $this->cols('Subtotal', money($order->subtotal));
        $out .= $this->cols('Diskon', money($order->discount_amount));
        $out .= $this->cols('Pajak', money($order->tax_amount));
        if ((float) $order->service_charge > 0) {
            $out .= $this->cols('Service', money($order->service_charge));
        }
        $out .= $this->bold(true);
        $out .= $this->cols('Total', money($order->grand_total));
        $out .= $this->bold(false);

        foreach ($order->payments as $payment) {
            $out .= $this->cols($payment->method?->label() ?? 'Bayar', money($payment->amount));
            if ((float) $payment->change_amount > 0) {
                $out .= $this->cols('Kembalian', money($payment->change_amount));
            }
        }

        if ($order->is_compliment) {
            $out .= $this->rule();
            $out .= $this->bold(true);
            $out .= $this->line('COMPLIMENT');
            $out .= $this->bold(false);
            $out .= $this->line('Tamu: '.($order->compliment_guest ?? '-'));
            $out .= $this->line('Ket: '.($order->compliment_reason ?? '-'));
        }

        $out .= $this->rule();
        $out .= $this->align(1);
        $out .= $this->line((string) setting('receipt_footer', 'Terima kasih'));
        $out .= $this->cut();

        return $out;
    }

    public function receiptHtml(Order $order, ?string $copy = null): string
    {
        return $this->renderReceipts($order, [$copy]);
    }

    /** Tampilan sama dengan halaman /pos/{order}/receipt (logo, ukuran 80mm). */
    protected function renderReceipts(Order $order, array $copies): string
    {
        $order->loadMissing(['items', 'payments', 'outlet', 'customer', 'table', 'user']);

        return view('pos.receipt-print', ['order' => $order, 'copies' => $copies])->render();
    }

    public function receiptHeightMm(Order $order): int
    {
        $order->loadMissing(['items', 'payments']);
        $lines = 10 + $order->items->count() + $order->payments->count() + ($order->is_compliment ? 3 : 0);

        return min(180, max(60, 28 + ($lines * 6)));
    }

    public function ticket(Order $order, string $station, iterable $items): string
    {
        $order->loadMissing(['table', 'outlet']);

        $title = match ($station) {
            'kitchen' => 'KITCHEN',
            'bar' => 'BAR',
            default => 'TICKET',
        };

        $out = $this->init();
        $out .= $this->align(1);
        $out .= $this->bold(true);
        $out .= $this->line($title);
        $out .= $this->bold(false);
        $out .= $this->line($order->order_number);
        if ($order->table?->code) {
            $out .= $this->line('Meja '.$order->table->code);
        }
        if ($order->guest_name) {
            $out .= $this->line($order->guest_name);
        }
        $out .= $this->rule();
        $out .= $this->align(0);

        foreach ($items as $item) {
            $name = is_array($item) ? ($item['name'] ?? '') : $item->name;
            $qty = is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 1) : $item->quantity;
            $notes = is_array($item) ? ($item['notes'] ?? '') : $item->notes;
            $out .= $this->line($this->qty((float) $qty).' x '.$name);
            if ($notes) {
                $out .= $this->line('  '.$notes);
            }
        }

        $out .= $this->cut();

        return $out;
    }

    public function ticketHtml(Order $order, string $station, iterable $items): string
    {
        $order->loadMissing(['table']);
        $title = match ($station) {
            'kitchen' => 'KITCHEN',
            'bar' => 'BAR',
            default => 'TICKET',
        };

        $rows = '';
        $count = 0;
        foreach ($items as $item) {
            $count++;
            $name = is_array($item) ? ($item['name'] ?? '') : $item->name;
            $qty = is_array($item) ? ($item['qty'] ?? $item['quantity'] ?? 1) : $item->quantity;
            $notes = is_array($item) ? ($item['notes'] ?? '') : $item->notes;
            $rows .= '<div>'.$this->e($this->qty((float) $qty).' x '.$name).'</div>';
            if ($notes) {
                $rows .= '<div class="n">'.$this->e((string) $notes).'</div>';
            }
        }

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            .'html,body{margin:0;padding:0;width:72mm;font:12px/1.35 monospace;color:#000}'
            .'.c{text-align:center}.b{font-weight:700}.n{margin:0 0 2px 8px}.hr{border:0;border-top:1px dashed #000;margin:6px 0}'
            .'</style></head><body>'
            .'<div class="c b">'.$this->e($title).'</div>'
            .'<div class="c">'.$this->e($order->order_number).'</div>'
            .($order->table?->code ? '<div class="c">Meja '.$this->e($order->table->code).'</div>' : '')
            .'<hr class="hr">'.$rows
            .'</body></html>';
    }

    public function send(string $ip, int $port, string $bytes): bool
    {
        if ($ip === '' || $bytes === '') {
            return false;
        }

        $socket = @stream_socket_client('tcp://'.$ip.':'.$port, $errno, $errstr, 0.4);
        if (! $socket) {
            return false;
        }

        stream_set_timeout($socket, 1);
        $ok = fwrite($socket, $bytes) !== false;
        fclose($socket);

        return $ok;
    }

    public function ticketHeightMm(iterable $items): int
    {
        $count = 0;
        foreach ($items as $item) {
            $count++;
        }

        return min(120, max(40, 24 + ($count * 8)));
    }

    protected function init(): string
    {
        return "\x1B\x40";
    }

    protected function align(int $mode): string
    {
        return "\x1B\x61".chr($mode);
    }

    protected function bold(bool $on): string
    {
        return "\x1B\x45".($on ? "\x01" : "\x00");
    }

    protected function line(string $text): string
    {
        return $this->ascii($text)."\n";
    }

    protected function rule(): string
    {
        return str_repeat('-', self::WIDTH)."\n";
    }

    protected function cols(string $left, string $right): string
    {
        $left = $this->ascii($left);
        $right = $this->ascii($right);
        $space = self::WIDTH - strlen($left) - strlen($right);
        if ($space < 1) {
            $left = substr($left, 0, max(0, self::WIDTH - strlen($right) - 1));
            $space = 1;
        }

        return $left.str_repeat(' ', $space).$right."\n";
    }

    protected function qty(float $qty): string
    {
        return fmod($qty, 1.0) === 0.0
            ? (string) (int) $qty
            : rtrim(rtrim(number_format($qty, 3, ',', '.'), '0'), ',');
    }

    protected function ascii(string $text): string
    {
        $text = str_replace(["\r", "\n"], ' ', $text);

        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    protected function cut(): string
    {
        return "\n\n\x1D\x56\x00";
    }

    protected function htmlRow(string $left, string $right): string
    {
        return '<div class="r"><span>'.$this->e($left).'</span><span>'.$this->e($right).'</span></div>';
    }

    protected function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
