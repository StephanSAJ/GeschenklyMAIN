<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

function wcc_get_category_info($category_id) {
    $category = get_term($category_id, 'product_cat');
    $custom_info = get_term_meta($category_id, 'wcc_category_info', true);

    $info = array(
        'name' => $category->name,
        'description' => $category->description,
        'image' => get_term_meta($category_id, 'thumbnail_id', true),
        'product_count' => $category->count,
    );

    // Füge die benutzerdefinierten Felder hinzu, wenn sie existieren
    if (is_array($custom_info)) {
        $info = array_merge($info, $custom_info);
    }

    return $info;
}

function wcc_get_tag_info($tag_id) {
    $tag = get_term($tag_id, 'product_tag');
    $custom_info = get_term_meta($tag_id, 'wcc_tag_info', true);

    $info = array(
        'name' => $tag->name,
        'description' => $tag->description,
        'product_count' => $tag->count,
    );

    // Füge die benutzerdefinierten Felder hinzu, wenn sie existieren
    if (is_array($custom_info)) {
        $info = array_merge($info, $custom_info);
    }

    return $info;
}

function wcc_get_subcategories($category_id) {
    $subcategories = get_terms(array(
        'taxonomy' => 'product_cat',
        'parent' => $category_id,
        'hide_empty' => false,
    ));

    $subcategory_data = array();
    foreach ($subcategories as $subcategory) {
        $subcategory_data[] = array(
            'id' => $subcategory->term_id,
            'name' => $subcategory->name,
            'description' => $subcategory->description,
            'image' => get_term_meta($subcategory->term_id, 'thumbnail_id', true),
            'url' => get_term_link($subcategory),
        );
    }

    return $subcategory_data;
}

function wcc_get_liked_products_info() {
    check_ajax_referer('wcc_ajax_nonce', 'nonce');

    $product_ids = isset($_POST['product_ids']) ? (array)$_POST['product_ids'] : array();
    $products_info = array();
    error_log('Received product_ids: ' . print_r($product_ids, true));  // Debug log

    if (empty($product_ids)) {
        wp_send_json_error('Keine Produkt-IDs erhalten.');
        return;
    }

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
            if (!$image_url) {
                $image_url = wc_placeholder_img_src('thumbnail');
            }
            $products_info[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => $image_url,
            );
        } else {
            error_log("Produkt mit ID $product_id nicht gefunden.");
        }
    }

    if (empty($products_info)) {
        wp_send_json_error('Keine Produktinformationen gefunden.');
    } else {
        error_log('Sending product info: ' . print_r($products_info, true));  // Debug log
        wp_send_json_success($products_info);
    }
}

add_action('wp_ajax_wcc_get_liked_products_info', 'wcc_get_liked_products_info');
add_action('wp_ajax_nopriv_wcc_get_liked_products_info', 'wcc_get_liked_products_info');
function wcc_get_tagged_products($tag_id, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        ),
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_generate_stars($score) {
    $full_stars = floor($score);
    $half_star = ($score - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    $stars = str_repeat('★', $full_stars);
    $stars .= $half_star ? '½' : '';
    $stars .= str_repeat('☆', $empty_stars);

    return $stars;
}

function wcc_get_popular_products($category_id, $limit = 8, $offset = 8) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products?categoryId=" . urlencode($category_id);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    // Combine and sort products from all returned periods
    $all_products = array();
    foreach ($data as $period_data) {
        if (isset($period_data['products']) && is_array($period_data['products'])) {
            $all_products = array_merge($all_products, $period_data['products']);
        }
    }

    // Sort products by totalClicks in descending order
    usort($all_products, function($a, $b) {
        return $b['totalClicks'] - $a['totalClicks'];
    });

    // Apply offset and limit
    $selected_products = array_slice($all_products, $offset, $limit);

    $products = array();
    foreach ($selected_products as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'popularity' => $product_data['totalClicks']
            );
        }
    }
    return $products;
}

function wcc_get_mostpopular_products($category_id) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/mostpopular-products";
    $query_params = array(
        'categoryId' => $category_id
    );
    $api_url = add_query_arg($query_params, $api_url);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }
    $all_products = $data;
    usort($all_products, function($a, $b) {
        return $b['totalClicks'] - $a['totalClicks'];
    });
    $products = array();
    foreach ($all_products as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'popularity' => $product_data['totalClicks'],
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
    }

    // Ausgabe der Produkte in der Konsole
    error_log('Products: ' . print_r($products, true));

    return $products;
}


function wcc_get_mostpopular_products_for_tag_pages($tag_id) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/mostpopular-products";
    $query_params = array(
        'tagId' => $tag_id
    );
    $api_url = add_query_arg($query_params, $api_url);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }
    $all_products = $data;
    usort($all_products, function($a, $b) {
        return $b['totalClicks'] - $a['totalClicks'];
    });
    $products = array();
    foreach ($all_products as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'popularity' => $product_data['totalClicks'],
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
    }

    // Ausgabe der Produkte in der Konsole
    error_log('Products: ' . print_r($products, true));

    return $products;
}

function wcc_get_tag_popular_products($tag_id, $limit = 12, $offset = 12) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products?tagId=" . urlencode($tag_id);
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    // Combine and sort products from all returned periods
    $all_products = array();
    foreach ($data as $period_data) {
        if (isset($period_data['products']) && is_array($period_data['products'])) {
            $all_products = array_merge($all_products, $period_data['products']);
        }
    }

    // Sort products by totalClicks in descending order
    usort($all_products, function($a, $b) {
        return $b['totalClicks'] - $a['totalClicks'];
    });

    // Apply offset and limit
    $selected_products = array_slice($all_products, $offset, $limit);

    $products = array();
    foreach ($selected_products as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'popularity' => $product_data['totalClicks'],
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
    }
    return $products;
}


function wcc_get_new_products($category_id, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'date' => get_the_date('d.m.Y'),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_get_tag_new_products($tag_id, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        ),
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'date' => get_the_date('d.m.Y'),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_get_trending_products($category_id, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
        'meta_key' => '_wc_average_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_get_tag_trending_products($tag_id, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        ),
        'meta_key' => '_wc_average_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}




function wcc_get_six_months_popular_products($category_id, $limit = 12, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products-6-months?categoryId=" . urlencode($category_id) . "&limit=" . urlencode($limit) . "&offset=" . urlencode($offset);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    $products = array();
    foreach ($data as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
                'popularity' => $product_data['totalClicks'] / 100 // Annahme: totalClicks wird als Prozentsatz behandelt
            );
        }
    }
    return $products;
}

function wcc_get_tag_six_months_popular_products($tag_id, $limit = 12, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products-6-months?tagId=" . urlencode($tag_id) . "&limit=" . urlencode($limit) . "&offset=" . urlencode($offset);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    $products = array();
    foreach ($data as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
                'popularity' => $product_data['totalClicks'] / 100 // Annahme: totalClicks wird als Prozentsatz behandelt
            );
        }
    }
    return $products;
}

function wcc_get_rising_products($category_id, $limit = 12, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/rising-products?categoryId=" . urlencode($category_id) . "&limit=" . urlencode($limit) . "&offset=" . urlencode($offset);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    $products = array();
    foreach ($data as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
                'growth_percentage' => $product_data['growth_percentage']
            );
        }
    }
    return $products;
}

function wcc_get_tag_rising_products($tag_id, $limit = 4, $offset = 4) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/rising-products?tagId=" . urlencode($tag_id) . "&limit=" . urlencode($limit) . "&offset=" . urlencode($offset);
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data) || empty($data)) {
        return array();
    }

    $products = array();
    foreach ($data as $product_data) {
        $product_id = $product_data['product_id'];
        $product = wc_get_product($product_id);
        if ($product) {
            $products[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => $product->get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
                'growth_percentage' => $product_data['growth_percentage']
            );
        }
    }
    return $products;
}

function wcc_get_filtered_products($category_id, $filters, $offset = 0, $limit = 12) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $category_id,
            ),
        ),
    );

    if (!empty($filters)) {
        foreach ($filters as $taxonomy => $terms) {
            $args['tax_query'][] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $terms,
                'operator' => 'IN',
            );
        }
    }

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_get_tag_filtered_products($tag_id, $filters, $offset = 0, $limit = 12) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tag_id,
            ),
        ),
    );

    if (!empty($filters)) {
        foreach ($filters as $taxonomy => $terms) {
            $args['tax_query'][] = array(
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $terms,
                'operator' => 'IN',
            );
        }
    }

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'price' => $product->get_price_html(),
                'url' => get_permalink(),
                'button_text' => $product->add_to_cart_text(),
                'slug' => $product->get_slug(),
            );
        }
        wp_reset_postdata();
    }

    return $products;
}

function wcc_get_category_products($category_id, $product_type, $offset = 0, $limit = 8) {
    switch ($product_type) {
        case 'popular':
            return wcc_get_popular_products($category_id, $limit, $offset);
        case 'new':
            return wcc_get_new_products($category_id, $limit, $offset);
        case 'trending':
            return wcc_get_trending_products($category_id, $limit, $offset);
        case 'sixMonthsPopular':
            return wcc_get_six_months_popular_products($category_id, $limit, $offset);
        case 'rising':
            return wcc_get_rising_products($category_id, $limit, $offset);
        default:
            return array();
    }
}

function wcc_get_tag_products($tag_id, $product_type, $offset = 0, $limit = 12) {
    switch ($product_type) {
        case 'popular':
            return wcc_get_tag_popular_products($tag_id, $limit, $offset);
        case 'new':
            return wcc_get_tag_new_products($tag_id, $limit, $offset);
        case 'trending':
            return wcc_get_tag_trending_products($tag_id, $limit, $offset);
        case 'sixMonthsPopular':
            return wcc_get_tag_six_months_popular_products($tag_id, $limit, $offset);
        case 'rising':
            return wcc_get_tag_rising_products($tag_id, $limit, $offset);
        default:
            return array();
    }
}

function wcc_get_category_personas($category_id) {
    $personas = get_term_meta($category_id, 'wcc_personas', true);
    if (!is_array($personas)) {
        $personas = array();
    }
    return $personas;
}

function wcc_get_tag_personas($tag_id) {
    $personas = get_term_meta($tag_id, 'wcc_personas', true);
    if (!is_array($personas)) {
        $personas = array();
    }
    return $personas;
}


function wcc_render_foldable_card($title, $content, $link) {
    ob_start();
    ?>
    <div class="foldable-card">
        <div class="foldable-card-header">
            <h3><?php echo esc_html($title); ?></h3>
        </div>
        <div class="foldable-card-content">
            <p><?php echo esc_html($content); ?></p>
            <a href="<?php echo esc_url($link); ?>" class="button">Mehr anzeigen</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function wcc_record_category_visit($category_id) {
    $category = get_term($category_id, 'product_cat');
    if ($category && !is_wp_error($category)) {
        $api_url = "https://schindler-ventures.de:3002/api/category-visit/record";
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode(array(
                'category_id' => $category_id,
                'category_name' => $category->name
            )),
            'data_format' => 'body',
        ));

        if (is_wp_error($response)) {
            error_log('Error recording category visit: ' . $response->get_error_message());
        }
    }
}

function wcc_record_tag_visit($tag_id) {
    $tag = get_term($tag_id, 'product_tag');
    if ($tag && !is_wp_error($tag)) {
        $api_url = "https://schindler-ventures.de:3002/api/tag-visit/record";
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode(array(
                'tag_id' => $tag_id,
                'tag_name' => $tag->name
            )),
            'data_format' => 'body',
        ));

        if (is_wp_error($response)) {
            error_log('Error recording tag visit: ' . $response->get_error_message());
        }
    }
}



function wcc_get_trending_subcategories($category_id, $selected_subcategories) {
    // Stellen Sie sicher, dass $selected_subcategories ein Array ist
    if (!is_array($selected_subcategories)) {
        $selected_subcategories = array();
    }

    $api_url = "https://schindler-ventures.de:3002/api/analytics/trending-subcategories";

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode(array(
            'category_id' => intval($category_id),
            'selected_subcategories' => array_map('intval', $selected_subcategories)
        )),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        error_log('Error getting trending subcategories: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Hier können Sie die Antwort der API verarbeiten
    // und die Daten zurückgeben oder weiterverarbeiten

    return $data;
}

function wcc_get_trending_tags($tag_id, $selected_tags) {
    // Stellen Sie sicher, dass $selected_tags ein Array ist
    if (!is_array($selected_tags)) {
        $selected_tags = array();
    }

    $api_url = "https://schindler-ventures.de:3002/api/analytics/trending-tags";

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode(array(
            'tag_id' => intval($tag_id),
            'selected_tags' => array_map('intval', $selected_tags)
        )),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        error_log('Error getting trending tags: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Hier können Sie die Antwort der API verarbeiten
    // und die Daten zurückgeben oder weiterverarbeiten

    return $data;
}





// Fügen Sie hier bei Bedarf weitere Hilfsfunktionen hinzu
?>
