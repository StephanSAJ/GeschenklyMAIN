<?php
/*
Plugin Name: Geschenkly Product Card
Description: Zeigt eine Produktkarte basierend auf einem Mockup-Entwurf an und ermöglicht die Aktivierung für alle Produkte.
Version: 2.1
Author: Dein Name
*/

// Add a custom metabox to the product edit page
function geschenkly_add_product_metabox() {
    add_meta_box(
        'geschenkly_product_card',
        'Produktkarte anzeigen',
        'geschenkly_product_metabox_callback',
        'product',
        'side'
    );
}
add_action('add_meta_boxes', 'geschenkly_add_product_metabox');

// Add settings page
function geschenkly_add_admin_menu() {
    add_menu_page(
        'Geschenkly Einstellungen',
        'Geschenkly Produktkarte',
        'manage_options',
        'geschenkly_settings',
        'geschenkly_settings_page',
        'dashicons-gift',
        30
    );
}
add_action('admin_menu', 'geschenkly_add_admin_menu');

// Settings page content
function geschenkly_settings_page() {
    ?>
    <div class="wrap">
        <h1>Geschenkly Einstellungen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('geschenkly_settings');
            do_settings_sections('geschenkly_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function geschenkly_settings_init() {
    register_setting('geschenkly_settings', 'geschenkly_global_product_card');

    add_settings_section(
        'geschenkly_settings_section',
        'Globale Produktkarten-Einstellungen',
        'geschenkly_settings_section_callback',
        'geschenkly_settings'
    );

    add_settings_field(
        'geschenkly_global_product_card',
        'Produktkarte für alle Produkte aktivieren',
        'geschenkly_global_product_card_render',
        'geschenkly_settings',
        'geschenkly_settings_section'
    );
}
add_action('admin_init', 'geschenkly_settings_init');

// Settings section callback
function geschenkly_settings_section_callback() {
    echo 'Hier können Sie die Produktkarte für alle Produkte aktivieren oder deaktivieren.';
}

// Global product card setting render
function geschenkly_global_product_card_render() {
    $value = get_option('geschenkly_global_product_card');
    ?>
    <input type='checkbox' name='geschenkly_global_product_card' <?php checked($value, 1); ?> value='1'>
    <?php
}

// Process bulk activation/deactivation
function geschenkly_process_bulk_product_card() {
    if (isset($_POST['option_page']) && $_POST['option_page'] === 'geschenkly_settings') {
        $new_value = isset($_POST['geschenkly_global_product_card']) ? '1' : '0';
        update_option('geschenkly_global_product_card', $new_value);

        $batch_size = 100;
        $offset = 0;
        $updated_count = 0;
        $total_count = 0;

        while (true) {
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'fields' => 'ids',
            );

            $product_ids = get_posts($args);

            if (empty($product_ids)) {
                break;
            }

            foreach ($product_ids as $product_id) {
                $total_count++;
                $current_value = get_post_meta($product_id, '_geschenkly_product_card', true);
                if ($current_value !== $new_value) {
                    update_post_meta($product_id, '_geschenkly_product_card', $new_value);
                    $updated_count++;
                    // Debugging
                    error_log("Geschenkly: Produkt ID $product_id aktualisiert. Alter Wert: $current_value, Neuer Wert: $new_value");
                }
            }

            $offset += $batch_size;
            wp_cache_flush();
            usleep(100000); // 100ms delay
        }

        // Debugging
        error_log("Geschenkly: Bulk-Aktion abgeschlossen. $updated_count von $total_count Produkten aktualisiert.");

        add_settings_error('geschenkly_messages', 'geschenkly_message', sprintf('%d von %d Produkten aktualisiert. Produktkarten wurden %s.', $updated_count, $total_count, $new_value === '1' ? 'aktiviert' : 'deaktiviert'), 'updated');
    }
}
add_action('admin_init', 'geschenkly_process_bulk_product_card');

// Funktion zum Überprüfen des Status der Produktkarten
function geschenkly_check_product_card_status() {
    $batch_size = 100;
    $offset = 0;
    $active_count = 0;
    $inactive_count = 0;
    $missing_count = 0;

    while (true) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'fields' => 'ids',
        );

        $product_ids = get_posts($args);

        if (empty($product_ids)) {
            break;
        }

        foreach ($product_ids as $product_id) {
            $value = get_post_meta($product_id, '_geschenkly_product_card', true);
            if ($value === '1') {
                $active_count++;
            } elseif ($value === '0') {
                $inactive_count++;
            } else {
                $missing_count++;
            }
        }

        $offset += $batch_size;
    }

    $total_count = $active_count + $inactive_count + $missing_count;
    error_log("Geschenkly Status: Aktiv: $active_count, Inaktiv: $inactive_count, Fehlend: $missing_count, Gesamt: $total_count");

    return array(
        'active' => $active_count,
        'inactive' => $inactive_count,
        'missing' => $missing_count,
        'total' => $total_count
    );
}

// Fügen Sie diese Funktion zur Einstellungsseite hinzu
function geschenkly_add_status_check_button() {
    add_settings_field(
        'geschenkly_status_check',
        'Produktkarten-Status überprüfen',
        'geschenkly_status_check_render',
        'geschenkly_settings',
        'geschenkly_settings_section'
    );
}
add_action('admin_init', 'geschenkly_add_status_check_button');

function geschenkly_status_check_render() {
    ?>
    <button type="button" id="geschenkly-status-check" class="button">Status überprüfen</button>
    <div id="geschenkly-status-result"></div>
    <script>
    jQuery(document).ready(function($) {
        $('#geschenkly-status-check').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'geschenkly_check_status'
                },
                success: function(response) {
                    $('#geschenkly-status-result').html(response);
                }
            });
        });
    });
    </script>
    <?php
}

// AJAX-Handler für die Statusüberprüfung
function geschenkly_ajax_check_status() {
    $status = geschenkly_check_product_card_status();
    $response = "Aktive Produktkarten: {$status['active']}<br>";
    $response .= "Inaktive Produktkarten: {$status['inactive']}<br>";
    $response .= "Fehlende Produktkarten: {$status['missing']}<br>";
    $response .= "Gesamtanzahl der Produkte: {$status['total']}";
    echo $response;
    wp_die();
}
add_action('wp_ajax_geschenkly_check_status', 'geschenkly_ajax_check_status');

// Ensure the product card field exists for all products
function geschenkly_ensure_product_card_field() {
    $batch_size = 100;
    $offset = 0;
    $created_count = 0;

    while (true) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'fields' => 'ids',
        );

        $product_ids = get_posts($args);

        if (empty($product_ids)) {
            break;
        }

        foreach ($product_ids as $product_id) {
            $existing_value = get_post_meta($product_id, '_geschenkly_product_card', true);
            if ($existing_value === '') {
                update_post_meta($product_id, '_geschenkly_product_card', '0');
                $created_count++;
            }
        }

        $offset += $batch_size;
        wp_cache_flush();
        usleep(100000); // 100ms delay
    }

    if ($created_count > 0) {
        error_log(sprintf('Geschenkly: %d Produktkartenfelder erstellt.', $created_count));
    }
}

// Run this function on plugin activation
register_activation_hook(__FILE__, 'geschenkly_ensure_product_card_field');

// You might also want to run this on a scheduled basis to catch any new products
add_action('geschenkly_daily_maintenance', 'geschenkly_ensure_product_card_field');

if (!wp_next_scheduled('geschenkly_daily_maintenance')) {
    wp_schedule_event(time(), 'daily', 'geschenkly_daily_maintenance');
}

// Callback function for the metabox
function geschenkly_product_metabox_callback($post) {
    wp_nonce_field('geschenkly_save_product_card_data', 'geschenkly_product_card_nonce');
    $display_value = get_post_meta($post->ID, '_geschenkly_product_card', true);
    $kurz_gesagt_values = get_post_meta($post->ID, '_geschenkly_kurz_gesagt', true);
    $besonderheit_values = get_post_meta($post->ID, '_geschenkly_besonderheit', true);

    if (!is_array($kurz_gesagt_values)) {
        $kurz_gesagt_values = array($kurz_gesagt_values);
    }
    if (!is_array($besonderheit_values)) {
        $besonderheit_values = array($besonderheit_values);
    }
    ?>
    <p>
        <label for="geschenkly_product_card_checkbox">
            <input type="checkbox" id="geschenkly_product_card_checkbox" name="geschenkly_product_card_checkbox" value="1" <?php checked($display_value, '1'); ?> />
            Produktkarte anzeigen
        </label>
    </p>
    <div id="kurz_gesagt_container">
        <?php foreach ($kurz_gesagt_values as $index => $value): ?>
        <p>
            <label for="geschenkly_kurz_gesagt_<?php echo $index; ?>">Kurz gesagt <?php echo $index + 1; ?>:</label>
            <input type="text" id="geschenkly_kurz_gesagt_<?php echo $index; ?>" name="geschenkly_kurz_gesagt[]" value="<?php echo esc_attr($value); ?>" style="width: 100%;">
        </p>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add_kurz_gesagt">Weiteren "Kurz gesagt" Punkt hinzufügen</button>

    <div id="besonderheit_container">
        <?php foreach ($besonderheit_values as $index => $value): ?>
        <p>
            <label for="geschenkly_besonderheit_<?php echo $index; ?>">Besonderheit <?php echo $index + 1; ?>:</label>
            <input type="text" id="geschenkly_besonderheit_<?php echo $index; ?>" name="geschenkly_besonderheit[]" value="<?php echo esc_attr($value); ?>" style="width: 100%;">
        </p>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add_besonderheit">Weitere Besonderheit hinzufügen</button>

    <script>
    jQuery(document).ready(function($) {
        function addField(container, namePrefix, labelPrefix) {
            var count = container.children('p').length + 1;
            container.append('<p><label for="' + namePrefix + '_' + count + '">' + labelPrefix + ' ' + count + ':</label>' +
                             '<input type="text" id="' + namePrefix + '_' + count + '" name="' + namePrefix + '[]" style="width: 100%;"></p>');
        }

        $('#add_kurz_gesagt').on('click', function() {
            addField($('#kurz_gesagt_container'), 'geschenkly_kurz_gesagt', 'Kurz gesagt');
        });

        $('#add_besonderheit').on('click', function() {
            addField($('#besonderheit_container'), 'geschenkly_besonderheit', 'Besonderheit');
        });
    });
    </script>
    <?php
}

// Save the custom metabox data
function geschenkly_save_product_card_data($post_id) {
    if (!isset($_POST['geschenkly_product_card_nonce']) || !wp_verify_nonce($_POST['geschenkly_product_card_nonce'], 'geschenkly_save_product_card_data')) {
        return $post_id;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (isset($_POST['post_type']) && 'product' == $_POST['post_type']) {
          if (!current_user_can('edit_page', $post_id)) {
              return $post_id;
          }
      } else {
          if (!current_user_can('edit_post', $post_id)) {
              return $post_id;
          }
      }

    $display_value = isset($_POST['geschenkly_product_card_checkbox']) ? '1' : '0';
    $old_value = get_post_meta($post_id, '_geschenkly_product_card', true);

    update_post_meta($post_id, '_geschenkly_product_card', $display_value);

    // Debugging
    error_log("Geschenkly: Produkt ID $post_id aktualisiert. Alter Wert: $old_value, Neuer Wert: $display_value");

    if (isset($_POST['geschenkly_kurz_gesagt']) && is_array($_POST['geschenkly_kurz_gesagt'])) {
        $kurz_gesagt_values = array_map('sanitize_text_field', $_POST['geschenkly_kurz_gesagt']);
        $kurz_gesagt_values = array_filter($kurz_gesagt_values); // Entfernt leere Einträge
        update_post_meta($post_id, '_geschenkly_kurz_gesagt', $kurz_gesagt_values);
    }

    if (isset($_POST['geschenkly_besonderheit']) && is_array($_POST['geschenkly_besonderheit'])) {
        $besonderheit_values = array_map('sanitize_text_field', $_POST['geschenkly_besonderheit']);
        $besonderheit_values = array_filter($besonderheit_values); // Entfernt leere Einträge
        update_post_meta($post_id, '_geschenkly_besonderheit', $besonderheit_values);
    }
}
add_action('save_post', 'geschenkly_save_product_card_data');

// Enqueue styles and scripts
function geschenkly_enqueue_assets() {
    if (is_product()) {
        wp_enqueue_style('geschenkly-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
        //wp_enqueue_script('geschenkly-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery', 'chart-js'), null, true);
        wp_enqueue_script('geschenkly-shop-button', plugin_dir_url(__FILE__) . 'assets/js/shop-button.js', array('jquery'), null, true);
        wp_enqueue_script('geschenkly-product-card-js', plugin_dir_url(__FILE__) . 'assets/js/product-card.js', array('jquery', 'chart-js'), null, true);
        wp_enqueue_script('wishlist-header.js', plugin_dir_url(__FILE__) . 'assets/js/wishlist-header.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'geschenkly_enqueue_assets');

// Function to format price into price range
function geschenkly_get_price_range($price) {
    if ($price <= 10) {
        return 'bis 10 EUR (günstig, Mitbringsel)';
    } elseif ($price <= 35) {
         return 'bis 35 EUR (normal, ~Geschenkpreis)';
    } elseif ($price <= 65) {
        return 'bis 65 EUR (moderat)';
    } else {
        return 'über 65 EUR (besonders)';
    }
}

// Display the product card
function geschenkly_display_product_card() {
    global $post, $product;

    // Check if the product card should be displayed
    $display_product_card = get_post_meta($post->ID, '_geschenkly_product_card', true);
    if ($display_product_card !== '1') {
        return;
    }

    $passende_beziehung = get_the_terms($post->ID, 'pa_passende-beziehung');
    $passende_beziehung_text = $passende_beziehung ? implode(', ', wp_list_pluck($passende_beziehung, 'name')) : '';

    $geschenk_eigenschaften = get_the_terms($post->ID, 'pa_geschenk-eigenschaften');
    $geschenk_eigenschaften_text = $geschenk_eigenschaften ? implode(', ', wp_list_pluck($geschenk_eigenschaften, 'name')) : '';

    $pa_eigenschaften = get_the_terms($post->ID, 'pa_eigenschaften');
    $pa_eigenschaften_tags = $pa_eigenschaften ? wp_list_pluck($pa_eigenschaften, 'name') : [];

    $lebensphase = get_the_terms($post->ID, 'pa_lebensphase');
    $lebensphase_tags = $lebensphase ? wp_list_pluck($lebensphase, 'name') : [];

    $product_categories = get_the_terms($post->ID, 'product_cat');

    $price = $product->get_price();
    $price_range = geschenkly_get_price_range($price);
    $besonderheit = get_post_meta($post->ID, '_geschenkly_besonderheit', true);
    $kurz_gesagt = get_post_meta($post->ID, '_geschenkly_kurz_gesagt', true);

    ob_start();
    ?>
    <div class="gift-card">
        <div class="gift-header">
            <h1 class="gift-title"><?php echo get_the_title($post->ID); ?></h1>
        </div>
        <div class="gift-content">
            <div class="gift-image-description">
                <?php echo $product->get_image('medium', array('class' => 'gift-product-image'));?>
<div class="gift-short-description">
    <?php echo $product->get_short_description(); ?>
    
    <button id="show-full-description" class="show-more-button" aria-expanded="false" aria-controls="full-description">
        <span class="info-icon">ℹ️</span> Mehr Details
    </button>
    
    <?php echo do_shortcode("[shariff]"); ?>

    <div class="product-ratings" style="margin-top: 20px; display: flex; justify-content: center;">
        <?php if (function_exists('kk_star_ratings')) : ?>
            <?php echo kk_star_ratings(); ?>
        <?php endif; ?>
    </div>
</div>
            </div>
            <div id="full-description" class="full-description" hidden>
                <?php echo $product->get_description(); ?>
            </div>

			<div class="shop-button-container">
				<?php
				// Hole den Button-Text des Produkts oder nutze einen Standardwert
				$button_text = $product->get_button_text();
				if ( empty( $button_text ) ) {
					$button_text = __( 'Zum Shop ➞', 'woocommerce' );
				}
				?>

				<a href="<?php echo esc_url( '/redirect.php?product=' . $post->post_name ); ?>"
				   onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Produktseite']);"
				   rel="nofollow noopener"
				   target="_blank"
				   class="single_add_to_cart_button button alt"
				   id="shopButton">
					<?php echo esc_html( $button_text ); ?>
				</a>

				<?php if ( strpos( $button_text, 'Amazon' ) !== false ) : ?>
					<p class="cart-notiz">* als Amazon-Partner verdienen wir an qualifizierten Verkäufen</p>
				<?php elseif ( strpos( $button_text, 'Ebay' ) !== false ) : ?>
					<p class="cart-notiz">* als Ebay-Partner verdienen wir an qualifizierten Verkäufen</p>
				<?php endif; ?>
			</div>

            <div class="gift-summary">
                <?php if ($kurz_gesagt): ?>
                <div class="summary-item summary-item-full">
                    <div class="summary-icon">🗒️ Kurz gesagt</div>
                    <ul class="tag-list kurz-gesagt-list">
                        <?php
                        $kurz_gesagt_values = get_post_meta($post->ID, '_geschenkly_kurz_gesagt', true);
                        if (is_array($kurz_gesagt_values)) {
                            foreach ($kurz_gesagt_values as $value) {
                                echo '<li class="tag">' . esc_html($value) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($besonderheit): ?>
                <div class="summary-item">
                    <div class="summary-icon">🌟 Besonderheit</div>
                    <ul class="tag-list besonderheit-list">
                        <?php
                        $besonderheit_values = get_post_meta($post->ID, '_geschenkly_besonderheit', true);
                        if (is_array($besonderheit_values)) {
                            foreach ($besonderheit_values as $value) {
                                echo '<li class="tag">' . esc_html($value) . '</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="summary-item">
                    <div class="summary-icon">🎁 Ideal für</div>
                    <p><?php echo esc_html($passende_beziehung_text); ?></p>
                    <?php if (!empty($lebensphase_tags)): ?>
                    <div class="attributes-tags">
                        <?php foreach ($lebensphase_tags as $tag): ?>
                        <span class="tag"><?php echo esc_html($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="summary-item">
                    <div class="summary-icon">💖 Eigenschaften</div>
                      <p><?php echo esc_html($geschenk_eigenschaften_text); ?></p>
                    <div class="attributes-tags">
                        <?php foreach ($pa_eigenschaften_tags as $tag): ?>
                        <span class="tag"><?php echo esc_html($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($price): ?>
                <div class="summary-item">
                    <div class="summary-icon">💰 Preisklasse</div>
                    <p><?php echo esc_html($price_range); ?></p>
                </div>
                <?php endif; ?>
                <div class="summary-item abstimmung-box">
                    <div class="summary-icon">🗳️ Wunschliste</div>
                    <p>Füge das Geschenk zu deiner Wunschliste hinzu und teile sie mit Freunden und Bekannten</p>
                    <div class="awp-wishlist-button-container-product">
                        <button class="awp-add-to-wishlist"
                            data-product-id="<?php echo esc_attr($post->ID); ?>"
                            data-product-name="<?php echo esc_attr(get_the_title($post->ID)); ?>"
                            data-product-url="<?php echo esc_url(get_permalink($post->ID)); ?>">
                            <i class="fa fa-heart"></i> hinzufügen
                        </button>
                    </div>
                </div>

                <div class="analytics-item" style="grid-column: span 2;">
                    Auf Geschenkly besonders beliebt für
                    <div id="categoryChartContainer">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="analytics-section">
                <p>Geschenkly Einblicke</p>
                <div class="analytics-grid">
                    <div class="analytics-item">
                        <p>Beliebtheit dieser Woche</p>
                        <div class="analytics-value" id="popularityValue">Wird geladen...</div>
                        <div class="trend" id="popularityTrend">Trend wird berechnet...</div>
                        <button class="trend-button" onclick="showTrendChart()">TREND ANZEIGEN</button>
                    </div>
                    <div class="analytics-item">
                        <p>Kaufwunsch letzte 30 Tage</p>
                        <div class="analytics-value" id="monthlyClicks">Wird geladen...</div>
                        <div class="trend" id="clicksTrend">Trend wird berechnet...</div>
                    </div>
                </div>
                <div id="trendChartContainer" style="display: none;">
                    <canvas id="trendChart"></canvas>
                </div>
                <div class="interest-section">
                    <p>Aktuelles Interesse von anderen Geschenkly Nutzern</p>
                    <div class="interest-visualization">
                        <div class="interest-bar">
                            <div class="interest-level" id="interestLevel"></div>
                        </div>
                        <div class="interest-labels">
                            <span>Ruhiges Interesse</span>
                            <span>steigt gerade an</span>
                            <span>sehr beliebt</span>
                        </div>
                    </div>
                    <p class="interest-description" id="interestDescription"></p>
                </div>
                <?php if (!empty($product_categories) && !is_wp_error($product_categories)): ?>
                <div class="gift-categories">
                    <p>Geschenkkategorien</p>
                    <div class="attributes-tags">
                        <?php foreach ($product_categories as $category): ?>
                        <a href="<?php echo esc_url(get_term_link($category)); ?>" class="tag">
                            <?php echo esc_html($category->name); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="feedback-section">
                <p>Geschenkly besser machen</p>
                <label for="feedback">Für welchen Anlass oder welche Person würdest Du dieses Geschenk schenken?</label>
                <input type="text" id="feedback" class="feedback-input" placeholder="z.B. Geburtstag, Mutter, Kollege...">
                <button class="feedback-submit" onclick="submitFeedback()">Feedback senden</button>
            </div>
        </div>
					<div id="awp-wishlist-app"></div>
    </div>
    <?php
    echo ob_get_clean();

    // Localize the script with new data
    wp_localize_script('geschenkly-product-card-js', 'geschenklyProductData', array(
        'productId' => $post->ID,
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}

function geschenkly_modify_product_page() {
    global $post;

    // Check if $post is set and is a WP_Post object
    if (!isset($post) || !is_a($post, 'WP_Post')) {
        return; // Exit the function if $post is not valid
    }

    $display_product_card = get_post_meta($post->ID, '_geschenkly_product_card', true);
    if ($display_product_card === '1') {
        // Remove standard WooCommerce elements
        remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        remove_action('woocommerce_single_product_summary', 'feedback_overlay_add_to_product_page', 35);
        // Remove tabs
        add_filter('woocommerce_product_tabs', 'geschenkly_remove_product_tabs', 98);
        // Add the Geschenkly product card
        add_action('woocommerce_before_single_product_summary', 'geschenkly_display_product_card', 20);
    }
}
add_action('wp', 'geschenkly_modify_product_page');

function geschenkly_remove_product_tabs($tabs) {
    unset($tabs['description']);
    unset($tabs['additional_information']);
    return $tabs;
}

// Remove duplicate display of product card
remove_action('woocommerce_after_single_product_summary', 'geschenkly_display_product_card', 5);
?>
