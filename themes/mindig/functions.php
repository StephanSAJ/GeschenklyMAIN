<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Theme's functions.php file.
 * This file bootstrap the entire framework.
 * @package Yithemes
 */

/*
 * WARNING: This file is part of the Your Inspiration Themes framework core.
 * Edit this section at your own risk.
 */

remove_action('wp_head', 'wp_resource_hints', 2);

add_filter('BeRocket_AAPF_template_full_content', 'some_custom_berocket_aapf_template_full_content', 4000, 1);
add_filter('BeRocket_AAPF_template_full_element_content', 'some_custom_berocket_aapf_template_full_content', 4000, 1);
function some_custom_berocket_aapf_template_full_content($template_content) {

    $template_content['template']['content']['header']['content']['title']['tag'] = 'p';

    return $template_content;
}

/** Deaktiviert das WooCommerce Cart Fragments AJAX-Skript überall */
add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments_everywhere', 11);
function dequeue_woocommerce_cart_fragments_everywhere() {
    // Entfernt das Skript wc-cart-fragments.js global
    wp_dequeue_script('wc-cart-fragments');
}

/* =========================================================================
 * Geschenkly Performance Quick-Wins (Ladezeit / LCP)
 * ========================================================================= */

/**
 * Resource-Hints: frueh die Verbindung zum Matomo-Host aufbauen, damit das
 * (async geladene) Tracking-Skript nicht erst DNS+TLS aushandeln muss.
 */
add_action( 'wp_head', 'geschenkly_resource_hints', 1 );
function geschenkly_resource_hints() {
    echo '<link rel="preconnect" href="https://geschenklyanalytics.de" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="https://geschenklyanalytics.de">' . "\n";
}

/**
 * Schwere Slider-Skripte nur dort laden, wo sie gebraucht werden.
 * Laeuft mit Prioritaet 105 – nach dem Framework-Enqueue (100).
 */
add_action( 'wp_enqueue_scripts', 'geschenkly_conditional_assets', 105 );
function geschenkly_conditional_assets() {
    if ( is_admin() ) {
        return;
    }

    $is_wc = function_exists( 'is_woocommerce' )
        && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() );
    $is_utility = is_404() || is_search();

    // MasterSlider (Hero-Slider) erscheint nicht auf WooCommerce-/Funktionsseiten.
    if ( $is_wc || $is_utility ) {
        wp_dequeue_script( 'masterslider-script' );
        wp_dequeue_style( 'masterslider-style' );
    }

    // OwlCarousel/prettyPhoto auf reinen Funktionsseiten ohne Karussell entfernen.
    if ( ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || $is_utility ) {
        wp_dequeue_script( 'owl-carousel' );
        wp_dequeue_style( 'owl-slider' );
        wp_dequeue_script( 'prettyPhoto' );
        wp_dequeue_style( 'prettyPhoto' );
    }
}

/**
 * Universell sichere Frontend-Entschlackung (zuvor im nie geladenen Child-_functions.php).
 * Entfernt ungenutzte Core-Requests: wp-embed, Heartbeat (Frontend), Emoji-Skript.
 */
add_action( 'init', 'geschenkly_trim_core_assets' );
function geschenkly_trim_core_assets() {
    if ( ! is_admin() ) {
        wp_deregister_script( 'wp-embed' );
    }

    // Heartbeat nur im Editor behalten.
    global $pagenow;
    if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
        wp_deregister_script( 'heartbeat' );
    }

    // Emoji-Skript/Styles entfernen.
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'geschenkly_remove_tinymce_emoji' );
}
function geschenkly_remove_tinymce_emoji( $plugins ) {
    return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

add_action( 'send_headers', 'add_header_xua' );
function add_header_xua() {
header( 'Strict-Transport-Security: max-age=63072000; includeSubdomains; preload' );
}

//let's start the game!
require_once('core/yit.php');

function add_cors_http_header() {
    if ( ! is_admin() ) {
        header("Access-Control-Allow-Origin: https://roaring-bunny-078485.netlify.app/");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
}
add_action('init', 'add_cors_http_header');



//Optimierungen wegen LCP Bewertung
add_action('wp_head', function() {
    // Preload Logo
    $logo_url = yit_get_option('header-custom-logo-image');
    if ($logo_url) {
        echo '<link rel="preload" as="image" href="' . esc_url($logo_url) . '">';
    }
});

// Optimiere Bildgrößen
add_image_size('custom-logo', 300, 100, true);

// prevent the category widget from using the category description as the list item title attribute
function sjc_disable_cat_desc_widget_list_titles ( $cat_args ) {
    $cat_args[ 'use_desc_for_title' ] = 0;
    return $cat_args;
}
add_filter( 'widget_categories_args', 'sjc_disable_cat_desc_widget_list_titles' );

function yit_remove_num_products() {
    remove_action( 'shop-page-meta', 'yit_wc_num_of_products', 10 );
}
add_action( 'after_setup_theme', 'yit_remove_num_products', 11 );

function yit_remove_wc_catalog_ordering() {
    remove_action( 'shop-page-meta', 'yit_wc_catalog_ordering', 10 );
}

add_filter('rank_math/frontend/breadcrumb/items', function($crumbs) {
    if (is_tax('product_tag')) {
        $tag = get_queried_object();
        if ($tag) {
            $last_key = array_key_last($crumbs);
            $crumbs[$last_key][0] = $tag->name;
        }
    }
    return $crumbs;
});

add_action( 'after_setup_theme', 'yit_remove_wc_catalog_ordering', 11 );

// add_filter( 'loop_shop_columns', 'wc_loop_shop_columns', 1, 10 );




// Remove WP embed script
function speed_stop_loading_wp_embed() {
if (!is_admin()) {
wp_deregister_script('wp-embed');
}
}
add_action('init', 'speed_stop_loading_wp_embed');

add_filter( 'script_loader_tag', 'wsds_defer_scripts', 10, 3 );
function wsds_defer_scripts( $tag, $handle, $src ) {

	// The handles of the enqueued scripts we want to defer
	$defer_scripts = array(
		'script_fingerprintjs',
		'scrolldepth',
		'wwcAmzAff-frontend',
		'wpshout-js-cookie-demo',
		'thickbox',
		'admin-bar',
		'bhittani_plugin_kksr_js',
		'woocommerce',
		'prettyPhoto',
		'prettyPhoto-init',
		'jquery-selectBox',
		'jquery-yith-wcwl',
		'bootstrap-twitter',
		'yit-internal',
		'jquery-commonlibraries',
		'shortcodes',
		'owl-carousel',
		'jquery-placeholder',
		'yit-common',
		'yit_woocommerce',
		'yit_woocommerce_2_3',
		'masterslider-script',
	);

    if ( in_array( $handle, $defer_scripts ) ) {
        return '<script src="' . $src . '" defer="defer" type="text/javascript"></script>' . "\n";
    }

    return $tag;
}
add_filter( 'action_scheduler_retention_period', function() {
    return DAY_IN_SECONDS * 7; // Behält abgeschlossene Aktionen nur für 7 Tage
});

function update_category_tag() {
    global $wpdb;

    $products = $wpdb->get_results("
        SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'
    ");

    foreach ($products as $product) {
        $categories = get_the_terms($product->ID, 'product_cat');
        $tags = get_the_terms($product->ID, 'product_tag');

        // Kategorie-Ratings
        if ($categories) {
            foreach ($categories as $category) {
                $meta_key = '_category_rating_' . $category->term_id;
                if (!get_post_meta($product->ID, $meta_key, true)) {
                    add_post_meta($product->ID, $meta_key, 1);
                }
            }
        }

        // Tag-Ratings
        if ($tags) {
            foreach ($tags as $tag) {
                $meta_key = '_tag_rating_' . $tag->term_id;
                if (!get_post_meta($product->ID, $meta_key, true)) {
                    add_post_meta($product->ID, $meta_key, 1);
                }
            }
        }
    }

    wp_send_json_success(['message' => 'Ratings updated successfully']);
}


/**
 * Making short link for our Ajax url
 */
add_action( 'wp_enqueue_scripts', function(){

    wp_enqueue_script( 'rating_js', get_stylesheet_directory_uri() . '/core/assets/js/frontend/rating.js', array( 'jquery' ), '1.0' );
    wp_localize_script( 'rating_js', 'rjs', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        )
    );
});

function get_current_product_category() {
    // Get the current URL
    $current_url = $_SERVER['REQUEST_URI'];

    // Extract the category slug from the URL
    $category_slug = basename( parse_url( $current_url, PHP_URL_PATH ) );

    // Get the category information based on the slug
    $category = get_term_by( 'slug', $category_slug, 'product_cat' );

    return $category;
}

add_action('wp_ajax_update_rating'       , 'update_rating_callback');
add_action('wp_ajax_nopriv_update_rating', 'update_rating_callback');

function update_rating_callback() {
    try {
        if (!empty($_POST['rate'])) {
            global $wpdb;
            $rate = $_POST['rate'];
            $post_id = $_POST['post_id'];
            $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
            $tag_id = isset($_POST['tag_id']) ? $_POST['tag_id'] : null;

            // Update the rating for the post
            if (!empty($category_id) || !empty($tag_id))
                $wpdb->query("UPDATE n0b9xntw9aposts SET rating = rating+'$rate' WHERE ID = '$post_id'");

            // Update the rating for the category
            if (!empty($category_id)) {
                $rating = get_post_meta($post_id, '_category_rating_' . $category_id, true);
                $rating_key = '_category_rating_' . $category_id;
                $rating = !empty($rating) ? $rating : 1;

                if ($rating < 500) {
                    $result = update_post_meta($post_id, $rating_key, $rating + $rate);
                }
            }

            // Update the rating for the tag
            if (!empty($tag_id)) {
                $rating = get_post_meta($post_id, '_tag_rating_' . $tag_id, true);
                $rating_key = '_tag_rating_' . $tag_id;
                $rating = !empty($rating) ? $rating : 1;

                if ($rating < 500) {
                    $result = update_post_meta($post_id, $rating_key, $rating + $rate);
                }
            }

            // Update the rating for the homepage
            $rating = get_post_meta($post_id, '_homepage_rating', true);
            $rating_key = '_homepage_rating';
            $rating = !empty($rating) ? $rating : 1;

            if ($rating < 500) {
                $result = update_post_meta($post_id, $rating_key, $rating + $rate);
            }

            unset($_POST['rate']);

            $return = "All data wrote correctly!";
            wp_send_json_success($return);
        } else {
            alert("Fail :(");
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        wp_send_json_success($e->getMessage());
    }
}



// Change "Default Sorting" to "Crepla sorting" on shop page and in WC Product Settings
function adding_sorting_name( $catalog_orderby ) {
    $catalog_new = array('click-rate' => 'User click rate (Crepla) sorting');
    $catalog_orderby = array_merge($catalog_orderby,  $catalog_new);
    return $catalog_orderby;
}
add_filter( 'woocommerce_catalog_orderby', 'adding_sorting_name' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'adding_sorting_name' );

//Add Alphabetical sorting option to shop page / WC Product Settings
function crepla_click_rate_woocommerce_shop_ordering( $sort_args ) {



    $orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

    if ( 'click-rate' == $orderby_value ) {

        // Sorting handled later though a hook
        add_filter( 'posts_clauses', 'order_by_click_rate_post_clauses' );

    }

    return $sort_args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'crepla_click_rate_woocommerce_shop_ordering' );

/**
 * WP Core doesn't let us change the sort direction for invidual orderby params - https://core.trac.wordpress.org/ticket/17065.
 *
 * This lets us sort by meta value desc, and have a second orderby param.
 *
 * @access public
 * @param array $args
 * @return array
 */
function order_by_click_rate_post_clauses($args) {
    global $wpdb;
    $args['orderby'] = "{$wpdb->prefix}posts.rating DESC, {$wpdb->prefix}posts.post_date DESC";
    return $args;
}

//User click rating by Crepla ends

// decreasing rating of product by 5% every Thursday

add_action( 'decrease_rating_five_percent', 'decrease_user_click_rating_five_percent' );
function decrease_user_click_rating_five_percent()
{
    // Taeglicher Decay (-0,1%). Frueher: SELECT aller wp_posts + verschachtelte
    // get_post_meta/update_post_meta-Schleifen ueber alle Kategorien & Tags pro Post
    // (Hunderttausende DB-Writes/Tag). Jetzt: wenige indizierte Bulk-UPDATEs.
    global $wpdb;

    try {
        // 1) Custom rating-Spalte nur fuer veroeffentlichte Produkte abklingen lassen.
        $wpdb->query(
            "UPDATE {$wpdb->posts} SET rating = rating * 0.999
             WHERE post_type = 'product' AND post_status = 'publish'"
        );

        // 2) Kategorie- und Tag-Ratings in je EINER indizierten Query abklingen lassen.
        foreach ( array( '_category_rating_', '_tag_rating_' ) as $prefix ) {
            $like = $wpdb->esc_like( $prefix ) . '%';
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = meta_value * 0.999
                     WHERE meta_key LIKE %s AND meta_value <> '' AND ( meta_value + 0 ) > 0",
                    $like
                )
            );
        }

        // 3) Homepage-Rating.
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta} SET meta_value = meta_value * 0.999
                 WHERE meta_key = %s AND meta_value <> '' AND ( meta_value + 0 ) > 0",
                '_homepage_rating'
            )
        );
    } catch ( Exception $e ) {
        error_log( 'Geschenkly rating decay error: ' . $e->getMessage() );
    }
}



if( !wp_next_scheduled( 'decrease_rating_five_percent' ) ) {
    // Schedule the event
    wp_schedule_event( time(), 'daily', 'decrease_rating_five_percent' );
    }



add_action( 'wp_enqueue_scripts', 'mindig_child_wp_enqueue_scripts' );




/**
 * Lädt Child-Theme-Ressourcen nur auf einer spezifischen Seite.
 * Nutzt korrekte Theme-Verzeichnis-URIs.
 */
function mindig_child_wp_enqueue_scripts() {
    // Prüft, ob wir uns auf der Seite mit dem Slug 'anna-deine-geschenk-beraterin' befinden
	if ( is_page( 'anna-deine-geschenk-beraterin' ) ) {
        
        // Holt den korrekten URI des Child Themes (z.B. https://ihredomain.de/wp-content/themes/mindig-child/)
        $child_theme_uri = get_stylesheet_directory_uri();
        
        // Enqueue Style: Geht davon aus, dass die CSS-Datei unter /theme/assets/css/ liegt
		wp_enqueue_style( 
            'mindig-child-main', 
            $child_theme_uri . '/theme/assets/css/mindig-child-main.css',
            array(), // Keine Abhängigkeiten (kann leer bleiben)
            '1.0'    // Versionsnummer für Cache-Busting (kann 'false' sein, aber besser eine Version)
        );
        
        // Enqueue Script: Geht davon aus, dass die JS-Datei unter /theme/assets/js/ liegt
		wp_enqueue_script( 
            'mindig-child-main', 
            $child_theme_uri . '/theme/assets/js/mindig-child-main.js', 
            array( 'jquery' ), // Abhängig von JQuery
            '1.0',             // Versionsnummer
            true               // Lädt im Footer (gut für Performance)
        );
	}
}

add_filter( 'berocket_wp_head_canonical', 'berocket_wp_head_canonical_3224234rw34' );

function berocket_wp_head_canonical_3224234rw34() {

    return false;

}

/* ab hier bis zur Mittteilung löschbar */

function custom_category_product_sorting( $args ) {
    if ( is_product_category() ) {
        // Existing code for product categories
        $term_id = get_queried_object_id();
        $products = get_posts( array(
            'post_type' => 'product',
            'numberposts' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $term_id,
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => 'Kategoriebewertung',
                    'compare' => 'EXISTS',
                ),
            ),
            'meta_key' => 'Kategoriebewertung',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
        ) );
        if ( $products ) {
            $args['post__in'] = wp_list_pluck( $products, 'ID' );
        } else {
            // Sort by order_by_click_rate_post_clauses if no rating exists
            $args = order_by_click_rate_post_clauses($args);
        }
    } elseif ( is_shop() || is_front_page() ) {
        // Use order_by_click_rate_post_clauses for the homepage
        $args = order_by_click_rate_post_clauses($args);
    }
    return $args;
}


add_filter( 'woocommerce_get_catalog_ordering_args', 'custom_category_product_sorting' );

/* Ende der Mitteilung - bis hier in löschen möglich */

// Erstelle ein benutzerdefiniertes Metadatenfeld für Produktbewertungen je nach Kategorie
add_action('woocommerce_product_options_general_product_data', 'custom_product_rating_by_category');
function custom_product_rating_by_category() {
    global $post;

    echo '<div class="options_group">';

    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ));

    if (!empty($categories)) {
        foreach ($categories as $category) {
            $rating = get_post_meta($post->ID, '_category_rating_' . $category->term_id, true);
            woocommerce_wp_text_input(array(
                'id' => '_category_rating_' . $category->term_id,
                'label' => 'Bewertung für ' . $category->name . ' (0-500)',
                'placeholder' => '',
                'description' => '',
                'value' => !empty($rating) ? $rating : '1'
            ));
        }
    }

    echo '</div>';
}

// Speichere die benutzerdefinierten Bewertungen für das Produkt je nach Kategorie
add_action('woocommerce_process_product_meta', 'save_custom_product_rating_by_category', 10, 2);
function save_custom_product_rating_by_category($post_id, $post) {
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ));

    if (!empty($categories)) {
        foreach ($categories as $category) {
            $rating_key = '_category_rating_' . $category->term_id;

            if (isset($_POST[$rating_key])) {
                $rating = floatval($_POST[$rating_key]);

                if ($rating >= 0 && $rating <= 500) {
                    update_post_meta($post_id, $rating_key, $rating);
                } else {
                    delete_post_meta($post_id, $rating_key);
                }
            }
        }
    }
}

// Erstelle ein benutzerdefiniertes Metadatenfeld für Produktbewertungen je nach Schlagwort
add_action('woocommerce_product_options_general_product_data', 'custom_product_rating_by_tag');
function custom_product_rating_by_tag() {
    global $post;

    echo '<div class="options_group">';

    $tags = get_terms(array(
        'taxonomy' => 'product_tag',
        'hide_empty' => false
    ));

    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $rating = get_post_meta($post->ID, '_tag_rating_' . $tag->term_id, true);
            woocommerce_wp_text_input(array(
                'id' => '_tag_rating_' . $tag->term_id,
                'label' => 'Bewertung für ' . $tag->name . ' (0-500)',
                'placeholder' => '',
                'description' => '',
                'value' => !empty($rating) ? $rating : '1'
            ));
        }
    }

    echo '</div>';
}

// Speichere die benutzerdefinierten Bewertungen für das Produkt je nach Schlagwort
add_action('woocommerce_process_product_meta', 'save_custom_product_rating_by_tag', 10, 2);
function save_custom_product_rating_by_tag($post_id, $post) {
    $tags = get_terms(array(
        'taxonomy' => 'product_tag',
        'hide_empty' => false
    ));

    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $rating_key = '_tag_rating_' . $tag->term_id;

            if (isset($_POST[$rating_key])) {
                $rating = floatval($_POST[$rating_key]);

                if ($rating >= 0 && $rating <= 500) {
                    update_post_meta($post_id, $rating_key, $rating);
                } else {
                    delete_post_meta($post_id, $rating_key);
                }
            }
        }
    }
}
// Erstelle ein benutzerdefiniertes Metadatenfeld für die Produktbewertung auf der Startseite
add_action('woocommerce_product_options_general_product_data', 'custom_product_rating_for_homepage');
function custom_product_rating_for_homepage() {
    global $post;

    echo '<div class="options_group">';

    $rating = get_post_meta($post->ID, '_homepage_rating', true);
    woocommerce_wp_text_input(array(
        'id' => '_homepage_rating',
        'label' => 'Bewertung für Startseite (0-500)',
        'placeholder' => '',
        'description' => '',
        'value' => !empty($rating) ? $rating : '1'
    ));

    echo '</div>';
}

// Speichere die benutzerdefinierte Startseitenbewertung für das Produkt
add_action('woocommerce_process_product_meta', 'save_custom_product_rating_for_homepage', 10, 2);
function save_custom_product_rating_for_homepage($post_id, $post) {
    $rating_key = '_homepage_rating';

    if (isset($_POST[$rating_key])) {
        $rating = floatval($_POST[$rating_key]);

        if ($rating >= 0 && $rating <= 500) {
            update_post_meta($post_id, $rating_key, $rating);
        } else {
            delete_post_meta($post_id, $rating_key);
        }
    }
}


/**
 * Liefert den Meta-Key fuer die Archiv-Sortierung.
 *
 * Standard: bisheriges Verhalten (per-Kategorie/Tag/Homepage-Rating).
 * Optional (Option 'geschenkly_use_event_sort' == 'yes'): zentraler, vom
 * Analytics-Plugin gepflegter Popularitaets-Score '_geschenkly_pop_score'
 * aus der indizierten Event-Tabelle. Erst nach Staging-Verifikation aktivieren.
 */
function geschenkly_sort_meta_key( $default_key ) {
    return ( get_option( 'geschenkly_use_event_sort' ) === 'yes' )
        ? '_geschenkly_pop_score'
        : $default_key;
}

// Füge benutzerdefinierte Sortierung nach Bewertungen je nach Kategorie hinzu
add_action('pre_get_posts', 'sort_products_by_custom_category_rating');
function sort_products_by_custom_category_rating($query) {
    if (!is_admin() && $query->is_main_query() && is_product_category()) {
        $queried_object = get_queried_object();
        $category_id = $queried_object->term_id;

        // Ändere die Abfrage, um Produkte nach der benutzerdefinierten Bewertung für die aktuelle Kategorie zu sortieren
        $query->set('meta_key', geschenkly_sort_meta_key('_category_rating_' . $category_id));
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC'); // Sortiere absteigend (höchste Bewertung zuerst)
    }
}

function get_post_categories($post_id) {
    $categories = get_the_category($post_id);
    if(!empty($categories)) {
        return $categories;
    }
    return array();
}
add_action('pre_get_posts', 'sort_products_by_custom_tag_rating');
function sort_products_by_custom_tag_rating($query) {
    if (!is_admin() && $query->is_main_query() && is_product_tag()) {
        $queried_object = get_queried_object();
        $tag_id = $queried_object->term_id;

        // Ändere die Abfrage, um Produkte nach der benutzerdefinierten Bewertung für das aktuelle Schlagwort zu sortieren
        $query->set('meta_key', geschenkly_sort_meta_key('_tag_rating_' . $tag_id));
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC'); // Sortiere absteigend (höchste Bewertung zuerst)
    }
}

function get_post_tags($post_id) {
    $tags = get_the_tags($post_id);
    if(!empty($tags)) {
        return $tags;
    }
    return array();
}

// Funktion zum Anzeigen der Anzahl der Beiträge in einer Kategorie
function show_post_count_in_category( $category_id ) {
    $args = array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'cat'                    => $category_id,
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    $posts = new WP_Query( $args );
    $post_count = $posts->post_count;

    return $post_count;
}

// Funktion zum Anzeigen der Anzahl der Produkte in einer WooCommerce-Kategorie
function show_product_count_in_category( $category_id ) {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        ),
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    $products = new WP_Query( $args );
    $product_count = $products->post_count;

    return $product_count;
}

// Funktion zum Anzeigen der Anzahl der Produkte in einer WooCommerce-Kategorie oder einem Schlagwort
function show_product_count_in_term( $term_id, $taxonomy ) {
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    $products = new WP_Query( $args );
    $product_count = $products->post_count;

    return $product_count;
}

// Shortcode zum Anzeigen der Anzahl der Produkte in einer WooCommerce-Kategorie oder einem Schlagwort
function product_count_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'term_id' => '',
        'taxonomy' => 'product_cat',
    ), $atts, 'product_count' );

    if ( empty( $atts['term_id'] ) ) {
        $queried_object = get_queried_object();

        if ( is_product_category() || is_product_tag() ) {
            $atts['term_id'] = $queried_object->term_id;
            $atts['taxonomy'] = $queried_object->taxonomy;
        }
    }

    if ( ! empty( $atts['term_id'] ) ) {
        return show_product_count_in_term( $atts['term_id'], $atts['taxonomy'] );
    } else {
        return 'Bitte geben Sie eine Term-ID an.';
    }
}
add_shortcode( 'product_count', 'product_count_shortcode' );

function rank_math_process_shortcodes_in_meta( $content ) {
    return do_shortcode( $content );
}
add_filter( 'rank_math/frontend/description', 'rank_math_process_shortcodes_in_meta', 10, 1 );
add_filter( 'rank_math/frontend/title', 'rank_math_process_shortcodes_in_meta', 10, 1 );

// Füge benutzerdefinierte Sortierung nach Startseitenbewertung hinzu
add_action('pre_get_posts', 'sort_products_by_custom_homepage_rating');
function sort_products_by_custom_homepage_rating($query) {
    if (!is_admin() && $query->is_main_query() && is_shop()) {
        $query->set('meta_key', geschenkly_sort_meta_key('_homepage_rating'));
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'DESC'); // Sortiere absteigend (höchste Bewertung zuerst)
    }
}


// Hier werden WebP Uploads unterstützt
function add_webp_support($mimes) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('upload_mimes', 'add_webp_support');

/**
 * Lazy Loading & LCP Optimierung (Optimierte Version)
 * Stellt sicher, dass Featured Images immer EAGER sind und der Zähler
 * nur für die nachfolgenden Content-Bilder verwendet wird.
 */
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    static $image_count = 0; // Zähler pro Seitenaufruf

    // 1. Featured Images (wp-post-image) IMMER auf eager/high setzen 
    if (isset($attr['class']) && strpos($attr['class'], 'wp-post-image') !== false) {
        $attr['loading'] = 'eager';
        $attr['fetchpriority'] = 'high';
        // Featured Images benötigen keine weitere Zählung oder Verarbeitung hier
        return $attr; 
    }
    
    // Zähler nur für Content-Bilder erhöhen, die nicht Featured Images sind
    $image_count++; 
    
    // Standardmäßig Lazy Loading für alle Content-Bilder
    $attr['loading'] = 'lazy';

    // 2. Die ersten 4 *Content-Bilder* eager laden (nach den Featured Images)
    if ($image_count <= 4) {
        $attr['loading'] = 'eager';
        $attr['fetchpriority'] = 'high';
    }

    // WICHTIG: Die Attribute müssen zurückgegeben werden (einschließlich srcset und sizes)
    return $attr;
}, 10, 3);
add_action('wp_enqueue_scripts', function() {
    if (!is_admin()) {

        // sticky header script verzögern
        add_filter('rocket_delay_js_exclusions', function($exclude) {
            $exclude[] = 'internal.js';
            $exclude[] = 'common.js';
            return $exclude;
        });

    }
});
/**
 * 1. Deaktiviert die Lightbox, den Zoom und den Slider der Produktgalerie
 * auf der WooCommerce Einzelproduktseite.
 */
function wc_remove_product_gallery_features() {
    // Entfernt das Lightbox-Skript
    remove_action( 'wp_enqueue_scripts', 'woocommerce_photoswipe_scripts', 15 );
    
    // Entfernt die Zoom-Funktionalität
    remove_theme_support( 'wc-product-gallery-zoom' );
    
    // Entfernt die Lightbox-Funktionalität
    remove_theme_support( 'wc-product-gallery-lightbox' );
    
    // Entfernt die Slider-Funktionalität (optional, aber empfohlen)
    remove_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'wc_remove_product_gallery_features', 10 );

/**
 * 2. Deaktiviert die Produkt-Tabs auf der WooCommerce Einzelproduktseite.
 * (Entfernt Standard-Tabs: Beschreibung, Zusätzliche Infos, Bewertungen)
 */
function wc_remove_all_product_tabs( $tabs ) {
    // Entfernt den Beschreibungs-Tab
    unset( $tabs['description'] );       
    
    // Entfernt den Tab für Zusätzliche Informationen
    unset( $tabs['additional_information'] ); 
    
    // Entfernt den Tab für Bewertungen
    unset( $tabs['reviews'] );           
    
    return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'wc_remove_all_product_tabs', 98 );
/**
 * 3. Deaktiviert das Standard-CSS von WooCommerce-Widgets.
 * ACHTUNG: Kann das Layout von Widgets zerstören, wenn das Theme es nicht ersetzt.
 */
// Temporär einkommentiert lassen, um zu sehen, ob es Probleme verursacht.

// Fügen Sie diesen Code in die functions.php Ihres Themes ein

function mindig_affiliate_tracking_script() {
    // Stellen Sie sicher, dass wir auf einer Einzelproduktseite sind
    if ( is_product() ) {
        // Registrieren und Enqueue das Tracking-Skript
        wp_enqueue_script( 
            'mindig-affiliate-tracker', 
            get_stylesheet_directory_uri() . '/js/affiliate-tracker.js', // Pfad zur JS-Datei
            array('jquery'), 
            null, 
            true // true = Im Footer laden
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mindig_affiliate_tracking_script' );

function wc_remove_widget_styles() {
    wp_dequeue_style( 'woocommerce-general' );
    wp_dequeue_style( 'woocommerce-layout' );
    wp_dequeue_style( 'woocommerce-smallscreen' );
}
add_action( 'wp_enqueue_scripts', 'wc_remove_widget_styles', 99 );


/**
 * Stellt sicher, dass die WordPress Core-Funktionen für Responsive Images
 * (srcset/sizes) wieder aktiv sind, falls sie von einem anderen Plugin/Theme 
 * entfernt wurden.
 */
function crepla_re_enable_responsive_images() {
    // Stellt sicher, dass die Generierung der srcset-Attribute wieder funktioniert
    add_filter( 'wp_calculate_image_srcset', 'wp_calculate_image_srcset', 10, 5 );
    
    // Stellt sicher, dass die Generierung der sizes-Attribute wieder funktioniert
    add_filter( 'wp_calculate_image_sizes', 'wp_calculate_image_sizes', 10, 5 );

    // Stellt sicher, dass Bilder, die im Content (Editor) eingefügt werden, 
    // das responsive Markup erhalten
    add_filter( 'the_content', 'wp_make_content_images_responsive', 10 );
}
add_action( 'init', 'crepla_re_enable_responsive_images' );

add_action( 'wp_footer', 'geschenkly_mobile_menu_fix', 99 );
function geschenkly_mobile_menu_fix() {
    ?>
    <script>
    jQuery(document).ready(function($) {

        // Warten bis internal.js seinen Klon erstellt und ins st-menu eingefügt hat
        // internal.js läuft in $(document).ready, wir laufen mit priority 99 danach

        var $mobileNav = $('.st-menu .mobile-nav');
        if (!$mobileNav.length) return;

        // 1. Doppelte mob_menu_toggle_arrow entfernen (falls welche schon drin sind)
        $mobileNav.find('.mob_menu_toggle_arrow + .mob_menu_toggle_arrow').remove();

        // 2. div.submenu → ul.sub-menu direkt im li umstrukturieren
        //    (nötig wenn gecachtes Menü noch div.submenu-Struktur hat)
        $mobileNav.find('li.menu-item-has-children').each(function() {
            var $li = $(this);
            var $submenuDiv = $li.children('div.submenu');
            if ($submenuDiv.length) {
                var $innerUl = $submenuDiv.children('ul.sub-menu');
                if ($innerUl.length) {
                    $submenuDiv.replaceWith($innerUl);
                }
            }
        });

        // 3. Pfeile einfügen wo noch keine sind
        $mobileNav.find('li > ul').each(function() {
            if ($(this).prev('.mob_menu_toggle_arrow').length === 0) {
                $(this).before('<div class="mob_menu_toggle_arrow" />');
            }
        });

        // 4. Alle Click-Handler neu registrieren (alte überschreiben)
        $mobileNav.find('.mob_menu_toggle_arrow').off('click').on('click', function() {
            var $this = $(this);
            if ($this.hasClass('opened')) {
                $this.removeClass('opened');
            } else {
                $this.next().find('.mob_menu_toggle_arrow').removeClass('opened');
                $this.parent().parent().find('.mob_menu_toggle_arrow').removeClass('opened');
                $this.addClass('opened');
            }
        });

    });
    </script>
    <?php
}

function geschenkly_dequeue_scripts() {
    // 1. und 2. WooCommerce: AJAX Hinzufügen zum Warenkorb (entfernt die Funktion auf Archiv-Seiten)
    // Wenn Sie Affiliate-Produkte nutzen und keinen lokalen Warenkorb, kann dies sicher entfernt werden.
    wp_dequeue_script( 'add-to-cart' ); 
    wp_dequeue_script( 'woocommerce-add-to-cart' );

    // 3. WooCommerce: Warenkorb-Fragmente (Deaktiviert die Live-Aktualisierung des Mini-Warenkorbs)
    wp_dequeue_script( 'wc-cart-fragments' );

    // 4. WordPress: Embeds (Standard-Optimierung - sicher zu entfernen auf dem Front-End)
    wp_dequeue_script( 'wp-embed' );
}
// Hängt die Funktion in den Prozess des Ladens von Skripten ein.
add_action( 'wp_enqueue_scripts', 'geschenkly_dequeue_scripts', 999 );

// Für 'wp-embed' gibt es oft noch eine zweite Methode für den Block-Editor (Gutenberg)
function geschenkly_disable_gutenberg_embeds() {
    remove_action( 'enqueue_block_assets', 'wp_enqueue_block_css' );
}
add_action( 'init', 'geschenkly_disable_gutenberg_embeds' );

function geschenkly_prevent_photoswipe_enqueue_on_homepage() {
    if ( is_front_page() ) {
        // Die Funktion, die PhotoSwipe in WooCommerce in die Warteschlange einreiht,
        // ist 'woocommerce_photoswipe_assets' mit der Standardpriorität 10.
        // Wir entfernen diese Aktion explizit auf der Startseite.
        remove_action( 'wp_enqueue_scripts', 'woocommerce_photoswipe_assets' );

        // Da Sie diese Zeilen bereits hatten und sie keinen Schaden anrichten,
        // können Sie die alte Dequeue-Logik beibehalten, falls andere Plugins
        // die Datei auf andere Weise laden:
        wp_dequeue_style( 'photoswipe' );
        wp_dequeue_style( 'photoswipe-default-skin' );
        wp_dequeue_script( 'photoswipe' );
        wp_dequeue_script( 'photoswipe-ui-default' );
        wp_dequeue_script( 'wc-photoswipe' );
    }
}
// Wichtig: Wir hängen uns mit der niedrigsten Priorität (1) in den Hook ein,
// um sicherzustellen, dass wir die WooCommerce-Funktion entfernen, bevor sie ausgeführt wird (Priorität 10).
add_action( 'wp_enqueue_scripts', 'geschenkly_prevent_photoswipe_enqueue_on_homepage', 1 );

add_filter( 'rank_math/frontend/breadcrumb/items', function( $links, $breadcrumbs ) {
    if ( is_product_tag() && isset($links[1][0]) ) {
        $links[1][0] = ltrim($links[1][0], ';');
    }
    return $links;
}, 20, 2 );

?>