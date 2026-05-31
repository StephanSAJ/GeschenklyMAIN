jQuery(document).ready(function($) {
    function sendRatingData(post_id, post_title, category_id, tag_id) {
        console.log('PRODUCTID', post_id);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                nonce: rjs.nonce,
                rate: 1, // Fester Wert für die Rate, um den Klick zu erfassen
                post_id: post_id,
                post_title: post_title,
                category_id: category_id,
                tag_id: tag_id
            },
            success: function(response) {
                console.log(response);
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    $('.btn.btn-alternative.details, .attachment-woocommerce_thumbnail.size-woocommerce_thumbnail, .product-name, .btn.btn-flat.button.product_type_external, .single_add_to_cart_button.button.alt').on('click', function () {
        var parentDiv = $(this).closest('.product-actions-wrapper');
        console.log('parentDiv', parentDiv);

        var productInfoDiv = parentDiv.find('.product-info');
        var post_id = productInfoDiv.attr('data-product-id');
        console.log('post_id', post_id);

        var post_title = productInfoDiv.attr('data-product-title');
        console.log('post_title', post_title);

        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

        if (post_id && post_title) {
            sendRatingData(post_id, post_title, category_id, tag_id);
        } else {
            console.error('Produkt ID oder Titel fehlt.');
        }
        return true;
    });
});
