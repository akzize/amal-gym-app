<script>
    // Size the printed page to the roll width x the receipt's real height, so thermal
    // printers don't scale an A4/Letter page down or feed blank paper, then print.
    (function () {
        const paperWidth = @json($printerWidth ?? '80mm');
        const pageMarginMm = 4; // keep in sync with the @page margin above

        function printReceipt() {
            const receipt = document.querySelector('.receipt');
            const heightMm = Math.ceil(receipt.getBoundingClientRect().height * 25.4 / 96) + pageMarginMm * 2 + 2;

            const style = document.createElement('style');
            style.textContent = `@page { size: ${paperWidth} ${heightMm}mm; margin: ${pageMarginMm}mm; }`;
            document.head.appendChild(style);

            window.print();
        }

        window.addEventListener('load', function () {
            (document.fonts ? document.fonts.ready : Promise.resolve()).then(printReceipt);
        });
    })();
</script>
