<script>
    // Open the print dialog once the page and its fonts are ready. The paper size is left
    // to the dialog/driver (e.g. POS-80C) so the receipt fills the roll's real width.
    window.addEventListener('load', function () {
        (document.fonts ? document.fonts.ready : Promise.resolve()).then(function () {
            window.print();
        });
    });
</script>
