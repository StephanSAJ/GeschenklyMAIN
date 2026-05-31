/**
 * Created by Crepla on 26.01.2017.
 */
 
jQuery(document).ready(function($) {
	
    //Main page block

    //if clicked "View details" button
    $('.btn.btn-alternative.details').click(function () {		

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        // console.log(position);
        // console.log(post_id);

        var link_rate = parseFloat(position * 0.1);
        //console.log(link_rate);

        // add_action( 'admin_footer', 'my_action_javascript' );
        //
        // var data = { action: 'update_rating',
        //              rate: link_rate,
        //              post_id: post_id
        // };
        // console.log(link_rate);
        // jQuery.post(ajax_object.ajax_url, data, function(response) {
        //
        //     alert(response);
        //
        // });

        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: link_rate,
                post_id: post_id
            },
            // success: function(data) {
            // alert(data);
            // }
        });
        return true;
    });    

    //if clicked "Product photo" button
    $('body').on('click', '.attachment-shop_catalog.size-shop_catalog.wp-post-image', function () {		

        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        // console.log(position);
        // console.log(post_id);

        var photo_rate = parseFloat(position * 0.1);
        //console.log(photo_rate);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: photo_rate,
                post_id: post_id
            },
            // success: function(data) {
            //     alert(data);
            // }
        });
        return true;
    });

    //if clicked "Product link" button
   $('body').on('click', '.product-name', function () {
	
        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');

        // console.log(position);
        // console.log(post_id);

        var product_name_rate = parseFloat(position * 0.1);
        // console.log(product_name_rate);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: product_name_rate,
                post_id: post_id
            },
            // success: function(data) {
            //     alert(data);
            // }
        });
        return true;
    });

    //when "Zum geschenk" clicked
	$('body').on('click', '.btn.btn-flat.button.product_type_external', function () {	
  
        var position = $(this).closest('li').attr('data-id'); // defining the id of the clicked product
        var post_id = $(this).closest('li').attr('data-product-id');
        // console.log(position);
        // console.log(post_id);

        var amazon_rate = parseFloat(position * 0.1) + 0.3;
        // console.log(amazon_rate);
        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: amazon_rate,
                post_id: post_id
            },
            // success: function(data) {
            //     alert(data);
            // }
        });
        return true;
    });

    //Single product page
    //if "Buy product" button clicked
	$('body').on('click', '.btn.btn-flat.button.product_type_external', function () {	
	
        var position = $(this).closest('p').attr('data-url'); // defining the id of the clicked product
        //console.log(position);

        $.ajax({
            url: rjs.ajax_url,
            type: 'post',
            data: {
                action: 'update_rating',
                rate: 0.3,
                post_id: position
            },
            // success: function(data) {
            //     alert(data);
            // }
        });
        return true;
    });

});