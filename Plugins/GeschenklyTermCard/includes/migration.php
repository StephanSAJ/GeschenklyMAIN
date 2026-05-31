<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

function gtc_migrate_data() {
    // Überprüfe, ob die Migration bereits durchgeführt wurde
    if (get_option('gtc_migration_completed')) {
        return;
    }

    // Hole alle Begriffe aus den Taxonomien 'product_cat' und 'product_tag'
    $terms = get_terms(array(
        'taxonomy' => array('product_cat', 'product_tag'),
        'hide_empty' => false,
    ));

    // Definiere die zuzuordnenden Meta-Schlüssel
    $meta_key_map = array(
        'wcc_enable_card' => 'gtc_enable_card',
        'wcc_image' => 'gtc_image',
        'wcc_info' => 'gtc_info',
        'wcc_selected_subcategories' => 'gtc_selected_sub_terms',
        'wcc_selected_tags' => 'gtc_selected_sub_terms',
        'wcc_custom_subcategory_texts' => 'gtc_custom_sub_term_texts',
        'wcc_custom_tag_texts' => 'gtc_custom_sub_term_texts',
        'wcc_personas' => 'gtc_personas',
        'wcc_custom_description' => 'gtc_custom_description',
        'wcc_custom_header_text' => 'gtc_custom_header_text',
        'wcc_selected_partner_shops' => 'gtc_selected_partner_shops',
    );

    // Durchlaufe alle Begriffe und migriere die Metadaten
    foreach ($terms as $term) {
        foreach ($meta_key_map as $old_key => $new_key) {
            $old_value = get_term_meta($term->term_id, $old_key, true);

            if ($old_value) {
                // Spezieller Fall für 'wcc_selected_tags', um sie mit 'wcc_selected_subcategories' zu vereinigen
                if ($old_key === 'wcc_selected_tags') {
                    $existing_sub_terms = get_term_meta($term->term_id, 'gtc_selected_sub_terms', true);
                    if (!is_array($existing_sub_terms)) {
                        $existing_sub_terms = array();
                    }
                    $old_value = array_merge($existing_sub_terms, (array)$old_value);
                }

                if ($old_key === 'wcc_custom_tag_texts') {
                    $existing_custom_texts = get_term_meta($term->term_id, 'gtc_custom_sub_term_texts', true);
                    if (!is_array($existing_custom_texts)) {
                        $existing_custom_texts = array();
                    }
                    $old_value = array_merge($existing_custom_texts, (array)$old_value);
                }

                update_term_meta($term->term_id, $new_key, $old_value);
            }
        }
    }

    // Migriere die Partner-Shops-Option
    $old_partner_shops = get_option('wcc_partner_shops');
    if ($old_partner_shops) {
        update_option('gtc_partner_shops', $old_partner_shops);
    }

    // Markiere die Migration als abgeschlossen
    update_option('gtc_migration_completed', true);
}
