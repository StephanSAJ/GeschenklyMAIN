jQuery(document).ready(function($) {
    function sendRatingData(post_id, post_title, category_id, tag_id) {
        console.log('PRODUCTID2', post_id);
        console.log('TGID', tag_id);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: 1,
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

    function getIdFromCard() {
        if ($('#category-card').length > 0) {
            return $('#category-card').data('category-id');
        } else if ($('#tag-card').length > 0) {
            return $('#tag-card').data('tag-id');
        }
        return null;
    }

    // Updated event listener to specifically target the "ansehen-button"
    $('.ansehen-button').on('click', function(e) {
        var $productItem = $(this).closest('.product-item');
        var post_id = $productItem.find('.reaction-button').data('product-id');
        var post_title = $productItem.find('.product-card-link').text().trim();
        var id = getIdFromCard();
        var category_id = $('#category-card').length > 0 ? id : null;
        var tag_id = $('#tag-card').length > 0 ? id : null;

        console.log('Clicked "Ansehen" button:', {
            post_id: post_id,
            post_title: post_title,
            category_id: category_id,
            tag_id: tag_id
        });

        if (post_id && post_title) {
            sendRatingData(post_id, post_title, category_id, tag_id);
        } else {
            console.error('Produkt ID oder Titel fehlt.');
        }

        // The button already has onclick="_paq.push(...)" for Matomo tracking
        // We don't need to prevent default or delay navigation here
    });

    // Removed other event listeners that are no longer needed
});
