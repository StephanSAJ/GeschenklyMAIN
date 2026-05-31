<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

// Hook für die Ausgabekontrolle
add_action('template_redirect', 'gtc_custom_template_redirect');

function gtc_custom_template_redirect() {
    if ((is_product_category() || is_product_tag()) && gtc_should_display_card()) {
        add_filter('template_include', 'gtc_custom_template');
    }
}

function gtc_should_display_card() {
    $term = get_queried_object();
    $term_id = $term->term_id;
    return get_term_meta($term_id, 'gtc_enable_card', true) == '1';
}

function gtc_custom_template($template) {
    ob_start();
    get_header();
    ?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <?php gtc_display_term_card(); ?>
        </main>
    </div>
    <?php
    get_footer();
    $output = ob_get_clean();
    echo $output;
    exit();
}

function gtc_display_term_card() {
    $term = get_queried_object();
    $term_id = $term->term_id;
    $taxonomy = $term->taxonomy;

    $term_name = $term->name;
    $term_description = $term->description;
    $term_info = gtc_get_term_info($term_id, $taxonomy);
    $popular_products = gtc_get_popular_products($term_id, $taxonomy, 8);
    $personas = gtc_get_term_personas($term_id);
    $selected_sub_terms = get_term_meta($term_id, 'gtc_selected_sub_terms', true);
    $custom_description = get_term_meta($term_id, 'gtc_custom_description', true);

    include(plugin_dir_path(__DIR__) . 'templates/term-card.php');
}

// Entferne alle WooCommerce-Aktionen für Archivseiten
add_action('wp', 'gtc_remove_woocommerce_hooks');

function gtc_remove_woocommerce_hooks() {
    if ((is_product_category() || is_product_tag()) && gtc_should_display_card()) {
        remove_all_actions('woocommerce_before_main_content');
        remove_all_actions('woocommerce_archive_description');
        remove_all_actions('woocommerce_before_shop_loop');
        remove_all_actions('woocommerce_shop_loop');
        remove_all_actions('woocommerce_after_shop_loop');
        remove_all_actions('woocommerce_after_main_content');
    }
}
