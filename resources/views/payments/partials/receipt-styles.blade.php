<style>
    :root {
        --printer-width: {{ $printerWidth ?? '80mm' }};
    }

    @page {
        margin: 2mm;
    }

    @media print {

        /* drop screen padding/centering so the receipt starts at the page margin */
        body {
            display: block !important;
            margin: 0;
            padding: 0 !important;
            -webkit-print-color-adjust: exact;
        }

        /* fill the full printable width of the selected paper */
        .receipt {
            width: 100% !important;
            padding: 0 !important;
        }
    }

    /* make the receipt compact and monospaced for thermal printers */
    .receipt {
        width: calc(var(--printer-width) - 4mm);
        max-width: 100%;
        font-family: Arial, "Segoe UI", Tahoma, Geneva, Verdana, sans-serif, Helvetica, "Courier New", monospace;
        font-size: 13px;
        color: #111827;
        line-height: 1.3;
        word-break: break-word;
    }

    .hr {
        border-bottom: 1px dashed #222;
        margin: 6px 0;
        opacity: 0.6;
    }

    /* columns: left label + right value */
    .row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        align-items: baseline;
    }

    .muted {
        color: #374151;
        font-size: 11px;
    }

    .center {
        text-align: center;
    }

    /* compact small text for footer */
    .small {
        font-size: 11px;
        color: #374151;
    }

    /* ensure long text wraps under label (for trainee/group names) */
    .label {
        flex: 1 1 auto;
        min-width: 0;
    }

    .value {
        flex: 0 0 auto;
        text-align: left;
        /* RTL: values on left */
        white-space: nowrap;
    }

    /* header: logo + the two top lines, bold so they stand out on thermal paper */
    .logo {
        display: block;
        margin: 0 auto 4px;
        max-width: 40mm;
        max-height: 20mm;
        object-fit: contain;
    }

    .title {
        font-size: 16px;
        font-weight: 800;
    }

    .subtitle {
        font-size: 13px;
        font-weight: 700;
    }

    /* highlight amounts */
    .amount {
        font-weight: 500;
    }

    /* THANK YOU centered and spaced */
    .thankyou {
        margin-top: 8px;
        /* letter-spacing: 0.06em; */
        font-weight: 600;
    }
</style>
