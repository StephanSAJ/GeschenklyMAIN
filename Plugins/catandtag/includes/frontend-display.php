<?php
if (!defined('ABSPATH')) exit;

add_action('template_redirect', 'wcc_custom_template_redirect');
function wcc_custom_template_redirect() {
    // Wenn es eine Product-Kategorie oder Tag-Seite ist und wcc_enable_card = 1
    if ((is_product_category() || is_product_tag()) && wcc_should_display_card()) {
        // Wir entfernen das Standard-WooCommerce-Archiv
        add_filter('template_include', 'wcc_custom_template');
    }
}

function wcc_should_display_card() {
    $term = get_queried_object();
    if (!$term || empty($term->term_id)) return false;
    return (get_term_meta($term->term_id, 'wcc_enable_card', true) == '1');
}

function wcc_custom_template($template) {
    ob_start();
    get_header(); ?>
    <div id="primary" class="content-area">
      <main id="main" class="site-main" role="main">
        <?php
        // Anstelle der alten includes (z.B. "tag-card.php") fügen wir nur
        // ein <div> ein, in das unsere React-App gemountet wird.
        if (is_product_category()) {
            $term = get_queried_object();
            $cat_id = $term->term_id;
            echo '<div id="category-card" data-category-id="' . esc_attr($cat_id) . '"></div>';
        } elseif (is_product_tag()) {
            $term = get_queried_object();
            $tag_id = $term->term_id;
            echo '<div id="tag-card" data-tag-id="' . esc_attr($tag_id) . '"></div>';
        }
        ?>
      </main>
    </div>
    <?php
    get_footer();
    $output = ob_get_clean();
    echo $output;
    exit();
}

// Entferne WooCommerce-Archiv-Hooks
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
?>
