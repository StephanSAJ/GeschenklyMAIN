jQuery(document).ready( function($){
    "use strict";

    var $body = $('body'),
        $topbar = $( document.getElementById('topbar') ),
        $products_sliders = $('.products-slider-wrapper, .categories-slider-wrapper');


    /***************************************
     * UPDATE CALCULATE SHIPPING SELECT
    ***************************************/

    // FIX SHIPPING CALCULATOR SHOW
    $( '.shipping-calculator-form' ).show();

    var wc_version = 2.2;
    if(typeof yit_woocommerce != 'undefined')  wc_version = parseFloat( yit_woocommerce.version );

    if ( wc_version < 2.3 && $.fn.selectbox ) {
        $('#calc_shipping_state').next('.sbHolder').addClass('stateHolder');
        $body.on('country_to_state_changing', function(){
            $('.stateHolder').remove();
            $('#calc_shipping_state').show().attr('sb', '');

            $('select#calc_shipping_state').selectbox({
                effect: 'fade',
                classHolder: 'stateHolder sbHolder'
            });
        });
    }

    /**********************************
     * SHOP STYLE ZOOM
     **********************************/

    var apply_hover = function(){

        $( 'ul.products li:not(.category)' ).each( function(){

            var $this_item = $(this), to;

            var $wrapper = $this_item.find('.product-wrapper');

            $this_item.on({
                mouseenter: function() {
                    if ( $this_item.hasClass('grid') && $wrapper.hasClass('zoom') ) {
                        $this_item.height( $this_item.height()-1 );
                        $this_item.find('.product-actions-wrapper').height( $this_item.find('.product-actions').height() + 20 );
                        clearTimeout(to);
                    }
                },
                mouseleave: function() {
                    if ( $this_item.hasClass('grid') && $wrapper.hasClass('zoom') ) {

                        $this_item.find('.product-actions-wrapper').height( 0 );
                        to = setTimeout(function()
                        {
                            $this_item.css( 'height', 'auto' );
                        },700);
                    }
                }
            });
        })
    };

    $(document).on('yith-wcan-ajax-filtered yith_infs_added_elem', apply_hover );


    $(document).on( 'changed.owl.carousel' , function(){

          $('ul.products.yit_products_slider.owl-carousel .active'  ).removeClass('cloned');

          apply_hover();

    } );


    /*************************
     * SHOP STYLE SWITCHER
     *************************/

    $('#list-or-grid').on( 'click', 'a', function() {

        var trigger = $(this),
                view = trigger.attr( 'class' ).replace('-view', '');

            $( '.content ul.products li' ).removeClass( 'list grid' ).addClass( view );
            trigger.parent().find( 'a' ).removeClass( 'active' );
            trigger.addClass( 'active' );

            $.cookie( yit_shop_view_cookie, view );

            return false;
    });

     apply_hover();


    /***************************************************
     * ADD TO CART & ADD TO WISHLIST ( SHOP PAGE )
     **************************************************/
     
    var $pWrapper = new Array(),
        $i=0,
        $j=0;

    var add_to_cart = function(){

        $('ul.products').on('click', 'li.product .add_to_cart_button, .product-other-action .yith-wcwl-add-button .add_to_wishlist', function () {

            $pWrapper[$i] = $(this).parents('.product-wrapper');

            if (typeof yit.load_gif != 'undefined') {
                $pWrapper[$i].block({message: null, overlayCSS: {background: '#fff url(' + yit.load_gif + ') no-repeat center', opacity: 0.3, cursor: 'none'}});
            } else {
                $pWrapper[$i].block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url.substring(0, woocommerce_params.ajax_loader_url.length - 7) + '.gif) no-repeat center', opacity: 0.3, cursor: 'none'}});
            }

            $i++;

        });
    };

    add_to_cart();
    $(document).on( 'yith-wcan-ajax-filtered', add_to_cart );

    $body.on('added_to_cart added_to_wishlist', function (e) {

        if (typeof $pWrapper[$j] === 'undefined' )  return;

        var $ico,
            $thumb = $pWrapper[$j].find('.thumb-wrapper');

        if( e.type === 'added_to_wishlist' ) {
            $ico = '<span class="added_to_wishlist_ico"><img src="' + yit.added_to_wishlist_ico + '"></span>';
        }
        else {
            $ico = '<span class="added_to_cart_ico"><img src="' + yit.added_to_cart_ico + '"></span>';
        }

        $thumb.append( $ico );

        setTimeout(function () {
            $thumb.find('.added_to_wishlist_ico').fadeOut(2000, function () {
                $(this).remove();
            });
        }, 3000);

        $pWrapper[$j].unblock();

        $j++;

    });

    /********************************************
     * ADD TO WISHLIST ( SINGLE PRODUCT PAGE )
     *******************************************/

    $( '.product-actions .yith-wcwl-add-button .add_to_wishlist').on( 'click', function() {

        var feedback = $(this).closest('.yith-wcwl-add-to-wishlist').find('.yith-wcwl-wishlistaddedbrowse span.feedback');

        feedback.fadeIn();

        setTimeout( function(){
            feedback.fadeOut('slow');
        }, 4000 );

    });

    /*************************
     * PRODUCTS SLIDER
     *************************/

    if( $.fn.owlCarousel && $.fn.imagesLoaded && $products_sliders.length ) {

            var product_slider = function(t) {

             t.imagesLoaded(function(){
                var cols = t.data('columns') ? t.data('columns') : 4;
                var autoplay = (t.attr('data-autoplay')=='true') ? true : false;
                var owl = t.find('.products').owlCarousel({
                    items : cols,
                    responsiveClass   : true,
                    responsive:{
                        0 : {
                            items: yit_woocommerce.product_slider_col_0
                        },
                        479 : {
                            items: yit_woocommerce.product_slider_col_479
                        },
                        767 : {
                            items: yit_woocommerce.product_slider_col_767
                        },
                        992 : {
                            items: cols
                        }
                    },
                    autoplay          : autoplay,
                    autoplayTimeout   : 2000,
                    autoplayHoverPause: true,
                    loop              : true,
                    rtl: yit.isRtl == true
                });

                // Custom Navigation Events
                t.on('click', '.es-nav-next', function () {
                    owl.trigger('next.owl.carousel');
                });

                t.on('click', '.es-nav-prev', function () {
                    owl.trigger('prev.owl.carousel');
                });

            });

            };


        // initialize slider in only visible tabs
        $products_sliders.each(function(){
            var t = $(this);
            if( ! t.closest('.panel.group').length || t.closest('.panel.group').hasClass('showing')  ){
                product_slider( t );
            }
        });

        $('.tabs-container').on( 'yit_tabopened', function( e, tab ) {
            product_slider( tab.find( $products_sliders ) );
        });

    }

    /*************************
     * VARIATIONS SELECT
     *************************/

    var variations_select = function(){
        // variations select
        if( $.fn.selectbox ) {
            var form = $('form.variations_form');
            var select = form.find('select:not(.yith_wccl_custom)');

            if( form.data('wccl') ) {
                select = select.filter(function(){
                    return $(this).data('type') == 'select'
                });
            }

            select.selectbox({
                effect: 'fade',
                onOpen: function() {
                    //$('.variations select').trigger('focusin');
                }
            });

            var update_select = function(event){  // console.log(event);
                select.selectbox("detach");
                select.selectbox("attach");
            };

            // fix variations select
            form.on( 'woocommerce_update_variation_values', update_select);
            form.find('.reset_variations').on('click.yit', update_select);

        }
    };

    variations_select();

     /*************************
     * INQUIRY FORM
     *************************/

    if ( $('#inquiry-form .product-inquiry').length ) {
        $(document).on('click', '#inquiry-form .product-inquiry', function(){
            $(this).next('form.contact-form').slideToggle('slow');
        });
    }

    if( $( 'body').hasClass( 'single-product' ) ) {
        setTimeout( function() {
            if( $.trim( $( 'div.user-message').html() ) != '' || $.trim( $( '.contact-form li div.msg-error' ).text() ) != '' ) {
                $('#inquiry-form .product-inquiry').next('form.contact-form').slideToggle('slow');
            }
        }, 200 );
    }

    /*************************
     * Login Form
     *************************/

//    $('#login-form').on('submit', function(){
//        var a = $('#reg_password').val();
//        var b = $('#reg_password_retype').val();
//        if(!(a==b)){
//            $('#reg_password_retype').addClass('invalid');
//            return false;
//        }else{
//            $('#reg_password_retype').removeClass('invalid');
//            return true;
//        }
//    });

    /*************************
     * Widget Woo Price Filter
     *************************/

    if( typeof yit != 'undefined' && ( typeof yit.price_filter_slider == 'undefined' || yit.price_filter_slider == 'no' ) ) {
        var removePriceFilterSlider = function() {
            $( 'input#min_price, input#max_price' ).show();
            $('form > div.price_slider_wrapper').find( 'div.price_slider, div.price_label' ).hide();
        };

        $(document).on('ready', removePriceFilterSlider);
    }

    /*********************
     * SHARE IN SHOP PAGE
    **********************/

    $(document).on('click', '#yit_share', function(e){
        e.preventDefault();

        var share = $(this).parents('.product-other-action').siblings('.share-container');

        var template = '<div class="popupOverlay share"></div>' +
            '<div id="popupWrap" class="popupWrap share">' +
            '<div class="product-share">' +
            share.html() +
            '</div>' +
            '<i class="fa fa-times close-popup"></i>' +
            '</div>';

        $body.append(template);

        $('#popupWrap').center() ;
        $('.popupOverlay').css( { display: 'block', opacity:0 } ).animate( { opacity:0.7 }, 500 );
        $('#popupWrap').css( { display: 'block', opacity:0 } ).animate( { opacity:1 }, 500 );

        /** Close popup function **/
        var close_popup = function() {
            $('.popupOverlay').animate( {opacity:0}, 200);
            $('.popupOverlay').remove();
            $('.popupWrap').animate( {opacity:0}, 200);
            $('.popupWrap').remove();
        }

        /**
         *	Close popup on:
         *	- 'X button' click
         *   - wrapper click
         *   - esc key pressed
         **/
        $('.close-popup, .popupOverlay').click(function(){
            close_popup();
        });

        $(document).bind('keydown', function(e) {
            if (e.which == 27) {
                if($('.popupOverlay')) {
                    close_popup();
                }
            }
        });

        /** Center popup on windows resize **/
        $(window).resize(function(){
            $('#popupWrap').center();
        });
    });


    /*****************************************
     * CHANGE THUMB PRODUCT IN SHOP PAGE
     ******************************************/

    var yit_change_thumb_loop = function() {

    	$('.thumb-wrapper.zoom').on('click', '.single-attachment-thumbnail', function(){

        	$(this).siblings().removeClass('active');
	
        	var $main = $(this).parents( '.thumb-wrapper.zoom').find( '.thumb img' );
        	var $attachment = $(this).data('img');
	
        	$main.attr( 'src', $attachment);
            $main.removeAttr('srcset');
            $main.removeAttr('sizes');
        	$(this).addClass('active');

    	});
	}

	yit_change_thumb_loop();

    $(document).on( 'yith-wcan-ajax-filtered', yit_change_thumb_loop );

    /*************************
     * PRODUCT QUICK VIEW
     *************************/

    if ( $.fn.yit_quick_view && typeof yit_quick_view != 'undefined' ) {

        var yit_quick_view_init = function(){
            $('a.trigger-quick-view').yit_quick_view({

                item_container: 'li.product',
                loader: '.single-product.woocommerce',
                assets: yit_quick_view.assets,
                before: function( trigger, item ) {
                    // add loading in the button
                    if( typeof yit.load_gif != 'undefined' ) {
                        trigger.parents( '.product-wrapper').find('.thumb-wrapper').block({message: null, overlayCSS: {background: '#fff url(' + yit.load_gif +') no-repeat center', opacity: 0.5, cursor: 'none'}});
                    }
                    else {
                        trigger.parents( '.product-wrapper').find('.thumb-wrapper').block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_url.substring(0, woocommerce_params.ajax_url.length - 7) + '.gif) no-repeat center', opacity: 0.3, cursor: 'none'}});
                    }
                },
                openDialog: function( trigger, item ) {
                    // remove loading from button
                    trigger.parents( '.product-wrapper').find('.thumb-wrapper').unblock();
                },
                completed: function( trigger, item, res, overlay ) {

                    // add main class to dialog container
                    $(overlay).addClass('product-quick-view');

                    //product image slider
                    thumbanils_slider();

                    // quantity fields
                    $('div.quantity:not(.buttons_added), td.quantity:not(.buttons_added)').addClass('buttons_added').append('<input type="button" value="+" class="plus" />').prepend('<input type="button" value="-" class="minus" />');

                    $( 'input.qty:not(.product-quantity input.qty)' ).each( function() {
                        var min = parseFloat( $( this ).attr( 'min' ) );

                        if ( min >= 0 && parseFloat( $( this ).val() ) < min ) {
                            $( this ).val( min );
                        }
                    });

                    // Variation Form
                    var form_variation = $(overlay).find( '.variations_form' );

                    form_variation.wc_variation_form();
                    form_variation.trigger( 'check_variations' );
                    form_variation.trigger( 'reset_image' );

                    if( typeof $.yith_wccl != 'undefined' && res.attributes ) {
                        // color e label premium
                        $.yith_wccl( res.attributes );
                    }

                    variations_select();

                    // add to cart
                    $('form.cart', overlay).on('submit', function (e) {

                        if( typeof wc_cart_fragments_params != 'undefined' && wc_add_to_cart_params.cart_redirect_after_add === 'yes' ) {
                            window.location = wc_add_to_cart_params.cart_url;
                            return;
                        }

                        e.preventDefault();

                        var form = $(this),
                            button = form.find('button'),
                            product_url = item.find('a.thumb').attr('href');

                        if (typeof yit.load_gif != 'undefined') {
                            button.block({message: null, overlayCSS: {background: '#fff url(' + yit.load_gif + ') no-repeat center', opacity: 0.3, cursor: 'none'}});
                        }
                        else if ( typeof( woocommerce_params.plugin_url ) != 'undefined') {
                            button.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.3, cursor: 'none'}});
                        }
                        else {
                            button.block({message: null, overlayCSS: {background: '#fff url(' + woocommerce_params.ajax_loader_url.substring(0, woocommerce_params.ajax_loader_url.length - 7) + '.gif) no-repeat center', opacity: 0.3, cursor: 'none'}});
                        }

                        $.post(product_url, form.serialize() + '&_wp_http_referer=' + product_url, function (result) {
                            var message = $('.woocommerce-message', result),
                                cart_dropdown = $('#header .yit_cart_widget', result);

                            if( typeof wc_cart_fragments_params != 'undefined') {
                            // update fragments
                                var $supports_html5_storage;

                                try {
                                    $supports_html5_storage = ( 'sessionStorage' in window && window.sessionStorage !== null );

                                    window.sessionStorage.setItem('wc', 'test');
                                    window.sessionStorage.removeItem('wc');
                                } catch (err) {
                                    $supports_html5_storage = false;
                                }

                                $.ajax({
                                    url    : wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                                    type   : 'POST',
                                    success: function (data) {

                                        if (data && data.fragments) {

                                            $.each(data.fragments, function (key, value) {
                                                $(key).replaceWith(value);
                                            });

                                            if ($supports_html5_storage) {
                                                sessionStorage.setItem(wc_cart_fragments_params.fragment_name, JSON.stringify(data.fragments));
                                                sessionStorage.setItem('wc_cart_hash', data.cart_hash);
                                            }

                                            $(document.body).trigger('wc_fragments_refreshed');
                                        }
                                    }
                                });
                            }

                            var $ico,
                                $thumb = $('.quick-view-overlay .single-product div.images');

                            $ico = '<span class="added_to_cart_ico"><img src="' + yit.added_to_cart_ico + '"></span>';
                            $thumb.append( $ico );

                            // remove loading
                            button.unblock();
                        });
                    });
                },
                action: 'yit_load_product_quick_view'
            });
        };

        yit_quick_view_init();

        $(document).on( 'yith-wcan-ajax-filtered yith_infs_adding_elem', yit_quick_view_init );

    }

    var thumbanils_slider = function(){

        var $container = $('.slider-quick-view-container');
        var $slider = $container.find('.slider-quick-view');

        $slider.owlCarousel({
            autoplay: false,
            items: 1,
            singleItem: true,

            mouseDrag: false,
            touchDrag: false
        });

        $container.on('click', '.es-nav-next', function(){
            $slider.trigger('next.owl.carousel');
        });

        $container.on('click', '.es-nav-prev', function(){
            $slider.trigger('prev.owl.carousel');
        });

    };

    /*************************
     * END PRODUCT QUICK VIEW
     *************************/

    $(document).on( 'click' , '.cart_totals .cart_update_checkout input[type="submit"]' , function() {

        $('.woocommerce > form input[name="update_cart"]').click();

    } );


});