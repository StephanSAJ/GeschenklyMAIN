jQuery(document).ready(function ($) {
    "use strict";

    var $window = $(window),
        $body = $(document.body),

        header = document.getElementById('header'),
        nav = document.getElementById('nav'),
        primary = document.getElementById('primary'),
        footer = document.getElementById('footer'),
        copyright = document.getElementById('copyright'),

        $header = $(header),
        $nav = $('.nav'),
        $primary = $(primary),
        $footer = $(footer),
        $copyright = $(copyright),

        hAdminBar = $('#wpadminbar').length ? $('#wpadminbar').height() : 0,

        onScrollEnd,

        detectDevice = function () {
            if (YIT_Browser.isViewportBetween(1024)) {
                $body.addClass('isMobile');
                $("#animate-css").attr("disabled", "disabled");
            } else {
                $body.removeClass('isMobile');
                $("#animate-css").attr("disabled", false);
            }

            if (YIT_Browser.isViewportBetween(1024, 769)) {
                $body.addClass('isIpad');
            } else {
                $body.removeClass('isIpad');
            }

            if (YIT_Browser.isViewportBetween(768)) {
                $body.addClass('isIphone');
            } else {
                $body.removeClass('isIphone');
            }
        };

    /*************************
     * MISC
     *************************/

    if (YIT_Browser.isIE8()) {
        $('*:last-child').addClass('last-child');
    }

    if (YIT_Browser.isIE10()) {
        $('html').attr('id', 'ie10').addClass('ie');
    }

    // placeholder support
    if ($.fn.placeholder) {
        $('input[placeholder], textarea[placeholder]').placeholder();
    }

    // detect device and add the class to body
    _onresize(detectDevice);
    detectDevice();

  

    /*************************
     * Custom select
     *************************/

    if ($.fn.selectbox) {
        /*fix wc 2.3 */
        var calculate_shipping_select = '';
        if (parseFloat(yit_woocommerce.version) < 2.3) calculate_shipping_select = '.woocommerce table.shop_table.shipping p select,';
        var custom_selects = $('.woocommerce-ordering select, .faq-filters select, ' + calculate_shipping_select + ' .widget_product_categories select, .widget.widget_archive select, .widget.widget_categories #cat, #buddypress div.item-list-tabs ul li.last select, #buddypress select#whats-new-post-in, select#bbp_stick_topic_select, select#bbp_topic_status_select, select#message-type-select, select#display_name, select#bbp_destination_topic, select#bbp_forum_id, #dropdown_layered_nav_color, .wcml_currency_switcher');
        if (custom_selects.length > 0) {
            custom_selects.selectbox({
                effect: 'fade'
            });
        }
    }

    /*************************
     * Sticky Footer
     *************************/

    if ($.fn.imagesLoaded) {
        $primary.imagesLoaded(function () {
            if ($footer.length > 0) {
                $footer.stickyFooter();
            } else {
                $copyright.stickyFooter();
            }
        });
    }

    /*************************
     * Replace type="number" in opera
     *************************/

    $('.opera').find('.quantity input.input-text.qty').replaceWith(function () {
        return '<input type="text" class="input-text qty text" name="quantity" value="' + $(this).attr('value') + '" />';
    });

    /*************************
     * Back to top
     *************************/

    if ($('#back-top').length) {
        $.yit_backToTop();
    }

    /*************************
     * YIT Share
     *************************/

    var yit_share_init = function () {

        $('.single-post .blog .share').add('.single-post .blog .yit_post_meta .share').off('click').on('click', function () {
            var t = $(this),
                social = t.find('.socials-container');

            if (social.is(':visible')) {
                social.slideUp('slow');
            } else {
                social.slideDown('slow');
            }
        });
    };

    $body.on('yit_share_init', yit_share_init).trigger('yit_share_init');


    /*************************
     * MENU
     *************************/

    bigmenu();

    function bigmenu() {
        var show_dropdown = function (t) {

                var options,
                    submenuWidth,
                    offsetMenuRight,
                    leftPos = 0,
                    $headerContainer = $('#header-container').children('.container'),
                    containerWidth = $headerContainer.width(),
                    containerLeftPost = $headerContainer.offset().left + containerWidth,
                    dropdown = $(t);

                if (dropdown.is('#lang_sel ul > li')) {
                    submenuWidth = dropdown.find('ul').outerWidth();
                    offsetMenuRight = dropdown.offset().left + submenuWidth;

                    if (offsetMenuRight > containerLeftPost)
                        options = {
                            left: containerLeftPost - offsetMenuRight
                        };
                    else
                        options = {};

                    dropdown.find('ul li').parent().css(options).stop(true, true).fadeIn(300);

                } else if (dropdown.hasClass('bigmenu')) {
                    dropdown.find('ul.sub-menu:not(ul.sub-menu li > div.submenu > ul.sub-menu), ul.children:not(ul.children li > div.submenu > ul.children)').parent().stop(true, true).fadeIn(500);

                } else if (dropdown.hasClass('login-menu')) {
                    submenuWidth = dropdown.find('div.submenu').outerWidth();
                    offsetMenuRight = dropdown.offset().left + submenuWidth;

                    if (offsetMenuRight > containerLeftPost)
                        options = {
                            left: containerLeftPost - offsetMenuRight
                        };
                    else
                        options = {};

                    dropdown.find('.login-box').parent().css(options).stop(true, true).fadeIn(300);
                } else {
                    submenuWidth = dropdown.find('div.submenu').outerWidth();
                    offsetMenuRight = dropdown.offset().left + submenuWidth;

                    if (offsetMenuRight > containerLeftPost)
                        options = {
                            marginLeft: containerWidth - (offsetMenuRight)
                        };
                    else
                        options = {};

                    dropdown.find('ul.sub-menu:not(ul.sub-menu li > div.submenu > ul.sub-menu), ul.children:not(ul.children li > div.submenu > ul.children)').parent().css(options).stop(true, true).fadeIn(300);
                }

            },

            hide_dropdown = function (t) {
                var dropdown = $(t);
                dropdown.find('ul.sub-menu:not(ul.sub-menu li > div.submenu > ul.sub-menu), ul.children:not(ul.children li > div.submenu > ul.children), #lang_sel ul > li > ul > li,  div.login-box').parent().fadeOut(300);
            };

        $nav.on('mouseenter mouseleave', 'ul > li', function (e) {
            if (e.type == 'mouseenter') show_dropdown(this);
            else if (e.type == 'mouseleave') hide_dropdown(this);
        });


        $nav.on('click', 'ul > li', function (e) {
            if (window.innerWidth < 1025) {
                hide_dropdown(this);
            }
        });


        $('.nav ul > li').each(function () {
            var $this_item = $(this);
            if ($this_item.find('ul').length > 0) {
                $this_item.children('a').append('<span class="sf-sub-indicator"></span>');
                /*
                var add_padding = function () {
                    $this_item.children('a').css('padding-right', '').css({ paddingRight: parseInt($this_item.children('a').css('padding-right')) + 3 });
                };

                _onresize( add_padding );
                add_padding();*/
            }
        });

        $nav.on('mouseenter mouseleave', 'li:not(.megamenu) ul.sub-menu li, li:not(.megamenu) ul.children li, li:not(.bigmenu) ul.sub-menu li, li:not(.bigmenu) ul.children li, #header-row .widget_nav_menu > div > ul > li', function (e) {
            var $this = $(this);

            if (e.type == 'mouseenter') {
                if ($this.closest('.megamenu').length > 0) {
                    return;
                }
                var containerWidth = $header.width(),
                    containerOffsetRight = $header.offset().left + containerWidth,
                    submenuWidth = $this.find('ul.sub-menu, ul.children').parent().width(),
                    offsetMenuRight = $this.offset().left + submenuWidth * 2;

                if (offsetMenuRight > containerOffsetRight)
                    $this.addClass('left');


                $this.find('ul.sub-menu, ul.children').parent().stop(true, true).fadeIn(300);
            } else if (e.type == 'mouseleave') {
                if ($this.closest('.megamenu').length > 0 || ($this.closest('.bigmenu').length > 0 && !$this.prev().hasClass('.bigmenu')))
                    return;

                $this.find('ul.sub-menu, ul.children').parent().fadeOut(300);
            }
        });




    }
    /*************************
     * sticky header
     *************************/

    if ($header.hasClass('sticky-header')) {
        $header.imagesLoaded(function () {

            var sticky_header = $header.hasClass('skin3') || $header.hasClass('skin2') ? $('#header') : $('#header-container'),
                header_height = sticky_header.outerHeight(),
                headerBottomPos = sticky_header.offset().top + header_height,
                hPlaceholder = $('<div />').addClass('header-placeholder').height(header_height),
                logo = $('#logo'),
                wlogo = logo.width(),
                wlogoimg = logo.find('img').width();

            if (sticky_header.length) {

                var header_sticky_scroll = function () {
                    logo.width(wlogo);
                    hPlaceholder = hPlaceholder.height(header_height);

                    if ($window.scrollTop() + hAdminBar > headerBottomPos) {
                        var top = hAdminBar;

                        if (!sticky_header.hasClass('fixed')) {
                            sticky_header.hide().height('')
                                .addClass('fixed')
                                .css({
                                    top: -header_height,
                                    display: 'block',
                                    backgroundColor: $header.css('backgroundColor'),
                                    backgroundImage: $header.css('backgroundImage')
                                })
                                .delay(500)
                                .animate({
                                    top: top
                                }, 500);

                            logo.css({
                                top: -5, //-15,
                            });


                            logo.find('img').css({
                                width: wlogoimg * 0.8,
                                height: 'auto'
                            });


                            hPlaceholder.insertAfter(sticky_header);
                        }

                    } else if ($window.scrollTop() + hAdminBar <= hPlaceholder.offset().top) {
                        logo.css({
                            top: 0, //-25,

                        });
                        logo.find('img').css({
                            width: wlogoimg
                        });
                        sticky_header.removeClass("fixed")
                            .css({
                                top: 0,
                                height: header_height
                            })
                            .show();

                        setTimeout(function () {
                            sticky_header.height('');
                        }, 1000);

                        //logo.animate({ paddingRight: logopadding },500);

                        hPlaceholder.remove();

                    }

                };

                $window.on('scroll', header_sticky_scroll);
            }
        });
    }

    /*************************
     * Bigmenu
     *************************/

    $nav.yit_bigmenu();

    /*************************
     * Header Custom Big Menu
     *************************/

    var
        $header_row = $('#header-row'),
        $header_cm = $header_row.find('.yit-custom-megamenu ul.yit-cm'),
        $level_1 = $header_row.find('.yit-custom-megamenu ul.yit-cm > li > div.submenu'),
        level1_width = $level_1.outerWidth();
    $level_1.addClass('level_1');
    $header_cm.on('mouseenter mouseleave', 'li', function (e) {
        var $this = $(this);
        if (e.type == 'mouseenter') {
            var submenu = $this.children('div.submenu');
            submenu.stop(true, true).fadeIn(300);
        } else if (e.type == 'mouseleave') {
            if ($this.closest('.submenu').hasClass('level_1')) {
                $level_1.css({
                    width: level1_width
                });
            }
            $this.find('div.submenu').stop(true, true).fadeOut(300);
        }

    });



    if ($header_cm.find('li.bigmenu').length) {
        $header_cm.yit_bigmenu_custom();
    }



    if ($('#yith-s').length) {
        /*************************
         * SEARCH AUTOCOMPLETE
         *************************/


        var search_autocomplete = function () {

            var search = $('#yith-s'),
                search_container = $('.nav-searchfield'),
                search_button = $('#yith-searchsubmit'),
                a = search_container.outerWidth() + 1,
                search_skin = search.closest('form').next(),
                search_left = search.offset().left;
            if (search_skin.length > 0) {
                search_skin.css({
                    width: a + 'px',
                    left: search_left - 1
                });
            }

        };

        $(document).on('yit_ajax_search_init', search_autocomplete);
        _onresize(search_autocomplete);
    }


    if ($.fn.selectbox) {



        $(".search_mini_content .selectbox").selectbox({
            effect: 'fade',
            onOpen: function (inst) {

                var sel = $('#' + inst.id),
                    arr = $.makeArray(),
                    ul = sel.next().find('ul'),
                    sel_top = ul.offset().top;
                ul.find('li').each(function () {
                    var $this = $(this).find('a');
                    arr.push($this.width());
                });

                ul.css('width', Math.max.apply(Math, arr) + 37);

                ul.css({
                    top: 21
                });
            }
        });



    }
    /*************************
     * FULL WIDTH SECTION
     *************************/
    if ($('.section_fullwidth').length) {
        var _fullwidth_section = $.yit_fullwidth_section();

        $(window).on('scroll', _fullwidth_section);
        _onresize(_fullwidth_section);

    }



    /*************************
     * PARALLAX
     *************************/



    var parallax_items = $('.parallaxeos, .video-parallaxeos');

    parallax_items.imagesLoaded(function () {
        $('.parallaxeos_outer').yit_parallax();

        if (YIT_Browser.isMobile()) {
            parallax_items.css({
                backgroundPosition: ''
            }).translate3d({
                y: 0
            });
            $('.parallaxeos_outer').find('.parallaxeos_content').css({
                opacity: ''
            }).translate3d({
                y: 0
            });
            return;
        }

        $(window).on('scroll resize', function () {
            parallax_items.yit_parallax_onscroll();
        });
        parallax_items.yit_parallax_onscroll();

    });





    /*************************
     * PRETTYPHOTO
     *************************/


    if ($.fn.prettyPhoto) {
        $(".video-button a[rel^='prettyPhoto']").prettyPhoto({
            social_tools: '',
            default_width: 650,
            default_height: 487,
            show_title: false
        });
    }


    /*************************
     * MASONRY
     *************************/

    var add_masonry = function () {

        if ($.fn.imagesLoaded && $.fn.masonry) {
            $('.blog-masonry, ul.products.masonry, .testimonials').each(function () {
                var container = $(this),
                    item = container.data('item');

                if (item === 'undefined') {
                    item = '.masonry_item';
                }

                container.imagesLoaded(function () {
                    container.masonry({
                        itemSelector: item,
                        isAnimated: true,
                        isRTL: yit.isRtl
                    });
                }).css('visibility', 'visible');
            });
        }
    };

    $(window).on('load resize', add_masonry);

    /*************************
     * Newlsetter Placeholder
     *************************/

    var placeholder = $('.widget input.email-field.text-field.autoclear').attr('placeholder');
    $('.widget input.email-field.text-field.autoclear').on('focus', function () {
        var $this = $(this);
        $this.attr('placeholder', '');
    });
    $('.widget input.email-field.text-field.autoclear').on('blur', function () {
        var $this = $(this);
        $this.attr('placeholder', placeholder);
    });


    /*************************
     * PostFormat Gallery
     *************************/

    if ($body.hasClass('single-format-gallery')) {
        $('.masterslider').yit_gallery_post_format();
    }

    /*************************
     * Widget Testimonial
     *************************/

    if ($('.widget.testimonial-widget').length) {
        $.testimonial_slider_widget();
    }


    /*************************
     * Blog Share
     *************************/

    if ($body.hasClass('blog-single') || $('.shortcode.morph-button').length) {
        $.yit_blog_share();
    }


    /*************************
     * WAYPOINT
     *************************/

    if (!YIT_Browser.isMobile()) {
        $('.yit_animate:not(.animated), .parallaxeos_animate:not(.animated)').each(function () {
            $(this).yit_waypoint();
        });
    }



    /*************************
     * WIDGETS
     *************************/

    $('.yit_toggle_menu ul.menu').each(function () {
        var menu = $(this);

        menu.filter('.open_first').find("> li:first-child").addClass("opened");
        menu.filter('.open_all').find("> li").addClass("opened");

        menu.filter('.open_active').find('li').filter('.current-menu-ancestor').addClass("opened");
        menu.filter('.open_active').find('li').filter('.current-menu-parent').addClass("opened");

        menu.find('> li > ul').hide();
        menu.find('> li.opened > ul').show();

        menu.on('click', '> li > a', function (e) {
            e.preventDefault();

            var submenu = $(this).next("ul"),
                li = submenu.parent("li");

            li.hasClass("opened") ? li.removeClass("opened") : li.addClass("opened");

            submenu.slideToggle('slow');
        });
    });

    if ($.fn.owlCarousel) {
        $('.slides-reviews-widget').each(function () {
            var slider = $(this);

            slider.owlCarousel({
                singleItem: true,
                navigation: true,
                slideSpeed: slider.data('slidespeed'),
                navigationText: ['<span class="entypo chevron-left"></span>', '<span class="entypo chevron-right"></span>'],
                autoPlay: slider.data('autoplay'),
                pagination: false

            });
        });
    }



    /****************************
     *toggle
     *************************/
    $('.toggle-content:not(.opened), .content-tab:not(.opened)').hide();
    $('.toggle .toggle-title').on('click', function () {

        var $this = $(this),
            opened_class = $this.children('span').data('opened'),
            closed_class = $this.children('span').data('closed');

        $this.next().slideToggle(600, 'easeOutExpo');
        $this.toggleClass('tab-opened tab-closed');
        $this.children('span').toggleClass(closed_class + ' ' + opened_class);
        $this.attr('title', ($(this).attr('title') == 'Close') ? 'Open' : 'Close');
        return false;
    });


    /****************************
     * dropdown products category
     *************************/

    var widget = $('.widget.woocommerce.widget_product_categories, .widget.widget_categories');
    var main_ul = widget.find('> ul');

    if (main_ul.length) {
        var dropdown_widget_nav = function () {

            main_ul.find('> li').each(function () {

                var main = $(this),
                    link = main.find('> a'),
                    ul = main.find('> ul.children');

                if (ul.length) {

                    //init widget
                    if (main.hasClass('closed')) {
                        ul.hide();
                        link.after('<i class="icon-plus"></i>');
                    } else if (main.hasClass('opened')) {
                        link.after('<i class="icon-minus"></i>');
                    } else {
                        main.addClass('opened');
                        link.after('<i class="icon-minus"></i>');
                    }

                    // on click
                    main.find('i').on('click', function () {

                        ul.slideToggle('slow');

                        if (main.hasClass('closed')) {
                            main.removeClass('closed').addClass('opened');
                            main.find('i').removeClass('icon-plus').addClass('icon-minus');
                        } else {
                            main.removeClass('opened').addClass('closed');
                            main.find('i').removeClass('icon-minus').addClass('icon-plus');
                        }
                    });
                }
            });
        };

        $(document).on('yith-wcan-ajax-filtered');
        dropdown_widget_nav();
    }


    /*************************
     * QUICK VIEW PLUGIN
     *************************/

    if (typeof Modernizr != 'undefined' && $('.quick-view-overlay').length) {

        $.fn.yit_quick_view = function (options) {

            $(this).each(function () {
                var trigger = $(this),
                    $window = $(window),

                    settings = $.extend({
                        item_container: 'li',
                        completed: function () {},
                        before: function () {},
                        openDialog: function () {},
                        action: 'yit_load_quick_view',
                        loader: '.main-content',
                        assets: null,
                        loadPage: false
                    }, options),

                    trigger_id = trigger.attr('id'),
                    item = trigger.closest(settings.item_container),
                    container = document.getElementById('wrapper'),
                    triggerBttn = document.getElementById(trigger_id),
                    overlay = document.querySelector('div.quick-view-overlay'),
                    closeBttn = overlay.querySelector('a.overlay-close'),

                    openQuickView = function (e) {
                        e.preventDefault();

                        var completed = function (data, html) {

                            // load css assets
                            data.find('link').each(function () {
                                if ($('#cache-dynamics-css').length) {
                                    $(this).insertBefore($('#cache-dynamics-css'));
                                } else {
                                    $(this).appendTo('head');
                                }
                            });

                            // load js scripts for the single product content
                            if (settings.assets != null) {
                                $.each(settings.assets, function (index, value) {
                                    $.ajax({
                                        type: "GET",
                                        url: value,
                                        dataType: "script"
                                    });
                                });
                            }

                            settings.completed(trigger, item, html, overlay);

                            // open effect
                            $(overlay).imagesLoaded(function () {
                                settings.openDialog(trigger, item);

                                classie.add(overlay, 'open');
                            });

                            $(document).trigger('yit_quick_view_loaded');
                        };

                        if (!classie.has(overlay, 'close')) {
                            settings.before(trigger, item);

                            if (settings.loadPage) {
                                var href = trigger.attr('href');

                                $.get(href, {
                                    popup: true
                                }, function (html) {

                                    var data = $('<div>' + html + '</div>'),
                                        product_html = data.find(settings.loader).wrap('<div/>').parent().html();

                                    // main content
                                    $(overlay).find('.main').find('.overlay-close').after(product_html);

                                    completed(data, html);

                                });
                            } else {
                                $.post(yit.ajaxurl, {
                                    action: settings.action,
                                    item_id: trigger.data('item_id')
                                }, function (html) {

                                    var data = $('<div>' + html + '</div>'),
                                        product_html = data.find(settings.loader).wrap('<div/>').parent().html();

                                    // main content
                                    $(overlay).find('.main').find('.overlay-close').after(product_html);

                                    completed(data, html);

                                });
                            }
                        }
                    },

                    closeQuickView = function (e) {
                        e.preventDefault();

                        if (classie.has(overlay, 'open')) {

                            var close_button = $(overlay).find('.overlay-close'),
                                wrapper = $(overlay).find('.main');

                            classie.remove(overlay, 'open');

                            setTimeout(function () {
                                wrapper.find('.head').html(close_button);
                            }, 1000);

                        }
                    };

                triggerBttn.addEventListener('click', openQuickView);
                closeBttn.addEventListener('click', closeQuickView);
            });
        };
    }

    /*************************
     * Fix lang sel
     *************************/
    $.yit_wpml_lang_fix();




    /***********************
     * MOBILE MENU FIX
     ***********************/
    /*    var respmenuclick = $('.st-menu ul.level-1 > li.menu-item-has-children');
       respmenuclick.on('click',function(){
           var t = $(this);
           if ( t.hasClass('open')){
               t.removeClass('open')
           }
           else{
               respmenuclick.removeClass('open');
               t.addClass('open')
           }
       });

       $(".isMobile li.menu-item-has-children > a").click(function( event ){
           event.preventDefault();
       });
*/


    /***********************
     * MOBILE MENU  animation
     ***********************/


    var mob_menu = function () {
        if (window.innerWidth <= 768) {
            createMobileNavArrows();
            var mob_menu_arrow = $('.mob_menu_toggle_arrow');
            mob_menu_arrow.on('click', function () {
                var $this = $(this);
                var parent = $this.parent();
                if ($this.hasClass('opened')) {
                    $this.removeClass('opened');
                } else {
                    $this.next().find('.mob_menu_toggle_arrow').removeClass('opened');
                    $this.parent().parent().find('.mob_menu_toggle_arrow').removeClass('opened');
                    $this.addClass('opened');
                }
            });
        }
    };
    mob_menu();

    function createMobileNavArrows() {
        $('.mobile-nav li >ul').before('<div class="mob_menu_toggle_arrow" />');
    }
    window.addEventListener('resize', mob_menu);


    /* header mobile search */


    var toggleHeaderSearch = function () {
        $('#logo').after('<div class="search_mob_trigger" />');
        $('.search_mob_trigger').on('click', function () {
            if ($(this).hasClass('search-opened')) {
                $(this).removeClass('search-opened');
                $('#header-sidebar').animate({
                    height: '0px',
                    marginTop: '0px',

                });
            } else {
                $(this).addClass('search-opened');
                $('#header-sidebar').animate({
                    marginTop: '40px', //'70px',
                    height: '40px',

                });
            }

        });
    };
    toggleHeaderSearch();


    /* menu arrow in tablet version change its place */

    function changePlace() {
        var parent = $('#menu-kategorien-einfach >li >a >.sf-sub-indicator').parent();
        $('#menu-kategorien-einfach >li >a >.sf-sub-indicator').remove();
        parent.after('<span class="sf-sub-indicator" />');
    }
    changePlace();
});