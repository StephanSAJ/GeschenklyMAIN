<?php
/*
Plugin Name: Category and Tag Card (CatAndTag)
Plugin URI: http://example.com/
Description: Fügt eine detaillierte Karte zu WooCommerce-Kategorie- und Tag-Seiten hinzu – inkl. dynamischem Laden per AJAX – und nutzt React & Tailwind.
Version: 1.1.0
Author: Dein Name
Author URI: http://example.com
*/

if (!defined('ABSPATH')) exit; // Direktzugriff verhindern

// Konstanten definieren
define('MMGP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MMGP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Notwendige Dateien einbinden
require_once MMGP_PLUGIN_DIR . 'includes/admin-settings.php';
require_once MMGP_PLUGIN_DIR . 'includes/helper-functions.php';
require_once MMGP_PLUGIN_DIR . 'includes/frontend-display.php';

// Aktivierungshook
register_activation_hook(__FILE__, 'mmgp_activate_plugin');
function mmgp_activate_plugin() {
    // Aktivierungscode (z. B. Datenbanktabellen anlegen) hier
}

// Frontend-Assets laden – nur auf Kategorie-/Tag-Seiten
add_action('wp_enqueue_scripts', 'mmgp_enqueue_frontend_assets');
function mmgp_enqueue_frontend_assets() {
    if (is_product_category() || is_product_tag()) {
        wp_enqueue_script(
            'mmgp-bundle',
            MMGP_PLUGIN_URL . 'build/bundle.js',
            [],
            '1.1.0',
            true
        );
        wp_enqueue_style(
            'mmgp-bundle-styles',
            MMGP_PLUGIN_URL . 'build/bundle.css',
            [],
            '1.1.0'
        );
        wp_localize_script('mmgp-bundle', 'wcc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcc_ajax_nonce'),
        ]);
    }
}

// Preload kritischer Bilder (Kategorie oder Tag)
add_action('wp_head', 'mmgp_preload_critical_images');
function mmgp_preload_critical_images() {
    $term = get_queried_object();
    if (($term && (is_product_category() || is_product_tag()) && isset($term->term_id))) {
        $image_id = get_term_meta($term->term_id, 'wcc_image', true);
        if ($image_id && ($image_url = wp_get_attachment_url($image_id))) {
            echo '<link rel="preload" as="image" href="' . esc_url($image_url) . '">';
        }
    }
}

// Admin-Menü hinzufügen
add_action('admin_menu', 'mmgp_add_admin_menu');
function mmgp_add_admin_menu() {
    add_menu_page(
        'Category and Tag Card Settings',
        'Category & Tag Card',
        'manage_options',
        'wcc-settings',
        'wcc_settings_page'
    );
}

// AJAX-Aktionen

// Beliebte Produkte
add_action('wp_ajax_wcc_load_more_popular_products', 'wcc_ajax_load_more_popular_products');
add_action('wp_ajax_nopriv_wcc_load_more_popular_products', 'wcc_ajax_load_more_popular_products');
function wcc_ajax_load_more_popular_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $existing_ids = isset($_POST['existing_ids']) ? array_map('intval', (array)$_POST['existing_ids']) : [];

    $transient_key = "wcc_popular_{$category_id}_{$tag_id}_{$offset}_{$limit}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $products = wcc_get_category_products($category_id, 'popular', $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_products($tag_id, 'popular', $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    $new_products = array_filter($products, fn($p) => !in_array($p['id'], $existing_ids));
    $selected_products = array_slice($new_products, 0, $limit);

    set_transient($transient_key, $selected_products, HOUR_IN_SECONDS);
    wp_send_json_success(array_values($selected_products));
}

// 6-Monats-Popular Produkte
add_action('wp_ajax_wcc_load_more_six_months_popular_products', 'wcc_ajax_load_more_six_months_popular_products');
add_action('wp_ajax_nopriv_wcc_load_more_six_months_popular_products', 'wcc_ajax_load_more_six_months_popular_products');
function wcc_ajax_load_more_six_months_popular_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;

    $transient_key = "wcc_sixmonths_{$category_id}_{$tag_id}_{$offset}_{$limit}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $products = wcc_get_category_products($category_id, 'sixMonthsPopular', $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_products($tag_id, 'sixMonthsPopular', $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products);
}

// Aufsteiger (Rising) Produkte
add_action('wp_ajax_wcc_load_more_rising_products', 'wcc_ajax_load_more_rising_products');
add_action('wp_ajax_nopriv_wcc_load_more_rising_products', 'wcc_ajax_load_more_rising_products');
function wcc_ajax_load_more_rising_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;

    $transient_key = "wcc_rising_{$category_id}_{$tag_id}_{$offset}_{$limit}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $products = wcc_get_category_products($category_id, 'rising', $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_products($tag_id, 'rising', $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products);
}

// Neue Produkte
add_action('wp_ajax_wcc_load_more_new_products', 'wcc_ajax_load_more_new_products');
add_action('wp_ajax_nopriv_wcc_load_more_new_products', 'wcc_ajax_load_more_new_products');
function wcc_ajax_load_more_new_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;

    $transient_key = "wcc_new_{$category_id}_{$tag_id}_{$offset}_{$limit}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $products = wcc_get_category_products($category_id, 'new', $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_products($tag_id, 'new', $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products);
}

// Produkte filtern
add_action('wp_ajax_wcc_filter_products', 'wcc_ajax_filter_products');
add_action('wp_ajax_nopriv_wcc_filter_products', 'wcc_ajax_filter_products');
function wcc_ajax_filter_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : [];
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;

    $transient_key = "wcc_filtered_{$category_id}_{$tag_id}_{$offset}_{$limit}_" . md5(json_encode($filters));
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $products = wcc_get_filtered_products($category_id, $filters, $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_filtered_products($tag_id, $filters, $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products ?: 'Keine Produkte gefunden');
}

// Persona-Filter anwenden
add_action('wp_ajax_wcc_apply_persona_filter', 'wcc_ajax_apply_persona_filter');
add_action('wp_ajax_nopriv_wcc_apply_persona_filter', 'wcc_ajax_apply_persona_filter');
function wcc_ajax_apply_persona_filter() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;

    $transient_key = "wcc_persona_{$persona_id}_{$category_id}_{$tag_id}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    if ($category_id) {
        $personas = wcc_get_category_personas($category_id);
    } elseif ($tag_id) {
        $personas = wcc_get_tag_personas($tag_id);
    } else {
        wp_send_json_error('Invalid request');
        return;
    }

    $persona = $personas[$persona_id] ?? null;
    if ($persona && isset($persona['filters'])) {
        set_transient($transient_key, ['filters' => $persona['filters']], HOUR_IN_SECONDS);
        wp_send_json_success(['filters' => $persona['filters']]);
    } else {
        wp_send_json_error('Persona-Filter nicht gefunden');
    }
}

// Zufallsprodukte aus Kategorie
add_action('wp_ajax_wcc_get_random_products_from_category', 'wcc_get_random_products_from_category');
add_action('wp_ajax_nopriv_wcc_get_random_products_from_category', 'wcc_get_random_products_from_category');
function wcc_get_random_products_from_category() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $count = isset($_POST['count']) ? intval($_POST['count']) : 8;
    $exclude = isset($_POST['exclude']) ? array_map('intval', explode(',', $_POST['exclude'])) : [];

    $transient_key = "wcc_random_cat_{$category_id}_{$count}_" . md5(implode(',', $exclude));
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    $args = [
        'post_type' => 'product',
        'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_id]],
        'posts_per_page' => $count,
        'orderby' => 'rand',
        'post__not_in' => $exclude,
    ];

    $query = new WP_Query($args);
    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        $products[] = format_product_data($product);
    }
    wp_reset_postdata();

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products);
}

// Zufallsprodukte aus Tag
add_action('wp_ajax_wcc_get_random_products_from_tag', 'wcc_get_random_products_from_tag');
add_action('wp_ajax_nopriv_wcc_get_random_products_from_tag', 'wcc_get_random_products_from_tag');
function wcc_get_random_products_from_tag() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $count = isset($_POST['count']) ? intval($_POST['count']) : 8;
    $exclude = isset($_POST['exclude']) ? array_map('intval', explode(',', $_POST['exclude'])) : [];

    $transient_key = "wcc_random_tag_{$tag_id}_{$count}_" . md5(implode(',', $exclude));
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    $args = [
        'post_type' => 'product',
        'tax_query' => [['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id]],
        'posts_per_page' => $count,
        'orderby' => 'rand',
        'post__not_in' => $exclude,
    ];

    $query = new WP_Query($args);
    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        $products[] = format_product_data($product);
    }
    wp_reset_postdata();

    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    wp_send_json_success($products);
}

// Produktfeedback erfassen
add_action('wp_ajax_wcc_record_product_feedback', 'wcc_record_product_feedback');
add_action('wp_ajax_nopriv_wcc_record_product_feedback', 'wcc_record_product_feedback');
function wcc_record_product_feedback() {
    if (!check_ajax_referer('wcc_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $feedback_type = isset($_POST['feedback_type']) ? sanitize_text_field($_POST['feedback_type']) : '';

    if (!in_array($feedback_type, ['like', 'dislike'])) {
        wp_send_json_error(['message' => 'Invalid feedback type']);
        return;
    }

    $api_url = "https://schindler-ventures.de:3002/api/product-feedback/record";
    $data = [
        'product_id' => $product_id,
        'product_name' => $product_name,
        'category_id' => $category_id,
        'feedback_type' => $feedback_type
    ];
    $response = wp_remote_post($api_url, [
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        'body' => json_encode($data),
        'data_format' => 'body',
    ]);

    if (is_wp_error($response)) {
        error_log('Error recording feedback: ' . $response->get_error_message());
        wp_send_json_error(['message' => 'Error recording feedback']);
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            wp_send_json_success(['message' => 'Feedback recorded']);
        } else {
            error_log('API error: ' . wp_remote_retrieve_body($response));
            wp_send_json_error(['message' => 'Error recording feedback']);
        }
    }
}

// Kategorie-Details
add_action('wp_ajax_wcc_get_category_details', 'wcc_get_category_details');
add_action('wp_ajax_nopriv_wcc_get_category_details', 'wcc_get_category_details');
function wcc_get_category_details() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if (!$category_id) wp_send_json_error('Keine Kategorie angegeben.');

    $transient_key = "wcc_category_details_{$category_id}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    $term = get_term($category_id, 'product_cat');
    if (!$term || is_wp_error($term)) wp_send_json_error('Kategorie nicht gefunden.');

    $data = [
        'categoryTitle' => $term->name,
        'customHeaderText' => get_term_meta($category_id, 'wcc_custom_header_text', true),
        'categoryImage' => ($image_id = get_term_meta($category_id, 'wcc_image', true)) ? wp_get_attachment_url($image_id) : '',
        'info' => get_term_meta($category_id, 'wcc_info', true),
        'customDescription' => get_term_meta($category_id, 'wcc_custom_description', true),
        'partnerShops' => get_partner_shops($category_id),
        'subcategories' => get_subcategories_data($category_id),
    ];

    set_transient($transient_key, $data, HOUR_IN_SECONDS);
    wp_send_json_success($data);
}

// Tag-Details
add_action('wp_ajax_wcc_get_tag_details', 'wcc_get_tag_details');
add_action('wp_ajax_nopriv_wcc_get_tag_details', 'wcc_get_tag_details');

function wcc_get_tag_details() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    if (!$tag_id) {
        wp_send_json_error('Kein Tag angegeben.');
    }

    $transient_key = "wcc_tag_details_{$tag_id}";
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    $term = get_term($tag_id, 'product_tag');
    if (!$term || is_wp_error($term)) {
        wp_send_json_error('Tag nicht gefunden.');
    }

    $data = [
        'tagTitle'          => $term->name,
        'customHeaderText'  => get_term_meta($tag_id, 'wcc_custom_header_text', true),
        'tagImage'          => ($image_id = get_term_meta($tag_id, 'wcc_image', true)) ? wp_get_attachment_url($image_id) : '',
        // Die neuen Felder werden als Array unter "info" zusammengefasst:
        'info'              => [
            'contents'         => get_term_meta($tag_id, 'wcc_info_contents', true),
            'target_audience'  => get_term_meta($tag_id, 'wcc_info_target_audience', true),
            'age_group'        => get_term_meta($tag_id, 'wcc_info_age_group', true),
            'special_features' => get_term_meta($tag_id, 'wcc_info_special_features', true)
        ],
        'customDescription' => get_term_meta($tag_id, 'wcc_custom_description', true),
        'partnerShops'      => get_partner_shops($tag_id),
        'subcategories'     => get_subcategories_data($tag_id),
        'tags'              => get_related_tags($tag_id),
    ];

    set_transient($transient_key, $data, HOUR_IN_SECONDS);
    wp_send_json_success($data);
}

// Hilfsfunktion für Produktformatierung
function format_product_data($product) {
    return [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: '/placeholder-gift.jpg',
        'url' => get_permalink($product->get_id()),
        'slug' => $product->get_slug(),
        'button_text' => $product->add_to_cart_text(),
        'price' => $product->get_price_html(),
        'date' => get_the_date('d.m.Y', $product->get_id()),
    ];
}

// Hilfsfunktion für Partner-Shops
function get_partner_shops($term_id) {
    $selected_shops = get_term_meta($term_id, 'wcc_selected_partner_shops', true);
    $partner_shops = get_option('wcc_partner_shops', []);
    $shops = [];
    if (is_array($selected_shops)) {
        foreach ($selected_shops as $index) {
            if (isset($partner_shops[$index])) {
                $shop = $partner_shops[$index];
                if (isset($shop['logo'])) $shop['logo_url'] = wp_get_attachment_image_url($shop['logo'], 'medium');
                $shops[] = $shop;
            }
        }
    }
    return $shops;
}

// Hilfsfunktion für Unterkategorien
function get_subcategories_data($term_id) {
    $selected_subcategories = get_term_meta($term_id, 'wcc_selected_subcategories', true);
    $custom_tag_texts = get_term_meta($term_id, 'wcc_custom_tag_texts', true);
    $subcategories = [];
    if (is_array($selected_subcategories)) {
        foreach ($selected_subcategories as $subcat_id) {
            $subcat = get_term($subcat_id, 'product_cat');
            if ($subcat && !is_wp_error($subcat)) {
                $image_url = ($cat_image_id = get_term_meta($subcat->term_id, 'wcc_image', true)) ? wp_get_attachment_image_url($cat_image_id, 'full') : '';
                $custom_text = isset($custom_tag_texts[$subcat->term_id]) ? $custom_tag_texts[$subcat->term_id] : $subcat->description;
                $subcategories[] = [
                    'id' => $subcat->term_id,
                    'name' => $subcat->name,
                    'link' => get_term_link($subcat),
                    'image' => $image_url,
                    'description' => $custom_text,
                ];
            }
        }
    }
    return $subcategories;
}

// Hilfsfunktion für verwandte Tags
function get_related_tags($tag_id) {
    $selected_tags = get_term_meta($tag_id, 'wcc_selected_tags', true);
    $custom_tag_texts = get_term_meta($tag_id, 'wcc_custom_tag_texts', true);
    $tags = [];
    if (is_array($selected_tags)) {
        foreach ($selected_tags as $tag_id) {
            $tag = get_term($tag_id, 'product_tag');
            if ($tag && !is_wp_error($tag)) {
                $image_url = ($tag_image_id = get_term_meta($tag->term_id, 'wcc_image', true)) ? wp_get_attachment_image_url($tag_image_id, 'full') : '';
                $custom_text = isset($custom_tag_texts[$tag->term_id]) ? $custom_tag_texts[$tag->term_id] : $tag->description;
                $tags[] = [
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'link' => get_term_link($tag),
                    'image' => $image_url,
                    'description' => $custom_text,
                ];
            }
        }
    }
    return $tags;
}
?>
