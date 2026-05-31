<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

function gtc_get_term_info($term_id, $taxonomy) {
    $term = get_term($term_id, $taxonomy);
    $custom_info = get_term_meta($term_id, 'gtc_term_info', true);

    $info = array(
        'name' => $term->name,
        'description' => $term->description,
        'image' => get_term_meta($term_id, 'thumbnail_id', true),
        'product_count' => $term->count,
    );

    // Füge die benutzerdefinierten Felder hinzu, wenn sie existieren
    if (is_array($custom_info)) {
        $info = array_merge($info, $custom_info);
    }

    return $info;
}

function gtc_get_sub_terms($term_id, $taxonomy) {
    $sub_terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'parent' => $term_id,
        'hide_empty' => false,
    ));

    $sub_term_data = array();
    foreach ($sub_terms as $sub_term) {
        $sub_term_data[] = array(
            'id' => $sub_term->term_id,
            'name' => $sub_term->name,
            'description' => $sub_term->description,
            'image' => get_term_meta($sub_term->term_id, 'thumbnail_id', true),
            'url' => get_term_link($sub_term),
        );
    }

    return $sub_term_data;
}

function gtc_get_liked_products_info() {
    check_ajax_referer('gtc_ajax_nonce', 'nonce');

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

add_action('wp_ajax_gtc_get_liked_products_info', 'gtc_get_liked_products_info');
add_action('wp_ajax_nopriv_gtc_get_liked_products_info', 'gtc_get_liked_products_info');

function gtc_generate_stars($score) {
    $full_stars = floor($score);
    $half_star = ($score - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    $stars = str_repeat('★', $full_stars);
    $stars .= $half_star ? '½' : '';
    $stars .= str_repeat('☆', $empty_stars);

    return $stars;
}

function gtc_get_popular_products($term_id, $taxonomy, $limit = 8, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products";
    $query_params = array(
        'limit' => $limit,
        'offset' => $offset,
    );

    if ($taxonomy === 'product_cat') {
        $query_params['categoryId'] = $term_id;
    } elseif ($taxonomy === 'product_tag') {
        $query_params['tagId'] = $term_id;
    }

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

    $all_products = array();
    foreach ($data as $period_data) {
        if (isset($period_data['products']) && is_array($period_data['products'])) {
            $all_products = array_merge($all_products, $period_data['products']);
        }
    }

    usort($all_products, function($a, $b) {
        return $b['totalClicks'] - $a['totalClicks'];
    });

    $selected_products = array_slice($all_products, 0, $limit);

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

function gtc_get_most_popular_products($term_id, $taxonomy) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/mostpopular-products";
    $query_params = array();

    if ($taxonomy === 'product_cat') {
        $query_params['categoryId'] = $term_id;
    } elseif ($taxonomy === 'product_tag') {
        $query_params['tagId'] = $term_id;
    }

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

    error_log('Produkte: ' . print_r($products, true));

    return $products;
}

function gtc_get_products($term_id, $taxonomy, $product_type, $offset = 0, $limit = 12) {
    switch ($product_type) {
        case 'popular':
            return gtc_get_popular_products($term_id, $taxonomy, $limit, $offset);
        case 'new':
            return gtc_get_new_products($term_id, $taxonomy, $limit, $offset);
        case 'trending':
            return gtc_get_trending_products($term_id, $taxonomy, $limit, $offset);
        case 'sixMonthsPopular':
            return gtc_get_six_months_popular_products($term_id, $taxonomy, $limit, $offset);
        case 'rising':
            return gtc_get_rising_products($term_id, $taxonomy, $limit, $offset);
        case 'random':
            return gtc_get_random_products($term_id, $taxonomy, $limit, $offset);
        default:
            return array();
    }
}

function gtc_get_random_products($term_id, $taxonomy, $limit = 12, $offset = 0, $exclude = array()) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'orderby' => 'rand',
        'post__not_in' => $exclude,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
    );
    $products = wc_get_products($args);
    $result = array();
    foreach ($products as $p) {
        $result[] = array(
            'id' => $p->get_id(),
            'name' => $p->get_name(),
            'image' => get_the_post_thumbnail_url($p->get_id(), 'medium'),
            'url' => get_permalink($p->get_id()),
            'button_text' => $p->add_to_cart_text(),
            'slug' => $p->get_slug(),
        );
    }
    return $result;
}

function gtc_get_new_products($term_id, $taxonomy, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $term_id,
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

function gtc_get_trending_products($term_id, $taxonomy, $limit = 12, $offset = 0) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $term_id,
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

function gtc_get_six_months_popular_products($term_id, $taxonomy, $limit = 12, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/popular-products-6-months";
    $query_params = array(
        'limit' => $limit,
        'offset' => $offset,
    );

    if ($taxonomy === 'product_cat') {
        $query_params['categoryId'] = $term_id;
    } elseif ($taxonomy === 'product_tag') {
        $query_params['tagId'] = $term_id;
    }

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

function gtc_get_rising_products($term_id, $taxonomy, $limit = 12, $offset = 0) {
    $api_url = "https://schindler-ventures.de:3002/api/analytics/rising-products";
    $query_params = array(
        'limit' => $limit,
        'offset' => $offset,
    );

    if ($taxonomy === 'product_cat') {
        $query_params['categoryId'] = $term_id;
    } elseif ($taxonomy === 'product_tag') {
        $query_params['tagId'] = $term_id;
    }
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

function gtc_get_filtered_products($term_id, $taxonomy, $filters, $offset = 0, $limit = 12) {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $limit,
        'offset' => $offset,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        ),
    );

    if (!empty($filters)) {
        foreach ($filters as $tax => $terms) {
            $args['tax_query'][] = array(
                'taxonomy' => $tax,
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

function gtc_get_term_personas($term_id) {
    $personas = get_term_meta($term_id, 'gtc_personas', true);
    if (!is_array($personas)) {
        $personas = array();
    }
    return $personas;
}

function gtc_render_foldable_card($title, $content, $link) {
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

function gtc_record_term_visit($term_id, $taxonomy) {
    $term = get_term($term_id, $taxonomy);
    if ($term && !is_wp_error($term)) {
        $api_url = "https://schindler-ventures.de:3002/api/term-visit/record";
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => json_encode(array(
                'term_id' => $term_id,
                'term_name' => $term->name,
                'taxonomy' => $taxonomy,
            )),
            'data_format' => 'body',
        ));

        if (is_wp_error($response)) {
            error_log('Fehler beim Aufzeichnen des Begriffsbesuchs: ' . $response->get_error_message());
        }
    }
}

function gtc_get_trending_sub_terms($term_id, $taxonomy, $selected_terms) {
    if (!is_array($selected_terms)) {
        $selected_terms = array();
    }

    $api_url = "https://schindler-ventures.de:3002/api/analytics/trending-sub-terms";

    $response = wp_remote_post($api_url, array(
        'method' => 'POST',
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode(array(
            'term_id' => intval($term_id),
            'taxonomy' => $taxonomy,
            'selected_terms' => array_map('intval', $selected_terms)
        )),
        'data_format' => 'body',
    ));

    if (is_wp_error($response)) {
        error_log('Fehler beim Abrufen von Trend-Unterbegriffen: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data;
}
