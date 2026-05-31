jQuery(document).ready(function($) {
    var categoryId = $('#category-card').data('category-id');
    var activeFilters = {};
    var filteredProductsOffset = 0;

    // DOM-Elemente für Filter
    var $filterSection = $('#filterSection');
    var $filterWrapper = $('.gift-filter-wrapper');
    var $filterToggle = $('.filter-toggle');
    var $filterToggleIcon = $('.filter-toggle i');
    var $filterTextHeading = $('.filter-text h3');

    function loadFilteredProducts() {
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_filter_products',
                category_id: categoryId,
                filters: activeFilters,
                offset: filteredProductsOffset,
                limit: 12,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var $container = $('#filteredProducts');
                    if (filteredProductsOffset === 0) {
                        $container.html(response.data);
                    } else {
                        $container.append(response.data);
                    }
                    filteredProductsOffset += 12;
                    $('#showMoreFiltered').toggle(response.data.trim() !== '');
                }
            }
        });
    }

    // Event-Listener für "Mehr anzeigen" Button
    $('#showMoreFiltered').on('click', function() {
        loadFilteredProducts();
    });

    // Filter-Funktionalität
    $('.filter-button').on('click', function(e) {
        e.stopPropagation(); // Verhindert, dass der Klick den Filter schließt
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

    // Toggle Filter-Bereich
    function toggleFilterSection() {
        $filterSection.slideToggle(400, function() {
            if ($filterSection.is(':visible')) {
                $filterTextHeading.text('Filter ausblenden');
                $filterToggleIcon.removeClass('fa-sliders-h').addClass('fa-times');
            } else {
                $filterTextHeading.text('Finde dein perfektes Geschenk');
                $filterToggleIcon.removeClass('fa-times').addClass('fa-sliders-h');
            }
        });
    }

    // Nur der Toggle-Button öffnet/schließt den Filter
    $filterToggle.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleFilterSection();
    });

    // Verhindert, dass Klicks innerhalb des Filters ihn schließen
    $filterSection.on('click', function(e) {
        e.stopPropagation();
    });

    // Schließt den Filter, wenn außerhalb geklickt wird
    $(document).on('click', function(e) {
        if (!$(e.target).closest($filterWrapper).length && !$(e.target).closest($filterSection).length) {
            if ($filterSection.is(':visible')) {
                toggleFilterSection();
            }
        }
    });

    // Kategorie-spezifische Initialisierung
    function initCategoryPage() {
        console.log('Kategorie-Seite initialisiert');
    }

    // Führen Sie die Initialisierung aus
    initCategoryPage();
});
