<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

// Hook für die Ausgabekontrolle
add_action('template_redirect', 'wcc_custom_template_redirect');

function wcc_custom_template_redirect() {
    if ((is_product_category() || is_product_tag()) && wcc_should_display_card()) {
        add_filter('template_include', 'wcc_custom_template');
    }
}

function wcc_should_display_card() {
    $term = get_queried_object();
    $term_id = $term->term_id;
    return get_term_meta($term_id, 'wcc_enable_card', true) == '1';
}

function wcc_custom_template($template) {
    ob_start();
    get_header();
    ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php wcc_display_term_card(); ?>
        </main>
    </div>
    <?php
    get_footer();
    $output = ob_get_clean();
    echo $output;
    exit();
}

function wcc_display_term_card() {
    if (is_product_category()) {
        $category = get_queried_object();
        $category_id = $category->term_id;
        $category_name = $category->name;
        $category_description = $category->description;
        $category_info = wcc_get_category_info($category_id);
        $subcategories = wcc_get_subcategories($category_id);
        $popular_products = wcc_get_popular_products($category_id, 8);
        $personas = wcc_get_category_personas($category_id);
        $selected_subcategories = get_term_meta($category_id, 'wcc_selected_subcategories', true);
        $selected_tags = get_term_meta($category_id, 'wcc_selected_tags', true);
        $custom_description = get_term_meta($category_id, 'wcc_custom_description', true);
        include(WCC_PLUGIN_DIR . 'templates/category-card.php');
    } elseif (is_product_tag()) {
        $tag = get_queried_object();
        $tag_id = $tag->term_id;
        $tag_name = $tag->name;
        $tag_description = $tag->description;
        $tag_info = wcc_get_tag_info($tag_id);
        $popular_products = wcc_get_tag_popular_products($tag_id, 8);
        $personas = wcc_get_tag_personas($tag_id);
        $selected_tags = get_term_meta($tag_id, 'wcc_selected_tags', true);
        $custom_description = get_term_meta($tag_id, 'wcc_custom_description', true);
        include(WCC_PLUGIN_DIR . 'templates/tag-card.php');
    }
}

// Entferne alle WooCommerce-Aktionen für Archivseiten
add_action('wp', 'wcc_remove_woocommerce_hooks');

function wcc_remove_woocommerce_hooks() {
    if ((is_product_category() || is_product_tag()) && wcc_should_display_card()) {
        remove_all_actions('woocommerce_before_main_content');
        remove_all_actions('woocommerce_archive_description');
        remove_all_actions('woocommerce_before_shop_loop');
        remove_all_actions('woocommerce_shop_loop');
        remove_all_actions('woocommerce_after_shop_loop');
        remove_all_actions('woocommerce_after_main_content');

    }
}
