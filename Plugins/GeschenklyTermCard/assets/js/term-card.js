jQuery(document).ready(function($) {
    var termId = $('#term-card').data('term-id');
    var taxonomy = $('#term-card').data('taxonomy');
    var activeFilters = {};
    var filteredProductsOffset = 0;
    var popularProductsOffset = 12;
    var newProductsOffset = 12;
    var risingProductsOffset = 4;
    var sixMonthsPopularProductsOffset = 4;

    // DOM elements for filter
    var $filterSection = $('#filterSection');
    var $filterWrapper = $('.gift-filter-wrapper');
    var $filterToggle = $('.filter-toggle');
    var $filterToggleIcon = $('.filter-toggle i');
    var $filterTextHeading = $('.filter-text h3');

    function loadProducts(productType, containerId, offset, limit) {
        $.ajax({
            url: gtc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gtc_load_more_products',
                term_id: termId,
                taxonomy: taxonomy,
                product_type: productType,
                offset: offset,
                limit: limit,
                nonce: gtc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $container = $(containerId);
                    if (offset === 0) {
                        $container.html('');
                    }
                    response.data.forEach(function(product) {
                        $container.append(generateProductHtml(product));
                    });

                    if (response.data.length < limit) {
                        $('#showMore' + productType.charAt(0).toUpperCase() + productType.slice(1) + 'Products').hide();
                    }
                }
            }
        });
    }

    function generateProductHtml(product) {
        return `
            <div class="product-item">
                <div class="product-image-container">
                    <a href="${product.url}">
                        <img src="${product.image}" alt="${product.name}" loading="lazy">
                    </a>
                </div>
                <a class="product-card-link" href="${product.url}">${product.name}</a>
                <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                    href="/redirect.php?product=${product.slug}"
                    class="ansehen-button">
                    ${product.button_text}
                </a>
                <div class="awp-wishlist-button-container">
                    <button class="awp-add-to-wishlist"
                        data-product-id="${product.id}"
                        data-product-name="${product.name}"
                        data-product-url="${product.url}"
                        aria-label="Zur Wunschliste hinzufügen">
                        <i class="fa fa-heart" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        `;
    }

    $('.show-more').on('click', function() {
        var productType = $(this).data('product-type');
        var offset = 0;
        var limit = 12;
        var containerId = '#' + productType + 'Products';

        switch (productType) {
            case 'popular':
                offset = popularProductsOffset;
                popularProductsOffset += limit;
                break;
            case 'new':
                offset = newProductsOffset;
                newProductsOffset += limit;
                break;
            case 'rising':
                offset = risingProductsOffset;
                risingProductsOffset += limit;
                break;
            case 'sixMonthsPopular':
                offset = sixMonthsPopularProductsOffset;
                sixMonthsPopularProductsOffset += limit;
                break;
            case 'showMoreFiltered':
                offset = filteredProductsOffset;
                filteredProductsOffset += limit;
                containerId = '#filteredProducts';
                break;
        }

        loadProducts(productType, containerId, offset, limit);
    });

    // Filter functionality
    $('.filter-button').on('click', function(e) {
        e.stopPropagation();
        var filter = $(this).data('filter');
        var value = $(this).data('value');
        $(this).toggleClass('active');

        if (!activeFilters[filter]) {
            activeFilters[filter] = [];
        }

        var index = activeFilters[filter].indexOf(value);
        if (index > -1) {
            activeFilters[filter].splice(index, 1);
        } else {
            activeFilters[filter].push(value);
        }

        if (activeFilters[filter].length === 0) {
            delete activeFilters[filter];
        }

        filteredProductsOffset = 0;
        loadFilteredProducts();
    });

    function loadFilteredProducts() {
        $.ajax({
            url: gtc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gtc_filter_products',
                term_id: termId,
                taxonomy: taxonomy,
                filters: activeFilters,
                offset: filteredProductsOffset,
                limit: 12,
                nonce: gtc_ajax.nonce
            },
            success: function(response) {
                var $container = $('#filteredProducts');
                if (filteredProductsOffset === 0) {
                    $container.html('');
                }
                if (response.success) {
                    response.data.forEach(function(product) {
                        $container.append(generateProductHtml(product));
                    });
                    $('#showMoreFiltered').toggle(response.data.length === 12);
                } else {
                    $container.html('<p>Keine Produkte gefunden.</p>');
                    $('#showMoreFiltered').hide();
                }
            }
        });
    }

    // Toggle filter section
    function toggleFilterSection() {
        $filterSection.slideToggle(400, function() {
            if ($filterSection.is(':visible')) {
                $filterTextHeading.text('Filter schließen');
                $filterToggleIcon.removeClass('fa-sliders-h').addClass('fa-times');
            } else {
                $filterTextHeading.text('Filter öffnen');
                $filterToggleIcon.removeClass('fa-times').addClass('fa-sliders-h');
            }
        });
    }

    $filterWrapper.on('click', function(e) {
        e.preventDefault();
        toggleFilterSection();
    });

    // Persona filter
    $('.persona-card').on('click', function() {
        var personaId = $(this).data('persona-id');
        $.ajax({
            url: gtc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gtc_apply_persona_filter',
                persona_id: personaId,
                term_id: termId,
                nonce: gtc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    activeFilters = response.data.filters;
                    filteredProductsOffset = 0;
                    loadFilteredProducts();
                    updateFilterButtonStates();
                    $('html, body').animate({
                        scrollTop: $("#filteredProducts").offset().top
                    }, 1000);
                }
            }
        });
    });

    function updateFilterButtonStates() {
        $('.filter-button').each(function() {
            var filter = $(this).data('filter');
            var value = $(this).data('value');
            if (activeFilters[filter] && activeFilters[filter].includes(value)) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }
});
