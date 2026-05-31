/**
 * Created by Crepla on 26.01.2017.
 */

jQuery(document).ready(function($) {
    //document.querySelectorAll('.products a')
    //.forEach(function(elem) {
    //    elem.setAttribute('target', '_blank');
    //})

    $('.btn.btn-alternative.details').click(function () {

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        var link_rate = parseFloat(position * 0.005);
        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

        console.log({category_id, tag_id});
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: link_rate,
                post_id: post_id,
                tag_id: tag_id,
                category_id: category_id
            },
        });
        console.log('rating unklar',link_rate)
        return true;
    });

    //if clicked "Product photo"
    //$('body').on('click', '.attachment-shop_catalog.size-shop_catalog.wp-post-image', function ()
    $('body').on('click', '.attachment-woocommerce_thumbnail.size-woocommerce_thumbnail', function () {

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

         //console.log(position);
         //console.log(post_id);
         console.log({category_id, tag_id});

        var photo_rate = parseFloat(position * 0.005);
        //console.log(photo_rate);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: photo_rate,
                post_id: post_id,
                tag_id: tag_id,
                category_id: category_id
            },
        });
        console.log('rating Foto',photo_rate)
        return true;
    });

    //if clicked "Product link" button
   $('body').on('click', '.product-name', function () {

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

        console.log({category_id, tag_id});

        var product_name_rate = parseFloat(position * 0.005);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: product_name_rate,
                post_id: post_id,
                tag_id: tag_id,
                category_id: category_id
            },
        });
        console.log('rating ProduktLink',product_name_rate)

        return true;
    });

    //when "Zum geschenk" clicked class="single_add_to_cart_button button alt"
	$('body').on('click', '.btn.btn-flat.button.product_type_external', function () {

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

        var amazon_rate = parseFloat(position * 0.0005) + 0.5;
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: amazon_rate,
                post_id: post_id,
                tag_id: tag_id,
                category_id: category_id
            },
        });
        console.log('rating zum Klick TEST',amazon_rate, 'Test', category_id)
        return true;
    });

    //Single product page
    //if "Buy product" button clicked
	$('body').on('click', '.single_add_to_cart_button.button.alt', function () {

        var position = $(this).closest('p').attr('data-url'); // defining the id of the clicked product
        var category_id = $('.product-category-id').html();
        var tag_id = $('.product-tag-id').html();

        console.log({category_id, tag_id});
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: 0.25,
                post_id: position,
                tag_id: tag_id,
                category_id: category_id
            },
        });
        console.log('rating Single Page',0.55 )
        return true;
    });

});
