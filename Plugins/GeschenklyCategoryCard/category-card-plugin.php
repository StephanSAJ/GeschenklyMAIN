<?php
/*
Plugin Name: WooCommerce Category and Tag Card
Plugin URI: http://example.com/
Description: Fügt eine detaillierte Karte zu WooCommerce-Kategorie- und Tag-Seiten hinzu.
Version: 1.0
Author: Ihr Name
Author URI: http://example.com/
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Definieren Sie Konstanten
define('WCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Einbinden der erforderlichen Dateien
require_once(WCC_PLUGIN_DIR . 'includes/admin-settings.php');
require_once(WCC_PLUGIN_DIR . 'includes/frontend-display.php');
require_once(WCC_PLUGIN_DIR . 'includes/helper-functions.php');

// Aktivierungshaken
register_activation_hook(__FILE__, 'wcc_activate_plugin');

function wcc_activate_plugin() {
    // Führen Sie hier Aktivierungsaufgaben aus, z.B. Erstellen von Datenbanktabellen
}

// Stilvorlagen und Skripte einbinden
add_action('wp_enqueue_scripts', 'wcc_enqueue_scripts');

function wcc_enqueue_scripts() {
    wp_enqueue_style('wcc-styles', WCC_PLUGIN_URL . 'assets/css/category-card.css');
    wp_enqueue_style('wcc-tag-styles', WCC_PLUGIN_URL . 'assets/css/tag-card.css');
    wp_enqueue_script('wcc-scripts', WCC_PLUGIN_URL . 'assets/js/category-card.js', array('jquery'), '1.0', true);
    wp_localize_script('wcc-scripts', 'wcc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wcc_ajax_nonce')
    ));
}

// Admin-Menü hinzufügen
add_action('admin_menu', 'wcc_add_admin_menu');

function wcc_add_admin_menu() {
    add_menu_page('Category and Tag Card Settings', 'Category & Tag Card', 'manage_options', 'wcc-settings', 'wcc_settings_page');
}

// REST-API-Endpunkte registrieren
add_action('rest_api_init', 'wcc_register_rest_endpoints');

function wcc_register_rest_endpoints() {
    // Meta-Felder für Kategorien und Tags in die Standard-WordPress-REST-API einbinden
    $meta_fields = array(
        'wcc_enable_card',
        'wcc_image',
        'wcc_info',
        'wcc_selected_subcategories',
        'wcc_selected_tags',
        'wcc_custom_subcategory_texts',
        'wcc_custom_tag_texts',
        'wcc_personas',
        'wcc_custom_description',
        'wcc_custom_header_text',
        'wcc_selected_partner_shops'
    );

    foreach (['product_cat', 'product_tag'] as $taxonomy) {
        foreach ($meta_fields as $field) {
            register_rest_field($taxonomy, $field, array(
                'get_callback' => function ($data) use ($field) {
                    $term_id = $data['id'];
                    $value = get_term_meta($term_id, $field, true);
                    if ($field === 'wcc_image' && $value) {
                        $value = array(
                            'id' => $value,
                            'url' => wp_get_attachment_url($value)
                        );
                    } elseif ($field === 'wcc_selected_partner_shops' && $value) {
                        $partner_shops = get_option('wcc_partner_shops', []);
                        $selected_shops = [];
                        foreach ((array)$value as $index) {
                            if (isset($partner_shops[$index])) {
                                $shop = $partner_shops[$index];
                                $shop['logo'] = !empty($shop['logo']) ? wp_get_attachment_url($shop['logo']) : '';
                                $selected_shops[] = $shop;
                            }
                        }
                        $value = $selected_shops;
                    } elseif ($field === 'wcc_selected_subcategories' && $value) {
                        $subcategories = [];
                        foreach ((array)$value as $subcat_id) {
                            $subcat = get_term($subcat_id, 'product_cat');
                            if ($subcat && !is_wp_error($subcat)) {
                                $subcategories[] = array(
                                    'id' => $subcat->term_id,
                                    'name' => $subcat->name,
                                    'slug' => $subcat->slug
                                );
                            }
                        }
                        $value = $subcategories;
                    } elseif ($field === 'wcc_selected_tags' && $value) {
                        $tags = [];
                        foreach ((array)$value as $tag_id) {
                            $tag = get_term($tag_id, 'product_tag');
                            if ($tag && !is_wp_error($tag)) {
                                $tags[] = array(
                                    'id' => $tag->term_id,
                                    'name' => $tag->name,
                                    'slug' => $tag->slug
                                );
                            }
                        }
                        $value = $tags;
                    }
                    return $value ?: ($field === 'wcc_enable_card' ? '0' : []);
                },
                'schema' => null,
            ));
        }
    }

    // Endpunkt für Partner-Shops
    register_rest_route('wp/v2', '/options/wcc_partner_shops', array(
        'methods' => 'GET',
        'callback' => 'wcc_get_partner_shops',
        'permission_callback' => '__return_true',
    ));

    // Optional: Benutzerdefinierte Endpunkte für vollständige Daten
    register_rest_route('wcc/v1', '/categories', array(
        'methods' => 'GET',
        'callback' => 'wcc_get_categories',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('wcc/v1', '/tags', array(
        'methods' => 'GET',
        'callback' => 'wcc_get_tags',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('wcc/v1', '/partner-shops', array(
        'methods' => 'GET',
        'callback' => 'wcc_get_partner_shops',
        'permission_callback' => '__return_true',
    ));
}

function wcc_get_categories($request) {
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);

    if (is_wp_error($categories)) {
        return new WP_Error('no_categories', 'Keine Kategorien gefunden', array('status' => 404));
    }

    $result = array_map(function ($cat) {
        $meta = [];
        $fields = ['wcc_enable_card', 'wcc_image', 'wcc_info', 'wcc_selected_subcategories', 'wcc_selected_tags',
                   'wcc_custom_subcategory_texts', 'wcc_custom_tag_texts', 'wcc_personas', 'wcc_custom_description',
                   'wcc_custom_header_text', 'wcc_selected_partner_shops'];
        foreach ($fields as $field) {
            $value = get_term_meta($cat->term_id, $field, true);
            if ($field === 'wcc_image' && $value) {
                $value = array('id' => $value, 'url' => wp_get_attachment_url($value));
            } elseif ($field === 'wcc_selected_partner_shops' && $value) {
                $partner_shops = get_option('wcc_partner_shops', []);
                $selected_shops = [];
                foreach ((array)$value as $index) {
                    if (isset($partner_shops[$index])) {
                        $shop = $partner_shops[$index];
                        $shop['logo'] = !empty($shop['logo']) ? wp_get_attachment_url($shop['logo']) : '';
                        $selected_shops[] = $shop;
                    }
                }
                $value = $selected_shops;
            } elseif ($field === 'wcc_selected_subcategories' && $value) {
                $subcategories = [];
                foreach ((array)$value as $subcat_id) {
                    $subcat = get_term($subcat_id, 'product_cat');
                    if ($subcat && !is_wp_error($subcat)) {
                        $subcategories[] = array(
                            'id' => $subcat->term_id,
                            'name' => $subcat->name,
                            'slug' => $subcat->slug
                        );
                    }
                }
                $value = $subcategories;
            } elseif ($field === 'wcc_selected_tags' && $value) {
                $tags = [];
                foreach ((array)$value as $tag_id) {
                    $tag = get_term($tag_id, 'product_tag');
                    if ($tag && !is_wp_error($tag)) {
                        $tags[] = array(
                            'id' => $tag->term_id,
                            'name' => $tag->name,
                            'slug' => $tag->slug
                        );
                    }
                }
                $value = $tags;
            }
            $meta[$field] = $value ?: ($field === 'wcc_enable_card' ? '0' : []);
        }
        return [
            'id' => $cat->term_id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'fullSlug' => $cat->slug,
            'meta' => $meta
        ];
    }, $categories);

    return rest_ensure_response($result);
}

function wcc_get_tags($request) {
    $tags = get_terms([
        'taxonomy' => 'product_tag',
        'hide_empty' => false,
    ]);

    if (is_wp_error($tags)) {
        return new WP_Error('no_tags', 'Keine Tags gefunden', array('status' => 404));
    }

    $result = array_map(function ($tag) {
        $meta = [];
        $fields = ['wcc_enable_card', 'wcc_image', 'wcc_info', 'wcc_selected_subcategories', 'wcc_selected_tags',
                   'wcc_custom_subcategory_texts', 'wcc_custom_tag_texts', 'wcc_personas', 'wcc_custom_description',
                   'wcc_custom_header_text', 'wcc_selected_partner_shops'];
        foreach ($fields as $field) {
            $value = get_term_meta($tag->term_id, $field, true);
            if ($field === 'wcc_image' && $value) {
                $value = array('id' => $value, 'url' => wp_get_attachment_url($value));
            } elseif ($field === 'wcc_selected_partner_shops' && $value) {
                $partner_shops = get_option('wcc_partner_shops', []);
                $selected_shops = [];
                foreach ((array)$value as $index) {
                    if (isset($partner_shops[$index])) {
                        $shop = $partner_shops[$index];
                        $shop['logo'] = !empty($shop['logo']) ? wp_get_attachment_url($shop['logo']) : '';
                        $selected_shops[] = $shop;
                    }
                }
                $value = $selected_shops;
            } elseif ($field === 'wcc_selected_subcategories' && $value) {
                $subcategories = [];
                foreach ((array)$value as $subcat_id) {
                    $subcat = get_term($subcat_id, 'product_cat');
                    if ($subcat && !is_wp_error($subcat)) {
                        $subcategories[] = array(
                            'id' => $subcat->term_id,
                            'name' => $subcat->name,
                            'slug' => $subcat->slug
                        );
                    }
                }
                $value = $subcategories;
            } elseif ($field === 'wcc_selected_tags' && $value) {
                $tags = [];
                foreach ((array)$value as $tag_id) {
                    $tag_inner = get_term($tag_id, 'product_tag');
                    if ($tag_inner && !is_wp_error($tag_inner)) {
                        $tags[] = array(
                            'id' => $tag_inner->term_id,
                            'name' => $tag_inner->name,
                            'slug' => $tag_inner->slug
                        );
                    }
                }
                $value = $tags;
            }
            $meta[$field] = $value ?: ($field === 'wcc_enable_card' ? '0' : []);
        }
        return [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'fullSlug' => $tag->slug,
            'meta' => $meta
        ];
    }, $tags);

    return rest_ensure_response($result);
}

function wcc_get_partner_shops($request) {
    $partner_shops = get_option('wcc_partner_shops', []);
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

// AJAX-Handler für "Load More" beliebte Produkte
add_action('wp_ajax_wcc_load_more_popular_products', 'wcc_ajax_load_more_popular_products');
add_action('wp_ajax_nopriv_wcc_load_more_popular_products', 'wcc_ajax_load_more_popular_products');

function wcc_ajax_load_more_popular_products() {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 8;
    $existing_ids = isset($_POST['existing_ids']) ? array_map('intval', $_POST['existing_ids']) : array();

    if ($category_id) {
        $all_products = wcc_get_popular_products($category_id, PHP_INT_MAX, 0);
    } elseif ($tag_id) {
        $all_products = wcc_get_tag_popular_products($tag_id, PHP_INT_MAX, 0);
    } else {
        wp_send_json_error('Invalid request');
    }

    $new_products = array_filter($all_products, function($product) use ($existing_ids) {
        return !in_array($product['id'], $existing_ids);
    });

    $selected_products = array_slice($new_products, 0, $limit);

    $html = '';
    foreach ($selected_products as $product) {
        $wc_product = wc_get_product($product['id']);
        $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop', 'woocommerce');
        $html .= '<div class="product-item">';
        $html .= '<div class="product-image-container">';
        $html .= '<a href="' . esc_url(get_permalink($product['id'])) . '">';
        $html .= '<img src="' . esc_url($product['image']) . '" alt="' . esc_attr($product['name']) . '">';
        $html .= '</a>';
        $html .= '<div class="product-reaction-container">';
        $html .= '<button class="reaction-button like-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-heart reaction-icon"></i>';
        $html .= '</button>';
        $html .= '<button class="reaction-button dislike-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-thumbs-down reaction-icon"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h4><a href="' . esc_url(get_permalink($product['id'])) . '">' . esc_html($product['name']) . '</a></h4>';
        $html .= '<a rel="nofollow" target="_blank" onclick="_paq.push([\'trackEvent\', \'Conversion\', \'Klick zum Shop\', \'Teaser\']);" ';
        $html .= 'href="/redirect.php?product=' . esc_attr($wc_product ? $wc_product->get_slug() : '') . '" ';
        $html .= 'class="ansehen-button">';
        $html .= esc_html($button_text);
        $html .= '</a>';
        $html .= '</div>';
    }

    $response = array(
        'html' => $html,
        'count' => count($selected_products),
        'has_more' => count($new_products) > $limit,
    );

    wp_send_json_success($response);
}

// AJAX-Handler für "Load More" Produkte der letzten 6 Monate
add_action('wp_ajax_wcc_load_more_six_months_popular_products', 'wcc_ajax_load_more_six_months_popular_products');
add_action('wp_ajax_nopriv_wcc_load_more_six_months_popular_products', 'wcc_ajax_load_more_six_months_popular_products');

function wcc_ajax_load_more_six_months_popular_products() {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
    if ($category_id) {
        $products = wcc_get_six_months_popular_products($category_id, $limit, $offset);
    } elseif ($tag_id) {
        $products = wcc_get_tag_six_months_popular_products($tag_id, $limit, $offset);
    } else {
        wp_send_json_error('Invalid request');
    }
    $html = '';
    foreach ($products as $product) {
        $wc_product = wc_get_product($product['id']);
        $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop', 'woocommerce');
        $html .= '<div class="product-item">';
        $html .= '<div class="product-image-container">';
        $html .= '<a href="' . esc_url(get_permalink($product['id'])) . '">';
        $html .= '<img src="' . esc_url($product['image']) . '" alt="' . esc_attr($product['name']) . '">';
        $html .= '</a>';
        $html .= '<div class="product-reaction-container">';
        $html .= '<button class="reaction-button like-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-heart reaction-icon"></i>';
        $html .= '</button>';
        $html .= '<button class="reaction-button dislike-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-thumbs-down reaction-icon"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h4><a href="' . esc_url(get_permalink($product['id'])) . '">' . esc_html($product['name']) . '</a></h4>';
        if (isset($product['id'])) {
            $html .= '<div class="feedback-button-container" style="position: relative;">';
            $html .= '<button class="feedback-prompt-icon btn btn-default" data-product-id="' . esc_attr($product['id']) . '" title="Frage die Geschenkly Community">';
            $html .= '<i class="fa fa-comments" aria-hidden="true"></i> Abstimmung';
            $html .= '</button>';
            $html .= '<div class="feedback-tooltip" style="display: none; position: absolute; top: 30px; right: 0; background: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">';
            $html .= 'zur Abstimmungsliste hinzugefügt!';
            $html .= '</div>';
            $html .= '<div class="feedback-tooltip-exists" style="display: none; position: absolute; top: 30px; right: 0; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">';
            $html .= 'Dieses Produkt ist bereits in der Liste!';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '<a rel="nofollow" target="_blank" onclick="_paq.push([\'trackEvent\', \'Conversion\', \'Klick zum Shop\', \'Teaser\']);" ';
        $html .= 'href="/redirect.php?product=' . esc_attr($wc_product ? $wc_product->get_slug() : '') . '" ';
        $html .= 'class="ansehen-button">';
        $html .= esc_html($button_text);
        $html .= '</a>';
        $html .= '</div>';
    }
    wp_send_json_success($html);
}

// AJAX-Handler für "Load More" aufsteigende Produkte
add_action('wp_ajax_wcc_load_more_rising_products', 'wcc_ajax_load_more_rising_products');
add_action('wp_ajax_nopriv_wcc_load_more_rising_products', 'wcc_ajax_load_more_rising_products');

function wcc_ajax_load_more_rising_products() {
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
    if ($category_id) {
        $products = wcc_get_trending_products($category_id, $limit, $offset);
    } elseif ($tag_id) {
        $products = wcc_get_tag_trending_products($tag_id, $limit, $offset);
    } else {
        wp_send_json_error('Invalid request');
    }
    $html = '';
    foreach ($products as $product) {
        $wc_product = wc_get_product($product['id']);
        $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop', 'woocommerce');
        $html .= '<div class="product-item">';
        $html .= '<div class="product-image-container">';
        $html .= '<a href="' . esc_url(get_permalink($product['id'])) . '">';
        $html .= '<img src="' . esc_url($product['image']) . '" alt="' . esc_attr($product['name']) . '">';
        $html .= '</a>';
        $html .= '<div class="product-reaction-container">';
        $html .= '<button class="reaction-button like-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-heart reaction-icon"></i>';
        $html .= '</button>';
        $html .= '<button class="reaction-button dislike-button" data-product-id="' . esc_attr($product['id']) . '" data-category-id="' . esc_attr($category_id) . '">';
        $html .= '<i class="fas fa-thumbs-down reaction-icon"></i>';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h4><a href="' . esc_url(get_permalink($product['id'])) . '">' . esc_html($product['name']) . '</a></h4>';
        if (isset($product['id'])) {
            $html .= '<div class="feedback-button-container" style="position: relative;">';
            $html .= '<button class="feedback-prompt-icon btn btn-default" data-product-id="' . esc_attr($product['id']) . '" title="Frage die Geschenkly Community">';
            $html .= '<i class="fa fa-comments" aria-hidden="true"></i> Abstimmung';
            $html .= '</button>';
            $html .= '<div class="feedback-tooltip" style="display: none; position: absolute; top: 30px; right: 0; background: #28a745; color: #fff; padding: 5px 10px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">';
            $html .= 'zur Abstimmungsliste hinzugefügt!';
            $html .= '</div>';
            $html .= '<div class="feedback-tooltip-exists" style="display: none; position: absolute; top: 30px; right: 0; background: #dc3545; color: #fff; padding: 5px 10px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">';
            $html .= 'Dieses Produkt ist bereits in der Liste!';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '<a rel="nofollow" target="_blank" onclick="_paq.push([\'trackEvent\', \'Conversion\', \'Klick zum Shop\', \'Teaser\']);" ';
        $html .= 'href="/redirect.php?product=' . esc_attr($wc_product ? $wc_product->get_slug() : '') . '" ';
        $html .= 'class="ansehen-button">';
        $html .= esc_html($button_text);
        $html .= '</a>';
        $html .= '</div>';
    }
    wp_send_json_success($html);
}

// AJAX-Handler für "Load More" neue Produkte
add_action('wp_ajax_wcc_load_more_new_products', 'wcc_ajax_load_more_new_products');
add_action('wp_ajax_nopriv_wcc_load_more_new_products', 'wcc_ajax_load_more_new_products');

function wcc_ajax_load_more_new_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($category_id) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        );
    } elseif ($tag_id) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        );
    } else {
        wp_send_json_error('Invalid request');
    }

    $new_products_query = new WP_Query($args);
    $products = array();

    if ($new_products_query->have_posts()) {
        while ($new_products_query->have_posts()) {
            $new_products_query->the_post();
            global $product;
            $product_image = wp_get_attachment_image(
                get_post_thumbnail_id($product->get_id()),
                'woocommerce_thumbnail',
                false,
                array('alt' => $product->get_name())
            );
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => $product_image,
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'date' => get_the_date('d.m.Y'),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success($products);
}

// AJAX-Handler für Produktfilter
add_action('wp_ajax_wcc_filter_products', 'wcc_ajax_filter_products');
add_action('wp_ajax_nopriv_wcc_filter_products', 'wcc_ajax_filter_products');

function wcc_ajax_filter_products() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 12;

    if ($category_id) {
        $products = wcc_get_filtered_products($category_id, $filters, $offset, $limit);
    } elseif ($tag_id) {
        $products = wcc_get_tag_filtered_products($tag_id, $filters, $offset, $limit);
    } else {
        wp_send_json_error('Invalid request');
    }

    if (!empty($products)) {
        wp_send_json_success($products);
    } else {
        wp_send_json_error('Keine Produkte gefunden');
    }
}

// AJAX-Handler für Persona-Filter
add_action('wp_ajax_wcc_apply_persona_filter', 'wcc_ajax_apply_persona_filter');
add_action('wp_ajax_nopriv_wcc_apply_persona_filter', 'wcc_ajax_apply_persona_filter');

function wcc_ajax_apply_persona_filter() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;

    if ($category_id) {
        $personas = wcc_get_category_personas($category_id);
    } elseif ($tag_id) {
        $personas = wcc_get_tag_personas($tag_id);
    } else {
        wp_send_json_error('Invalid request');
    }

    $persona = $personas[$persona_id] ?? null;

    if ($persona && isset($persona['filters'])) {
        wp_send_json_success(['filters' => $persona['filters']]);
    } else {
        wp_send_json_error('Persona-Filter nicht gefunden');
    }
}

// AJAX-Handler für zufällige Produkte (Kategorie)
function wcc_get_random_products() {
    if (!check_ajax_referer('wcc_ajax_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
    $exclude = isset($_POST['exclude']) ? array_map('intval', (array)$_POST['exclude']) : array();

    $args = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
        'posts_per_page' => $count,
        'orderby' => 'rand',
        'post__not_in' => $exclude,
    );

    $query = new WP_Query($args);
    $random_products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            $random_products[] = array(
                'id' => get_the_ID(),
                'name' => get_the_title(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
        wp_send_json_success($random_products);
    } else {
        wp_send_json_error('Keine Produkte gefunden');
    }
}
add_action('wp_ajax_wcc_get_random_products', 'wcc_get_random_products');
add_action('wp_ajax_nopriv_wcc_get_random_products', 'wcc_get_random_products');

// AJAX-Handler für zufällige Produkte (Tag)
function wcc_get_random_products_from_tag() {
    if (!check_ajax_referer('wcc_ajax_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;
    $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
    $exclude = isset($_POST['exclude']) ? array_map('intval', (array)$_POST['exclude']) : array();

    $args = array(
        'post_type' => 'product',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        ),
        'posts_per_page' => $count,
        'orderby' => 'rand',
        'post__not_in' => $exclude,
    );

    $query = new WP_Query($args);
    $random_products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            $random_products[] = array(
                'id' => get_the_ID(),
                'name' => get_the_title(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
        wp_send_json_success($random_products);
    } else {
        wp_send_json_error('Keine Produkte gefunden');
    }
}
add_action('wp_ajax_wcc_get_random_products_from_tag', 'wcc_get_random_products_from_tag');
add_action('wp_ajax_nopriv_wcc_get_random_products_from_tag', 'wcc_get_random_products_from_tag');

// AJAX-Handler für Produkt-Feedback
function wcc_record_product_feedback() {
    error_log('wcc_record_product_feedback function called');
    if (!check_ajax_referer('wcc_ajax_nonce', 'nonce', false)) {
        error_log('Nonce check failed');
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $feedback_type = isset($_POST['feedback_type']) ? sanitize_text_field($_POST['feedback_type']) : '';

    error_log('Received data: ' . print_r($_POST, true));

    if (!in_array($feedback_type, ['like', 'dislike'])) {
        error_log('Invalid feedback type: ' . $feedback_type);
        wp_send_json_error(array('message' => 'Invalid feedback type'));
        return;
    }

    $api_url = "https://schindler-ventures.de:3002/api/product-feedback/record";
    $data = array(
        'product_id' => $product_id,
        'product_name' => $product_name,
        'category_id' => $category_id,
        'feedback_type' => $feedback_type
    );

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode($data),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        error_log('Error recording product feedback: ' . $response->get_error_message());
        wp_send_json_error(array('message' => 'Error recording product feedback'));
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('API Response - Code: ' . $response_code . ', Body: ' . $response_body);

        if ($response_code === 200) {
            wp_send_json_success(array('message' => 'Product feedback recorded successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error recording product feedback'));
        }
    }
}
add_action('wp_ajax_wcc_record_product_feedback', 'wcc_record_product_feedback');
add_action('wp_ajax_nopriv_wcc_record_product_feedback', 'wcc_record_product_feedback');

// AJAX-Handler für Persona-Filter abrufen
function wcc_ajax_get_persona_filters() {
    $persona_id = isset($_POST['persona_id']) ? intval($_POST['persona_id']) : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : 0;

    if ($category_id) {
        $personas = wcc_get_category_personas($category_id);
    } elseif ($tag_id) {
        $personas = wcc_get_tag_personas($tag_id);
    } else {
        wp_send_json_error('Invalid request');
    }

    $persona = $personas[$persona_id] ?? null;

    if ($persona && isset($persona['filters'])) {
        wp_send_json_success($persona['filters']);
    } else {
        wp_send_json_error('Persona-Filter nicht gefunden');
    }
}
add_action('wp_ajax_wcc_get_persona_filters', 'wcc_ajax_get_persona_filters');
add_action('wp_ajax_nopriv_wcc_get_persona_filters', 'wcc_ajax_get_persona_filters');
