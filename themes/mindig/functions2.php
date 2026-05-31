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

// Cache-Header setzen
add_action('send_headers', function() {
    header('Cache-Control: public, max-age=31536000');
}); 

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
		'jquery',
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

Das ist eine sehr wichtige Frage, da die von Ihnen bereitgestellte Funktion eine schwerwiegende Sicherheitslücke (SQL-Injection) enthält und eine inkonsistente Tabellenbenennung aufweist.

Hier ist die korrekte Version der Funktion update_rating_callback(), die sicher ist, WordPress-Standards verwendet und die Logik beibehält:

✅ Korrigierte update_rating_callback() Funktion
PHP

function update_rating_callback() {
    // Fängt Fehler ab und gibt sie an den Client zurück
    try {
        // Prüfen, ob der 'rate'-Wert vorhanden ist
        if (!isset($_POST['rate']) || empty($_POST['rate'])) {
             // Verwenden Sie wp_send_json_error für Fehler
             wp_send_json_error(['message' => 'Bewertungs-Wert fehlt.']);
        }
        
        global $wpdb;

        // 1. Eingaben sichern und bereinigen (WICHTIG!)
        // floatval für die Rate, da sie im Dezimalbereich liegen kann (auch wenn Sie im Beispiel nur +1 verwenden)
        // intval für IDs
        $rate = floatval($_POST['rate']);
        $post_id = intval($_POST['post_id']);
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : null;

        // Zusätzliche grundlegende Validierung
        if ( $post_id <= 0 ) {
            wp_send_json_error(['message' => 'Ungültige Post-ID.']);
        }
        
        // 2. Rating für den Post-Typ 'product' in der Haupttabelle aktualisieren
        // KORREKTUR: Verwenden Sie $wpdb->posts und $wpdb->prepare() zum Schutz vor SQL-Injections
        if (!empty($category_id) || !empty($tag_id)) {
            // Die Abfrage muss korrekt vorbereitet werden: %f für float, %d für integer
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts} SET rating = rating + %f WHERE ID = %d", 
                    $rate, 
                    $post_id
                )
            );
        }

        // 3. Rating für die Kategorie-Meta aktualisieren
        if (!empty($category_id)) {
            $rating_key = '_category_rating_' . $category_id;
            
            // Holen des aktuellen Wertes
            $current_rating = floatval(get_post_meta($post_id, $rating_key, true));
            $current_rating = ($current_rating > 0) ? $current_rating : 1; // Sicherstellen, dass er nicht 0 ist, falls leer

            if ($current_rating < 500) {
                $new_rating = min(500, $current_rating + $rate); // Stelle sicher, dass das Limit nicht überschritten wird
                update_post_meta($post_id, $rating_key, $new_rating);
            }
        }

        // 4. Rating für die Tag-Meta aktualisieren
        if (!empty($tag_id)) {
            $rating_key = '_tag_rating_' . $tag_id;
            
            // Holen des aktuellen Wertes
            $current_rating = floatval(get_post_meta($post_id, $rating_key, true));
            $current_rating = ($current_rating > 0) ? $current_rating : 1; // Sicherstellen, dass er nicht 0 ist, falls leer

            if ($current_rating < 500) {
                $new_rating = min(500, $current_rating + $rate); // Stelle sicher, dass das Limit nicht überschritten wird
                update_post_meta($post_id, $rating_key, $new_rating);
            }
        }

        // 5. Rating für die Startseite-Meta aktualisieren
        $rating_key = '_homepage_rating';
        $current_rating = floatval(get_post_meta($post_id, $rating_key, true));
        $current_rating = ($current_rating > 0) ? $current_rating : 1; // Sicherstellen, dass er nicht 0 ist, falls leer

        if ($current_rating < 500) {
            $new_rating = min(500, $current_rating + $rate); // Stelle sicher, dass das Limit nicht überschritten wird
            update_post_meta($post_id, $rating_key, $new_rating);
        }

        // 6. Erfolgsmeldung senden
        wp_send_json_success(['message' => 'Alle Daten korrekt aktualisiert!', 'post_id' => $post_id]);

    } catch (Exception $e) {
        // Fehler bei der Verarbeitung abfangen
        wp_send_json_error(['message' => 'Es ist ein Fehler aufgetreten: ' . $e->getMessage()]);
    }
    
    // Ajax-Funktionen MÜSSEN mit wp_die() oder einer wp_send_json_... Funktion enden
    wp_die();
}
// Die Actions bleiben gleich:
// add_action('wp_ajax_update_rating'      , 'update_rating_callback');
// add_action('wp_ajax_nopriv_update_rating', 'update_rating_callback');

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

// decreasing rating of product by 0,1% every Thursday

add_action( 'decrease_rating_five_percent', 'decrease_user_click_rating_five_percent' );
function decrease_user_click_rating_five_percent()
{
    try {
        global $wpdb;

        $decrease_percent = 0.001;

        // Decrease the rating for the post
        $table_name = $wpdb->prefix . 'posts';
        $wpdb->query("UPDATE $table_name SET rating = rating * (1 - $decrease_percent)");

        // Get all the posts
        $posts = $wpdb->get_results("SELECT ID FROM $table_name");

        foreach ($posts as $post) {
            $post_id = $post->ID;

            // Decrease the rating for categories
            $categories = get_the_terms($post_id, 'product_cat');
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $category_id = $category->term_id;
                    $rating_key = '_category_rating_' . $category_id;
                    $rating = get_post_meta($post_id, $rating_key, true);

                    if (!empty($rating)) {
                        $new_rating = $rating * (1 - $decrease_percent);
                        update_post_meta($post_id, $rating_key, $new_rating);
                    }
                }
            }

            // Decrease the rating for tags
            $tags = get_the_terms($post_id, 'product_tag');
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tag_id = $tag->term_id;
                    $rating_key = '_tag_rating_' . $tag_id;
                    $rating = get_post_meta($post_id, $rating_key, true);

                    if (!empty($rating)) {
                        $new_rating = $rating * (1 - $decrease_percent);
                        update_post_meta($post_id, $rating_key, $new_rating);
                    }
                }
            }
			// Decrease the rating for the homepage
            $rating_key = '_homepage_rating';
            $rating = get_post_meta($post_id, $rating_key, true);

            if (!empty($rating)) {
                $new_rating = $rating * (1 - $decrease_percent);
                update_post_meta($post_id, $rating_key, $new_rating);
            }
        }

        echo "Ratings successfully decreased by 5%.";

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}



if( !wp_next_scheduled( 'decrease_rating_five_percent' ) ) {
    // Schedule the event
    wp_schedule_event( time(), 'daily', 'decrease_rating_five_percent' );
    }



add_action( 'wp_enqueue_scripts', 'mindig_child_wp_enqueue_scripts' );




function mindig_child_wp_enqueue_scripts() {
	if ( is_page( 'anna-deine-geschenk-beraterin' ) ) {
		$url = str_replace( array( 'http:', 'https:' ), '', get_stylesheet_directory_uri() );
		wp_enqueue_style( 'mindig-child-main', "https://geschenkly.de/wp-content/themes/mindig/theme/assets/css/mindig-child-main.css" );
		wp_enqueue_script( 'mindig-child-main', "https://geschenkly.de/wp-content/themes/mindig/theme/assets/js/mindig-child-main.js", array( 'jquery' ), false, true );
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


// Füge benutzerdefinierte Sortierung nach Bewertungen je nach Kategorie hinzu
add_action('pre_get_posts', 'sort_products_by_custom_category_rating');
function sort_products_by_custom_category_rating($query) {
    if (!is_admin() && $query->is_main_query() && is_product_category()) {
        $queried_object = get_queried_object();
        $category_id = $queried_object->term_id;

        // Ändere die Abfrage, um Produkte nach der benutzerdefinierten Bewertung für die aktuelle Kategorie zu sortieren
        $query->set('meta_key', '_category_rating_' . $category_id);
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
        $query->set('meta_key', '_tag_rating_' . $tag_id);
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
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'cat'            => $category_id,
        'posts_per_page' => -1,
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
        'posts_per_page' => -1,
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
        'posts_per_page' => -1,
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
        $query->set('meta_key', '_homepage_rating');
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

// Lazy Loading & LCP Optimierung
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    static $image_count = 0; // Zähler pro Seite
    $image_count++;

    // Standardmäßig Lazy Loading
    $attr['loading'] = 'lazy';

    // Die ersten 4 Produktbilder eager laden & hohe Priorität
    if ($image_count <= 4) {
        $attr['loading'] = 'eager';
        $attr['fetchpriority'] = 'high';
    }

    // Featured Images immer eager und hohe Priorität
    if (isset($attr['class']) && strpos($attr['class'], 'wp-post-image') !== false) {
        $attr['loading'] = 'eager';
        $attr['fetchpriority'] = 'high';
    }

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
