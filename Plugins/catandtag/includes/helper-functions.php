<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

/**
 * API-Daten mit Transient-Caching abrufen
 */
function wcc_fetch_api_data_with_caching($url, $transient_key, $expiration = HOUR_IN_SECONDS, $args = [], $is_post = false) {
    $cached_data = get_transient($transient_key);
    if ($cached_data !== false) return $cached_data;

    $default_args = ['headers' => ['Content-Type' => 'application/json; charset=utf-8']];
    $args = wp_parse_args($args, $default_args);

    $response = $is_post ? wp_remote_post($url, $args) : wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        error_log("API-Fehler bei $url: " . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log("Ungültige API-Antwort bei $url: " . substr($body, 0, 200));
        return false;
    }

    set_transient($transient_key, $data, $expiration);
    return $data;
}

/**
 * Kategorie-Informationen abrufen
 */
function wcc_get_category_info($category_id) {
    $category = get_term($category_id, 'product_cat');
    $custom_info = get_term_meta($category_id, 'wcc_category_info', true);
    $info = [
        'name' => $category->name,
        'description' => $category->description,
        'image' => ($img_id = get_term_meta($category_id, 'thumbnail_id', true)) ? wp_get_attachment_url($img_id) : '',
        'product_count' => $category->count,
    ];
    return is_array($custom_info) ? array_merge($info, $custom_info) : $info;
}

/**
 * Tag-Informationen abrufen
 */
function wcc_get_tag_info($tag_id) {
    $tag = get_term($tag_id, 'product_tag');
    $custom_info = get_term_meta($tag_id, 'wcc_tag_info', true);
    $info = [
        'name' => $tag->name,
        'description' => $tag->description,
        'product_count' => $tag->count,
    ];
    return is_array($custom_info) ? array_merge($info, $custom_info) : $info;
}

/**
 * Unterkategorien abrufen
 */
function wcc_get_subcategories($category_id) {
    $subcategories = get_terms(['taxonomy' => 'product_cat', 'parent' => $category_id, 'hide_empty' => false]);
    $data = [];
    foreach ($subcategories as $subcategory) {
        $data[] = [
            'id' => $subcategory->term_id,
            'name' => $subcategory->name,
            'description' => $subcategory->description,
            'image' => ($img_id = get_term_meta($subcategory->term_id, 'thumbnail_id', true)) ? wp_get_attachment_url($img_id) : '',
            'url' => get_term_link($subcategory),
        ];
    }
    return $data;
}

/**
 * Informationen zu geliketen Produkten abrufen
 */
function wcc_get_liked_products_info() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');
    $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array)$_POST['product_ids']) : [];
    if (empty($product_ids)) wp_send_json_error('Keine Produkt-IDs erhalten.');

    $products_info = [];
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $products_info[] = [
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail'),
            ];
        }
    }

    wp_send_json_success($products_info ?: 'Keine Produktinformationen gefunden.');
}
add_action('wp_ajax_wcc_get_liked_products_info', 'wcc_get_liked_products_info');
add_action('wp_ajax_nopriv_wcc_get_liked_products_info', 'wcc_get_liked_products_info');

/**
 * Produkte nach Tag abrufen
 */
function wcc_get_tagged_products($tag_id, $limit = 12, $offset = 0) {
    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id]],
    ]);

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        $products[] = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: '/placeholder-gift.jpg',
            'price' => $product->get_price_html(),
            'url' => get_permalink(),
        ];
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Beliebte Produkte (Kategorie)
 */
function wcc_get_popular_products($category_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_popular_{$category_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products?categoryId=" . urlencode($category_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $all_products = [];
    foreach ($data as $period_data) {
        if (isset($period_data['products']) && is_array($period_data['products'])) {
            $all_products = array_merge($all_products, $period_data['products']);
        }
    }
    usort($all_products, fn($a, $b) => $b['totalClicks'] - $a['totalClicks']);

    $selected_products = array_slice($all_products, $offset, $limit);
    $products = [];
    foreach ($selected_products as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['popularity' => $product_data['totalClicks']];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * Beliebte Produkte (Tag)
 */
function wcc_get_tag_popular_products($tag_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_tag_popular_{$tag_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products?tagId=" . urlencode($tag_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $all_products = [];
    foreach ($data as $period_data) {
        if (isset($period_data['products']) && is_array($period_data['products'])) {
            $all_products = array_merge($all_products, $period_data['products']);
        }
    }
    usort($all_products, fn($a, $b) => $b['totalClicks'] - $a['totalClicks']);

    $selected_products = array_slice($all_products, $offset, $limit);
    $products = [];
    foreach ($selected_products as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['popularity' => $product_data['totalClicks']];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * Neue Produkte (Kategorie)
 */
function wcc_get_new_products($category_id, $limit = 8, $offset = 0) {
    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_id]],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Neue Produkte (Tag)
 */
function wcc_get_tag_new_products($tag_id, $limit = 8, $offset = 0) {
    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id]],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Trending Produkte (Kategorie)
 */
function wcc_get_trending_products($category_id, $limit = 8, $offset = 0) {
    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_id]],
        'meta_key' => '_wc_average_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ]);

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Trending Produkte (Tag)
 */
function wcc_get_tag_trending_products($tag_id, $limit = 8, $offset = 0) {
    $query = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id]],
        'meta_key' => '_wc_average_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ]);

    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * 6-Monats-Popular Produkte (Kategorie)
 */
function wcc_get_six_months_popular_products($category_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_6months_popular_{$category_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products-6-months?categoryId=" . urlencode($category_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $products = [];
    foreach (array_slice($data, $offset, $limit) as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['popularity' => $product_data['totalClicks'] / 100];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * 6-Monats-Popular Produkte (Tag)
 */
function wcc_get_tag_six_months_popular_products($tag_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_tag_6months_popular_{$tag_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products-6-months?tagId=" . urlencode($tag_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $products = [];
    foreach (array_slice($data, $offset, $limit) as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['popularity' => $product_data['totalClicks'] / 100];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * Aufsteiger Produkte (Kategorie)
 */
function wcc_get_rising_products($category_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_rising_{$category_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/rising-products?categoryId=" . urlencode($category_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $products = [];
    foreach (array_slice($data, $offset, $limit) as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['growth_percentage' => $product_data['growth_percentage']];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * Aufsteiger Produkte (Tag)
 */
function wcc_get_tag_rising_products($tag_id, $limit = 8, $offset = 0) {
    $transient_key = "wcc_tag_rising_{$tag_id}_{$limit}_{$offset}";
    $cached = get_transient($transient_key);
    if ($cached !== false) return $cached;

    $api_url = "https://schindler-ventures.de:3002/api/analytics/rising-products?tagId=" . urlencode($tag_id);
    $data = wcc_fetch_api_data_with_caching($api_url, $transient_key);
    if (!$data) return [];

    $products = [];
    foreach (array_slice($data, $offset, $limit) as $product_data) {
        $product = wc_get_product($product_data['product_id']);
        if ($product) {
            $products[] = format_product_data($product) + ['growth_percentage' => $product_data['growth_percentage']];
        }
    }
    set_transient($transient_key, $products, HOUR_IN_SECONDS);
    return $products;
}

/**
 * Gefilterte Produkte (Kategorie)
 */
function wcc_get_filtered_products($category_id, $filters, $offset = 0, $limit = 8) {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_id]],
    ];

    if (!empty($filters)) {
        foreach ($filters as $taxonomy => $terms) {
            $args['tax_query'][] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $terms, 'operator' => 'IN'];
        }
    }

    $query = new WP_Query($args);
    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Gefilterte Produkte (Tag)
 */
function wcc_get_tag_filtered_products($tag_id, $filters, $offset = 0, $limit = 8) {
    $args = [
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => [['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id]],
    ];

    if (!empty($filters)) {
        foreach ($filters as $taxonomy => $terms) {
            $args['tax_query'][] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $terms, 'operator' => 'IN'];
        }
    }

    $query = new WP_Query($args);
    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $products[] = format_product_data(wc_get_product(get_the_ID()));
    }
    wp_reset_postdata();
    return $products;
}

/**
 * Produkte nach Typ abrufen (Kategorie)
 */
function wcc_get_category_products($category_id, $product_type, $offset = 0, $limit = 8) {
    $methods = [
        'popular' => 'wcc_get_popular_products',
        'new' => 'wcc_get_new_products',
        'trending' => 'wcc_get_trending_products',
        'sixMonthsPopular' => 'wcc_get_six_months_popular_products',
        'rising' => 'wcc_get_rising_products',
    ];
    return isset($methods[$product_type]) ? call_user_func($methods[$product_type], $category_id, $limit, $offset) : [];
}

/**
 * Produkte nach Typ abrufen (Tag)
 */
function wcc_get_tag_products($tag_id, $product_type, $offset = 0, $limit = 8) {
    $methods = [
        'popular' => 'wcc_get_tag_popular_products',
        'new' => 'wcc_get_tag_new_products',
        'trending' => 'wcc_get_tag_trending_products',
        'sixMonthsPopular' => 'wcc_get_tag_six_months_popular_products',
        'rising' => 'wcc_get_tag_rising_products',
    ];
    return isset($methods[$product_type]) ? call_user_func($methods[$product_type], $tag_id, $limit, $offset) : [];
}

/**
 * Personas abrufen
 */
function wcc_get_category_personas($category_id) {
    $personas = get_term_meta($category_id, 'wcc_personas', true);
    return is_array($personas) ? $personas : [];
}

function wcc_get_tag_personas($tag_id) {
    $personas = get_term_meta($tag_id, 'wcc_personas', true);
    return is_array($personas) ? $personas : [];
}

/**
 * Kategorie- oder Tag-Besuch erfassen
 */
function wcc_record_category_visit($category_id) {
    $category = get_term($category_id, 'product_cat');
    if ($category && !is_wp_error($category)) {
        $api_url = "https://schindler-ventures.de:3002/api/category-visit/record";
        $response = wp_remote_post($api_url, [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['category_id' => $category_id, 'category_name' => $category->name]),
        ]);
        if (is_wp_error($response)) error_log('Category visit error: ' . $response->get_error_message());
    }
}

function wcc_record_tag_visit($tag_id) {
    $tag = get_term($tag_id, 'product_tag');
    if ($tag && !is_wp_error($tag)) {
        $api_url = "https://schindler-ventures.de:3002/api/tag-visit/record";
        $response = wp_remote_post($api_url, [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode(['tag_id' => $tag_id, 'tag_name' => $tag->name]),
        ]);
        if (is_wp_error($response)) error_log('Tag visit error: ' . $response->get_error_message());
    }
}

/**
 * Trending Unterkategorien oder Tags
 */
function wcc_get_trending_subcategories($category_id, $selected_subcategories) {
    $transient_key = 'wcc_trending_subcategories_' . $category_id . '_' . md5(serialize($selected_subcategories));
    $api_url = "https://schindler-ventures.de:3002/api/analytics/trending-subcategories";
    $args = [
        'method' => 'POST',
        'body' => json_encode([
            'category_id' => intval($category_id),
            'selected_subcategories' => array_map('intval', (array)$selected_subcategories)
        ]),
    ];
    return wcc_fetch_api_data_with_caching($api_url, $transient_key, HOUR_IN_SECONDS, $args, true) ?: [];
}

function wcc_get_trending_tags($tag_id, $selected_tags) {
    $transient_key = 'wcc_trending_tags_' . $tag_id . '_' . md5(serialize($selected_tags));
    $api_url = "https://schindler-ventures.de:3002/api/analytics/trending-tags";
    $args = [
        'method' => 'POST',
        'body' => json_encode([
            'tag_id' => intval($tag_id),
            'selected_tags' => array_map('intval', (array)$selected_tags)
        ]),
    ];
    return wcc_fetch_api_data_with_caching($api_url, $transient_key, HOUR_IN_SECONDS, $args, true) ?: [];
}
?>
