jQuery(document).ready( function($){
    "use strict";

    var $body = $('body'),
        $topbar = $( document.getElementById('topbar') ),
        $products_sliders = $('.products-slider-wrapper, .categories-slider-wrapper');


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

});