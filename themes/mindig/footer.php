<?php do_action( 'yit_footer' ); ?>
<?php wp_footer(); ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialisiere Zähler und setze data-id für vorhandene Produkte
    let productElements = document.querySelectorAll(".product");
    productElements.forEach((product, index) => {
        product.dataset.id = index + 1;
    });

    // Überwache Änderungen in der Produktliste
    const productsContainer = document.querySelector('.products');
    if (productsContainer) {
        const observer = new MutationObserver(() => {
            let idIndex = 1;
            document.querySelectorAll(".product").forEach(product => {
                product.dataset.id = idIndex++;
            });
        });

        // Beobachte nur Kindelemente, die hinzugefügt oder entfernt werden
        observer.observe(productsContainer, { childList: true });
    }
});
</script>
</body>
</html>
