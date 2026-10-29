/* EPPOS Plus 80MM: paper 80mm, printable ~72mm */
@page { size: 80mm auto; margin: 0; }

* { box-sizing: border-box; }

html, body {
    width: 80mm;
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
    font-family: ui-monospace, "Courier New", Courier, monospace;
    font-size: 12px;
    line-height: 1.35;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

.sheet { padding: 3mm 4mm 6mm; }
.sheet + .sheet { break-before: page; page-break-before: always; }

h1 { font-size: 15px; margin: 0 0 4px; text-align: center; }
p { margin: 0 0 2px; }
.center { text-align: center; }
.muted { color: #000; }
.copy { font-size: 13px; font-weight: 700; letter-spacing: 0.08em; }
.row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
.row span:first-child { flex: 1; word-break: break-word; }
.row span:last-child { white-space: nowrap; }
hr { border: none; border-top: 1px dashed #000; margin: 8px 0; }
.total { font-size: 14px; font-weight: 700; }
.head-logo { display: block; width: 34mm; height: auto; margin: 0 auto 3mm; }

@media print {
    html, body { width: 80mm; }
}
