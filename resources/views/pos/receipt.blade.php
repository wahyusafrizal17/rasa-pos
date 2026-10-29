<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=80mm">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @include('pos.partials.receipt-styles')

        #bt-status { padding-top: 3mm; }
        .toolbar { display: flex; gap: 6px; justify-content: center; margin-bottom: 6px; }
        .toolbar button { font: inherit; padding: 6px 14px; border: 1px solid #000; border-radius: 6px; background: #fff; color: #000; }

        @media print {
            #bt-status, .toolbar { display: none; }
        }
    </style>
    <script>window.RasaPrint = { printer: @json($qzPrinter ?? '') };</script>
    @include('layouts.partials.bt-print')
</head>
<body>
    <p id="bt-status" class="center muted">Menyiapkan cetak Bluetooth…</p>
    @if (! request()->has('preview'))
        <div class="toolbar">
            <button type="button" onclick="history.length > 1 ? history.back() : window.close()">Kembali</button>
            <button type="button" onclick="window.print()">Cetak</button>
        </div>
    @endif
    <script>
        window.__receiptEscpos = @json($receiptEscpos ?? '');
        (async function () {
            var status = document.getElementById('bt-status');
            // dibuka langsung (Cetak invoice / print ulang), bukan iframe struk di POS
            var standalone = window.self === window.top && !new URLSearchParams(window.location.search).has('preview');
            var toolbar = document.querySelector('.toolbar');
            if (toolbar && window.self !== window.top) toolbar.style.display = 'none';
            var fallback = function (message) {
                status.textContent = message;
                if (!standalone) return;
                if (document.readyState === 'complete') {
                    window.print();
                } else {
                    window.addEventListener('load', function () { window.print(); });
                }
            };
            if (!window.__receiptEscpos) {
                fallback('Struk siap. Ketuk Cetak.');
                return;
            }
            try {
                await window.RasaPrint.printRaw(@json($qzPrinter ?? ''), window.__receiptEscpos);
                status.textContent = 'Struk terkirim ke printer.';
            } catch (e) {
                fallback('Ketuk Cetak untuk kirim ke printer.');
            }
        })();
    </script>
    @include('pos.partials.receipt-sheet', ['order' => $order, 'copy' => null])
</body>
</html>
