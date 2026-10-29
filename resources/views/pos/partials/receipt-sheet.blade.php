<section class="sheet">
    <img class="head-logo" src="{{ asset('images/logo/rasa-logo.png') }}" alt="Rasa Group">
    <h1>{{ $order->outlet?->name ?? config('app.name') }}</h1>
    @if (! empty($copy))<p class="center copy">{{ $copy }}</p>@endif
    <p class="center muted">{{ $order->order_number }}</p>
    <p class="center muted">{{ wib($order->created_at) }}</p>
    <p class="center muted">{{ $order->order_type->label() }}@if($order->table) · {{ $order->table->code }}@endif @if($order->guest_name) · {{ $order->guest_name }}@endif</p>
    <p class="center muted">Kasir: {{ $order->user?->name }}</p>
    <hr>
    @foreach ($order->items as $item)
        <div class="row">
            <span>{{ qty_label($item->quantity) }} × {{ $item->name }}</span>
            <span>{{ money($item->total) }}</span>
        </div>
        @if ($item->notes)<p class="muted">  {{ $item->notes }}</p>@endif
    @endforeach
    <hr>
    <div class="row"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
    <div class="row"><span>Diskon</span><span>{{ money($order->discount_amount) }}</span></div>
    <div class="row"><span>Pajak</span><span>{{ money($order->tax_amount) }}</span></div>
    @if ((float) $order->service_charge > 0)
        <div class="row"><span>Service</span><span>{{ money($order->service_charge) }}</span></div>
    @endif
    <div class="row total"><span>Total</span><span>{{ money($order->grand_total) }}</span></div>
    @foreach ($order->payments as $payment)
        <div class="row"><span>{{ $payment->method->label() }}</span><span>{{ money($payment->amount) }}</span></div>
        @if ($payment->change_amount > 0)
            <div class="row"><span>Kembalian</span><span>{{ money($payment->change_amount) }}</span></div>
        @endif
    @endforeach
    @if ($order->is_compliment)
        <hr>
        <p class="total">COMPLIMENT</p>
        <p>Tamu: {{ $order->compliment_guest ?? '-' }}</p>
        <p>Ket: {{ $order->compliment_reason ?? '-' }}</p>
    @endif
    <hr>
    <p class="center">{{ setting('receipt_footer', 'Terima kasih telah berkunjung') }}</p>
</section>
