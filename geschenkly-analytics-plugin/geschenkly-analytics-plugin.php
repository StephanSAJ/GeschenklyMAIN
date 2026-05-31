<?php
/**
 * Plugin Name: Geschenkly Analytics Plugin
 * Description: Erfasst Klick-Daten und sendet sie an eine externe API.
 * Version: 1.1
 * Author: Dein Name
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class MyAnalyticsPlugin {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_update_rating', array($this, 'update_rating_callback'));
        add_action('wp_ajax_nopriv_update_rating', array($this, 'update_rating_callback'));
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_data_attributes'), 10);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('rating_js', plugin_dir_url(__FILE__) . 'js/rating.js', array('jquery'), '1.0', true);
        wp_enqueue_script('rating_new_design_js', plugin_dir_url(__FILE__) . 'js/ratingNewDesign.js', array('jquery'), '1.0', true);

        wp_localize_script('rating_js', 'rjs', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
        wp_localize_script('rating_new_design_js', 'rjs', array(
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    public function update_rating_callback() {
        try {
            if (!empty($_POST['rate']) && !empty($_POST['post_id']) && !empty($_POST['post_title'])) {
                global $wpdb;
                $rate = floatval($_POST['rate']);
                $post_id = intval($_POST['post_id']);
                $post_title = sanitize_text_field($_POST['post_title']);
                $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
                $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : null;
                $category_name = $category_id ? get_term($category_id)->name : null;
                $tag_name = $tag_id ? get_term($tag_id)->name : null;
                // Prepare data to send to external API
                $data_to_send = array(
                    'post_id' => $post_id,
                    'post_title' => $post_title,
                    'rate' => $rate,
                    'category_id' => $category_id,
                    'category_name' => $category_name,
                    'tag_id' => $tag_id,
                    'tag_name' => $tag_name,
                    'timestamp' => current_time('mysql')
                );
                // Send data to external API
                $this->send_rating_data_to_api($data_to_send);
                wp_send_json_success("Rating updated and data sent to API!");
            } else {
                wp_send_json_error("Missing rate, post_id, or post_title");
            }
        } catch (Exception $e) {
            wp_send_json_error('Caught exception: ' . $e->getMessage());
        }
    }

    private function send_rating_data_to_api($data) {
        $api_url = 'https://schindler-ventures.de:3002/api/rating'; // Ersetze dies mit der tatsächlichen API-URL
        $response = wp_remote_post($api_url, array(
            'method'    => 'POST',
            'body'      => json_encode($data),
            'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
        ));
        if (is_wp_error($response)) {
            // Fehlerbehandlung hier
            error_log('Error sending data to API: ' . $response->get_error_message());
        } else {
            // Verarbeite die Antwort, falls erforderlich
            error_log('Data sent to API successfully');
        }
    }

    public function add_data_attributes() {
        global $product;
        $product_id = $product->get_id();
        $product_title = $product->get_name();
        echo '<div
            class="product-info"
            data-product-id="' . esc_attr($product_id) . '"
            data-product-title="' . esc_attr($product_title) . '">
        </div>';
    }
}
new MyAnalyticsPlugin();
?>
