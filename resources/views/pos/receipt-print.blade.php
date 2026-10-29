<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @include('pos.partials.receipt-styles')
    </style>
</head>
<body>
    @foreach ($copies as $copy)
        @include('pos.partials.receipt-sheet', ['order' => $order, 'copy' => $copy])
    @endforeach
</body>
</html>
