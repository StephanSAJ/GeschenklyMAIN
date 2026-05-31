jQuery(document).ready(function($) {
    var likedProducts = JSON.parse(localStorage.getItem('likedProducts')) || [];

    function initializeLikedItemsIcon() {
        var $annaSupport = $('.anna-support');

        // Falls das Element .anna-support nicht vorhanden ist, versuchen wir ein alternatives Element zu finden
        if (!$annaSupport.length) {
            // Ersetzen Sie '.header-container' durch ein geeignetes Selektor, das auf Ihren Produktseiten vorhanden ist
            $annaSupport = $('.header-container');
        }

        if ($annaSupport.length) {
            var $iconsContainer = $annaSupport.siblings('.geschenkly-icons-container');
            if (!$iconsContainer.length) {
                $iconsContainer = $('<div class="geschenkly-icons-container"></div>');
                $annaSupport.after($iconsContainer);
            }

            var $likedItemsIcon = $(`
                <div id="liked-items-icon" class="geschenkly-icon" style="cursor: pointer;">
                    <i class="far fa-thumbs-up reaction-icon" aria-hidden="true"></i>
                    <span id="liked-items-count">0</span>
                </div>
            `);

            $iconsContainer.append($likedItemsIcon);

            $likedItemsIcon.on('click', function(e) {
                e.preventDefault();
                updatePreviewBox();
            });
        } else {
            console.error('Referenzelement für die Wunschliste nicht gefunden.');
        }
    }

    function createPreviewBox() {
        var $previewBox = $(`
            <div id="preview-box" class="geschenkly-preview-box" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5); max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 20px;">Favoritenliste</h3>
                        <button id="close-preview-box" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #888;">&times;</button>
                    </div>
                    <div id="preview-products"></div>
                    <div id="email-form" class="geschenkly-email-form" style="margin-top: 15px;">
                        <div style="display: flex;">
                            <input type="email" id="email-input" placeholder="Deine E-Mail-Adresse" style="flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-right: none; border-radius: 4px 0 0 4px;">
                            <button id="send-button" style="background-color: #ffd700; border: none; padding: 10px 15px; cursor: pointer; border-radius: 0 4px 4px 0;">
                                <i class="fa fa-paper-plane" aria-hidden="true" style="color: #333;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        $('body').append($previewBox);

        $('#close-preview-box').on('click', function() {
            $('#preview-box').fadeOut();
        });

        $previewBox.on('click', function(e) {
            if ($(e.target).closest('#preview-box > div').length === 0) {
                $('#preview-box').fadeOut();
            }
        });
    }

    function updatePreviewBox() {
        var $previewProducts = $('#preview-products');
        $previewProducts.empty();

        if (likedProducts.length === 0) {
            $previewProducts.html('<p style="text-align: center; color: #666;">Keine gelikten Produkte vorhanden.</p>');
            return;
        }

        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_get_liked_products_info',
                product_ids: likedProducts,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    response.data.forEach(function(product) {
                        var $productItem = $(`
                            <div class="preview-product-item" data-product-id="${product.id}" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding: 10px; background-color: #f9f9f9; border-radius: 4px;">
                                <div style="display: flex; align-items: center; flex-grow: 1;">
                                    <img src="${product.image}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 4px;">
                                    <span style="font-size: 14px;">${product.name}</span>
                                </div>
                                <button class="remove-product" style="background: none; border: none; color: #999; cursor: pointer; font-size: 16px; width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; padding: 0; margin-left: 10px;">&times;</button>
                            </div>
                        `);
                        $previewProducts.append($productItem);
                    });
                } else {
                    console.error('Error loading product info:', response.data);
                    $previewProducts.html('<p style="text-align: center; color: #ff4d4d;">Fehler beim Laden der Produktinformationen.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $previewProducts.html('<p style="text-align: center; color: #ff4d4d;">Fehler beim Laden der Produktinformationen.</p>');
            }
        });

        $('#preview-box').fadeIn();
    }

    function updateLikedItemsCount() {
        var count = likedProducts.length;
        $('#liked-items-count').text(count);
    }

    function removeProduct(productId) {
        likedProducts = likedProducts.filter(id => id != productId);
        localStorage.setItem('likedProducts', JSON.stringify(likedProducts));
        $(`.preview-product-item[data-product-id="${productId}"]`).fadeOut(function() {
            $(this).remove();
            if (likedProducts.length === 0) {
                $('#preview-box').fadeOut();
            }
        });
        updateLikedItemsCount();
    }

    function initializeEventListeners() {
        $('#preview-products').on('click', '.remove-product', function() {
            var productId = $(this).closest('.preview-product-item').data('product-id');
            removeProduct(productId);
        });

        $('#send-button').on('click', function() {
            const email = $('#email-input').val();
            if (email) {
                $.ajax({
                    url: geschenklyWishlist.restUrl,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        email: email,
                        productIds: likedProducts
                    }),
                    success: function(response) {
                        alert('Deine gelikten Produkte wurden erfolgreich gespeichert!');
                        $('#preview-box').fadeOut();
                        $('#email-input').val('');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('API-Fehler:', textStatus, errorThrown);
                        alert('Es gab einen Fehler beim Speichern der Produkte. Bitte versuche es später erneut.');
                    }
                });
            } else {
                alert('Bitte gib eine gültige E-Mail-Adresse ein.');
            }
        });

        // Wunschlisten-Funktionalität initialisieren
        $('body').on('click', '.awp-add-to-wishlist', function(event) {
            event.preventDefault();

            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            var productUrl = $(this).data('product-url');

            // Füge das Produkt zur lokalen Wunschliste hinzu
            if (!likedProducts.includes(productId)) {
                likedProducts.push(productId);
                localStorage.setItem('likedProducts', JSON.stringify(likedProducts));
                updateLikedItemsCount();
            }

            // Optional: Visuelle Rückmeldung hinzufügen
            $(this).find('i').toggleClass('fas far');
        });
    }

    function init() {
        createPreviewBox();
        initializeLikedItemsIcon();
        initializeEventListeners();
        updateLikedItemsCount();

        // Optional: Markieren von gelikten Produkten auf der Seite
        likedProducts.forEach(function(productId) {
            var $productItem = $(`.product-item[data-product-id="${productId}"]`);
            if ($productItem.length) {
                $productItem.addClass('liked');
                $productItem.find('.like-button').addClass('active').find('i').removeClass('far').addClass('fas');
                $productItem.css('border', '2px solid #4CAF50').css('box-shadow', '0 0 10px rgba(76, 175, 80, 0.5)');
            }
        });
    }

    init();
});
