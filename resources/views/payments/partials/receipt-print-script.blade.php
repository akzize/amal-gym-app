<script>
    // Open the print dialog once the page, its fonts and its images (the association logo)
    // are ready. The paper size is left to the dialog/driver (e.g. POS-80C) so the receipt
    // fills the roll's real width.
    window.addEventListener('load', function () {
        const fontsReady = document.fonts ? document.fonts.ready : Promise.resolve();

        // Chrome snapshots the page for the print preview the moment print() is called, so an
        // image that is loaded but not yet decoded/painted would be missing from the preview.
        const imagesReady = Promise.all(Array.from(document.images).map(function (img) {
            return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
        }));

        Promise.all([fontsReady, imagesReady]).then(function () {
            // wait two frames so the decoded images are actually painted
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    window.print();
                });
            });
        });
    });
</script>
