document.addEventListener('DOMContentLoaded', function() {
    const shopButton = document.getElementById('shopButton');
    const originalButtonContainer = document.querySelector('.shop-button-container');
    const footerButtonContainer = document.createElement('div');
    footerButtonContainer.className = 'footer-shop-button';
    document.body.appendChild(footerButtonContainer);

    let footerButton = null;

    function updateButtonPosition() {
        const rect = originalButtonContainer.getBoundingClientRect();
        const isMobile = window.innerWidth <= 768;

        if ((rect.bottom < 0 || window.scrollY > rect.bottom) && isMobile) {
            if (!footerButton) {
                footerButton = shopButton.cloneNode(true);
                footerButtonContainer.appendChild(footerButton);
            }
            footerButtonContainer.style.display = 'block';
            shopButton.style.visibility = 'hidden';
        } else {
            footerButtonContainer.style.display = 'none';
            shopButton.style.visibility = 'visible';
        }
    }

    window.addEventListener('scroll', updateButtonPosition);
    window.addEventListener('resize', updateButtonPosition);

    // Initial check
    updateButtonPosition();
});
