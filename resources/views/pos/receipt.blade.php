<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        body { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; width: 280px; margin: 16px auto; font-size: 12px; color: #111; }
        h1 { font-size: 16px; margin: 0; }
        .muted { color: #666; }
        .row { display: flex; justify-content: space-between; margin: 4px 0; }
        hr { border: none; border-top: 1px dashed #bbb; margin: 10px 0; }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ $order->outlet?->name ?? config('app.name') }}</h1>
    <p class="muted">{{ $order->order_number }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
    <p class="muted">{{ $order->order_type->label() }} @if($order->table) · {{ $order->table->code }} @endif</p>
    <p class="muted">Kasir: {{ $order->user?->name }}</p>
    <hr>
    @foreach ($order->items as $item)
        <div class="row">
            <div>{{ $item->quantity }} × {{ $item->name }}</div>
            <div>{{ money($item->total) }}</div>
        </div>
        @if ($item->notes)<div class="muted">note: {{ $item->notes }}</div>@endif
    @endforeach
    <hr>
    <div class="row"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
    <div class="row"><span>Diskon</span><span>{{ money($order->discount_amount) }}</span></div>
    <div class="row"><span>Pajak</span><span>{{ money($order->tax_amount) }}</span></div>
    <div class="row"><strong>Total</strong><strong>{{ money($order->grand_total) }}</strong></div>
    @foreach ($order->payments as $payment)
        <div class="row"><span>{{ $payment->method->label() }}</span><span>{{ money($payment->amount) }}</span></div>
        @if ($payment->change_amount > 0)
            <div class="row"><span>Kembalian</span><span>{{ money($payment->change_amount) }}</span></div>
        @endif
    @endforeach
    <hr>
    <p style="text-align:center">{{ setting('receipt_footer', 'Terima kasih telah berkunjung') }}</p>
</body>
</html>
