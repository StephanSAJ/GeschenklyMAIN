<?php
/*
Plugin Name: Geschenkly Term Card
Plugin URI: https://geschenkly.com/
Description: Fügt eine detaillierte Karte zu WooCommerce-Kategorie- und Tag-Seiten hinzu.
Version: 1.1
Author: Ihr Name
Author URI: https://geschenkly.com/
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define constants
define('GTC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GTC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once(GTC_PLUGIN_DIR . 'includes/admin-settings.php');
require_once(GTC_PLUGIN_DIR . 'includes/frontend-display.php');
require_once(GTC_PLUGIN_DIR . 'includes/helper-functions.php');
require_once(GTC_PLUGIN_DIR . 'includes/migration.php');

// Activation hook
register_activation_hook(__FILE__, 'gtc_activate_plugin');

function gtc_activate_plugin() {
    gtc_migrate_data();
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'gtc_enqueue_scripts');

function gtc_enqueue_scripts() {
    wp_enqueue_style('gtc-styles', GTC_PLUGIN_URL . 'assets/css/term-card.css');
    wp_enqueue_script('gtc-scripts', GTC_PLUGIN_URL . 'assets/js/term-card.js', array('jquery'), '1.1', true);
    wp_localize_script('gtc-scripts', 'gtc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gtc_ajax_nonce')
    ));
}

// Add admin menu
add_action('admin_menu', 'gtc_add_admin_menu');

function gtc_add_admin_menu() {
    add_menu_page('Term Card Einstellungen', 'Term Card', 'manage_options', 'gtc-settings', 'gtc_settings_page');
}

// Register REST API endpoints
add_action('rest_api_init', 'gtc_register_rest_endpoints');

function gtc_register_rest_endpoints() {
    $meta_fields = array(
        'gtc_enable_card',
        'gtc_image',
        'gtc_info',
        'gtc_selected_sub_terms',
        'gtc_custom_sub_term_texts',
        'gtc_personas',
        'gtc_custom_description',
        'gtc_custom_header_text',
        'gtc_selected_partner_shops'
    );

    foreach (['product_cat', 'product_tag'] as $taxonomy) {
        foreach ($meta_fields as $field) {
            register_rest_field($taxonomy, $field, array(
                'get_callback' => function ($data) use ($field) {
                    $term_id = $data['id'];
                    $value = get_term_meta($term_id, $field, true);
                    if ($field === 'gtc_image' && $value) {
                        $value = array(
                            'id' => $value,
                            'url' => wp_get_attachment_url($value)
                        );
                    } elseif ($field === 'gtc_selected_partner_shops' && $value) {
                        $partner_shops = get_option('gtc_partner_shops', []);
                        $selected_shops = [];
                        foreach ((array)$value as $index) {
                            if (isset($partner_shops[$index])) {
                                $shop = $partner_shops[$index];
                                $shop['logo'] = !empty($shop['logo']) ? wp_get_attachment_url($shop['logo']) : '';
                                $selected_shops[] = $shop;
                            }
                        }
                        $value = $selected_shops;
                    } elseif ($field === 'gtc_selected_sub_terms' && $value) {
                        $sub_terms = [];
                        foreach ((array)$value as $sub_term_id) {
                            $sub_term = get_term($sub_term_id);
                            if ($sub_term && !is_wp_error($sub_term)) {
                                $sub_terms[] = array(
                                    'id' => $sub_term->term_id,
                                    'name' => $sub_term->name,
                                    'slug' => $sub_term->slug,
                                    'taxonomy' => $sub_term->taxonomy
                                );
                            }
                        }
                        $value = $sub_terms;
                    }
                    return $value ?: ($field === 'gtc_enable_card' ? '0' : []);
                },
                'schema' => null,
            ));
        }
    }

    register_rest_route('wp/v2', '/options/gtc_partner_shops', array(
        'methods' => 'GET',
        'callback' => 'gtc_get_partner_shops',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gtc/v1', '/terms', array(
        'methods' => 'GET',
        'callback' => 'gtc_get_terms_data',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('gtc/v1', '/partner-shops', array(
        'methods' => 'GET',
        'callback' => 'gtc_get_partner_shops',
        'permission_callback' => '__return_true',
    ));
}

function gtc_get_terms_data($request) {
    $terms = get_terms([
        'taxonomy' => ['product_cat', 'product_tag'],
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return new WP_Error('no_terms', 'Keine Begriffe gefunden', array('status' => 404));
    }

    $result = array_map(function ($term) {
        $meta = [];
        $fields = [
            'gtc_enable_card', 'gtc_image', 'gtc_info', 'gtc_selected_sub_terms',
            'gtc_custom_sub_term_texts', 'gtc_personas', 'gtc_custom_description',
            'gtc_custom_header_text', 'gtc_selected_partner_shops'
        ];
        foreach ($fields as $field) {
            $value = get_term_meta($term->term_id, $field, true);
            if ($field === 'gtc_image' && $value) {
                $value = array('id' => $value, 'url' => wp_get_attachment_url($value));
            } elseif ($field === 'gtc_selected_partner_shops' && $value) {
                $partner_shops = get_option('gtc_partner_shops', []);
                $selected_shops = [];
                foreach ((array)$value as $index) {
                    if (isset($partner_shops[$index])) {
                        $shop = $partner_shops[$index];
                        $shop['logo'] = !empty($shop['logo']) ? wp_get_attachment_url($shop['logo']) : '';
                        $selected_shops[] = $shop;
                    }
                }
                $value = $selected_shops;
            } elseif ($field === 'gtc_selected_sub_terms' && $value) {
                $sub_terms = [];
                foreach ((array)$value as $sub_term_id) {
                    $sub_term = get_term($sub_term_id);
                    if ($sub_term && !is_wp_error($sub_term)) {
                        $sub_terms[] = array(
                            'id' => $sub_term->term_id,
                            'name' => $sub_term->name,
                            'slug' => $sub_term->slug,
                            'taxonomy' => $sub_term->taxonomy
                        );
                    }
                }
                $value = $sub_terms;
            }
            $meta[$field] = $value ?: ($field === 'gtc_enable_card' ? '0' : []);
        }
        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
            'meta' => $meta
        ];
    }, $terms);

    return rest_ensure_response($result);
}

function gtc_get_partner_shops($request) {
    $partner_shops = get_option('gtc_partner_shops', []);
    if (empty($partner_shops)) {
        return rest_ensure_response([]);
    }

    foreach ($partner_shops as &$shop) {
        if (!empty($shop['logo'])) {
            $shop['logo'] = wp_get_attachment_url($shop['logo']);
        }
    }
    return rest_ensure_response($partner_shops);
}

// AJAX handler for "Load More" products
add_action('wp_ajax_gtc_load_more_products', 'gtc_ajax_load_more_products');
add_action('wp_ajax_nopriv_gtc_load_more_products', 'gtc_ajax_load_more_products');

function gtc_ajax_load_more_products() {
    check_ajax_referer('gtc_ajax_nonce', 'nonce');
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    $product_type = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : 'popular';
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;

    $products = gtc_get_products($term_id, $taxonomy, $product_type, $offset, $limit);

    wp_send_json_success($products);
}

// AJAX handler for product filter
add_action('wp_ajax_gtc_filter_products', 'gtc_ajax_filter_products');
add_action('wp_ajax_nopriv_gtc_filter_products', 'gtc_ajax_filter_products');

function gtc_ajax_filter_products() {
    check_ajax_referer('gtc_ajax_nonce', 'nonce');
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;

    $products = gtc_get_filtered_products($term_id, $taxonomy, $filters, $offset, $limit);

    if (!empty($products)) {
        wp_send_json_success($products);
    } else {
        wp_send_json_error('Keine Produkte gefunden');
    }
}

// AJAX handler for persona filter
add_action('wp_ajax_gtc_apply_persona_filter', 'gtc_ajax_apply_persona_filter');
add_action('wp_ajax_nopriv_gtc_apply_persona_filter', 'gtc_ajax_apply_persona_filter');

function gtc_ajax_apply_persona_filter() {
    check_ajax_referer('gtc_ajax_nonce', 'nonce');
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : 0;
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;

    $personas = gtc_get_term_personas($term_id);
    $persona = $personas[$persona_id] ?? null;

    if ($persona && isset($persona['filters'])) {
        wp_send_json_success(['filters' => $persona['filters']]);
    } else {
        wp_send_json_error('Persona-Filter nicht gefunden');
    }
}


// AJAX handler for random products
function gtc_get_random_products_ajax() {
    if (!check_ajax_referer('gtc_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Sicherheitsüberprüfung fehlgeschlagen'));
        return;
    }
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
    $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
    $exclude = isset($_POST['exclude']) ? array_map('intval', (array)$_POST['exclude']) : array();

    $random_products = gtc_get_random_products($term_id, $taxonomy, $count, 0, $exclude);

    if (!empty($random_products)) {
        wp_send_json_success($random_products);
    } else {
        wp_send_json_error('Keine Produkte gefunden');
    }
}
add_action('wp_ajax_gtc_get_random_products', 'gtc_get_random_products_ajax');
add_action('wp_ajax_nopriv_gtc_get_random_products', 'gtc_get_random_products_ajax');

// AJAX handler for product feedback
function gtc_record_product_feedback() {
    if (!check_ajax_referer('gtc_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Sicherheitsüberprüfung fehlgeschlagen'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
    $feedback_type = isset($_POST['feedback_type']) ? sanitize_text_field($_POST['feedback_type']) : '';

    if (!in_array($feedback_type, ['like', 'dislike'])) {
        wp_send_json_error(array('message' => 'Ungültiger Feedback-Typ'));
        return;
    }

    $api_url = "https://schindler-ventures.de:3002/api/product-feedback/record";
    $data = array(
        'product_id' => $product_id,
        'product_name' => $product_name,
        'term_id' => $term_id,
        'feedback_type' => $feedback_type
    );

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode($data),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Fehler beim Aufzeichnen des Produktfeedbacks'));
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code === 200) {
            wp_send_json_success(array('message' => 'Produktfeedback erfolgreich aufgezeichnet'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Aufzeichnen des Produktfeedbacks'));
        }
    }
}
add_action('wp_ajax_gtc_record_product_feedback', 'gtc_record_product_feedback');
add_action('wp_ajax_nopriv_gtc_record_product_feedback', 'gtc_record_product_feedback');
