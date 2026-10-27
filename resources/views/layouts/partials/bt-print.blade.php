<script>
(function () {
    var port = null;
    var preferredName = window.RasaPrint?.printer || '';

    function notice(message) {
        if (window.posNotice) {
            window.posNotice(message);
            return;
        }
        console.warn(message);
    }

    function bytesFromBase64(base64) {
        var binary = atob(base64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    }

    async function writeChunks(writer, bytes) {
        var size = 512;
        for (var i = 0; i < bytes.length; i += size) {
            await writer.write(bytes.subarray(i, Math.min(i + size, bytes.length)));
        }
    }

    async function openPort(selected) {
        if (!selected) {
            throw new Error('Printer Bluetooth belum dipilih.');
        }
        if (selected.readable && selected.writable) {
            return selected;
        }
        await selected.open({ baudRate: 9600 }); // ponytail: most BT ESC/POS; raise to 115200 if printer needs it
        return selected;
    }

    async function pickPort(forcePicker) {
        if (!('serial' in navigator)) {
            throw new Error('Browser ini tidak mendukung raw Bluetooth. Pakai Chrome di perangkat yang printer BT-nya sudah dipair.');
        }

        if (!forcePicker) {
            if (port && port.readable && port.writable) {
                return port;
            }
            var known = await navigator.serial.getPorts();
            if (known.length) {
                port = await openPort(known[0]);
                return port;
            }
        }

        port = await openPort(await navigator.serial.requestPort());
        return port;
    }

    async function printRaw(printerName, base64, forcePicker) {
        if (!base64) {
            throw new Error('Data struk kosong.');
        }
        preferredName = printerName || preferredName || window.RasaPrint?.printer || '';
        var selected = await pickPort(!!forcePicker);
        var bytes = bytesFromBase64(base64);
        var writer = selected.writable.getWriter();
        try {
            await writeChunks(writer, bytes);
        } finally {
            writer.releaseLock();
        }
        return true;
    }

    async function connect() {
        await pickPort(false);
        return true;
    }

    async function jobs(list, options) {
        options = options || {};
        for (var i = 0; i < list.length; i++) {
            var job = list[i];
            var station = job.payload?.station || job.printer?.station;
            if (options.skipCashier && station === 'cashier') {
                continue;
            }
            if (!job.escpos) {
                continue;
            }
            await printRaw(job.printer?.name || options.printer, job.escpos);
        }
    }

    async function receipt(payload) {
        try {
            if (!payload.receipt_escpos) {
                throw new Error('Struk ESC/POS tidak tersedia.');
            }
            await printRaw(payload.qz_printer || payload.printer || preferredName, payload.receipt_escpos);
            return true;
        } catch (error) {
            notice(error.message || 'Gagal cetak via Bluetooth.');
            return false;
        }
    }

    async function tickets(payload) {
        try {
            await jobs(payload.print_jobs || [], { printer: payload.qz_printer || preferredName });
            return true;
        } catch (error) {
            notice(error.message || 'Gagal cetak via Bluetooth.');
            return false;
        }
    }

    window.RasaPrint = Object.assign(window.RasaPrint || {}, {
        printer: preferredName,
        printReceipt: receipt,
        printTickets: tickets,
        printRaw: printRaw,
        connect: connect,
        pickPrinter: function () { return pickPort(true); },
    });
    // alias lama biar pemanggil RasaQz tidak putus
    window.RasaQz = window.RasaPrint;
})();
</script>
