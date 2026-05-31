<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

function gtc_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=gtc-settings&tab=general" class="nav-tab <?php echo empty($_GET['tab']) || $_GET['tab'] == 'general' ? 'nav-tab-active' : ''; ?>">Allgemein</a>
            <a href="?page=gtc-settings&tab=partner_shops" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'partner_shops' ? 'nav-tab-active' : ''; ?>">Partner Shops</a>
        </h2>
        <form action="options.php" method="post">
            <?php
            if (isset($_GET['tab']) && $_GET['tab'] == 'partner_shops') {
                settings_fields('gtc_partner_shops_options');
                do_settings_sections('gtc-partner-shops-settings');
            } else {
                settings_fields('gtc_options');
                do_settings_sections('gtc-settings');
            }
            submit_button('Einstellungen speichern');
            ?>
        </form>
    </div>
    <?php
}

// Füge Felder zur Kategorie- und Tag-Bearbeitungsseite hinzu
add_action('product_cat_edit_form_fields', 'gtc_info_fields', 10, 2);
add_action('product_tag_edit_form_fields', 'gtc_info_fields', 10, 2);

function gtc_info_fields($term, $taxonomy) {
    $term_id = $term->term_id;
    $info = get_term_meta($term_id, 'gtc_info', true);
    $selected_sub_terms = get_term_meta($term_id, 'gtc_selected_sub_terms', true);
    $custom_sub_term_texts = get_term_meta($term_id, 'gtc_custom_sub_term_texts', true);
    $enable_card = get_term_meta($term_id, 'gtc_enable_card', true);
    $card_type = ($taxonomy === 'product_cat') ? 'Kategorie' : 'Schlagwort';
    $image_id = get_term_meta($term_id, 'gtc_image', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    $partner_shops = get_option('gtc_partner_shops', []);
    $selected_partner_shops = get_term_meta($term_id, 'gtc_selected_partner_shops', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_enable_card"><?php echo $card_type; ?>-Karte aktivieren</label></th>
        <td>
            <input type="checkbox" name="gtc_enable_card" id="gtc_enable_card" value="1" <?php checked($enable_card, '1'); ?>>
            <label for="gtc_enable_card">Aktivieren Sie diese Option, um die <?php echo $card_type; ?>-Karte anzuzeigen.</label>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_image"><?php echo $card_type; ?>-Bild</label></th>
        <td>
            <div class="gtc-image-preview">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 200px; height: auto;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="gtc_image" id="gtc_image" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button gtc-upload-image">Bild auswählen</button>
            <button type="button" class="button gtc-remove-image" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Bild entfernen</button>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_info_contents">Was ist drin?</label></th>
        <td>
            <?php
            wp_editor(
                $info['contents'] ?? '',
                'gtc_info_contents',
                array(
                    'textarea_name' => 'gtc_info[contents]',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => false
                )
            );
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_info_target_audience">Für wen?</label></th>
        <td>
            <?php
            wp_editor(
                $info['target_audience'] ?? '',
                'gtc_info_target_audience',
                array(
                    'textarea_name' => 'gtc_info[target_audience]',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => false
                )
            );
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_info_age_group">Altersgruppe</label></th>
        <td>
            <?php
            wp_editor(
                $info['age_group'] ?? '',
                'gtc_info_age_group',
                array(
                    'textarea_name' => 'gtc_info[age_group]',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => false
                )
            );
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_info_special_features">Was ist besonders?</label></th>
        <td>
            <?php
            wp_editor(
                $info['special_features'] ?? '',
                'gtc_info_special_features',
                array(
                    'textarea_name' => 'gtc_info[special_features]',
                    'textarea_rows' => 5,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => false
                )
            );
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_info[pinterest_board_url]">Pinterest Board URL</label></th>
        <td>
            <input type="url" name="gtc_info[pinterest_board_url]" id="gtc_info[pinterest_board_url]" value="<?php echo esc_url($info['pinterest_board_url'] ?? ''); ?>">
            <p class="description">Geben Sie die URL zum spezifischen Pinterest-Board für diese Kategorie/dieses Schlagwort ein. Lassen Sie das Feld leer, um das Standard-Profil zu verwenden.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Partner Shops</label></th>
        <td>
            <select name="gtc_selected_partner_shops[]" multiple="multiple" class="gtc-select2" style="width: 100%;">
                <?php foreach ($partner_shops as $index => $shop): ?>
                    <option value="<?php echo $index; ?>" <?php echo in_array($index, (array)$selected_partner_shops) ? 'selected' : ''; ?>>
                        <?php echo esc_html($shop['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_custom_description">Benutzerdefinierte Beschreibung</label></th>
        <td>
            <?php
            $custom_description = get_term_meta($term_id, 'gtc_custom_description', true);
            wp_editor($custom_description, 'gtc_custom_description', array(
                'textarea_name' => 'gtc_custom_description',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'teeny' => true
            ));
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="gtc_custom_header_text">Benutzerdefinierter Headertext</label></th>
        <td>
            <?php
            $custom_header_text = get_term_meta($term_id, 'gtc_custom_header_text', true);
            wp_editor($custom_header_text, 'gtc_custom_header_text', array(
                'textarea_name' => 'gtc_custom_header_text',
                'media_buttons' => true,
                'textarea_rows' => 5,
                'teeny' => true
            ));
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Ausgewählte Unterbegriffe</label></th>
        <td>
            <select name="gtc_selected_sub_terms[]" multiple="multiple" class="gtc-select2" style="width: 100%;">
                <?php
                $terms = get_terms([
                    'taxonomy' => ['product_cat', 'product_tag'],
                    'hide_empty' => false,
                ]);
                foreach ($terms as $sub_term) {
                    $selected = in_array($sub_term->term_id, (array)$selected_sub_terms) ? 'selected' : '';
                    echo '<option value="' . esc_attr($sub_term->term_id) . '" ' . $selected . '>' . esc_html($sub_term->name) . ' (' . $sub_term->taxonomy . ')</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Benutzerdefinierte Texte für Unterbegriffe</label></th>
        <td>
            <?php
            if (!empty($selected_sub_terms)) {
                foreach ($selected_sub_terms as $sub_term_id) {
                    $sub_term = get_term($sub_term_id);
                    if ($sub_term) {
                        echo '<p><strong>' . esc_html($sub_term->name) . ':</strong><br>';
                        echo '<textarea name="gtc_custom_sub_term_texts[' . $sub_term_id . ']" rows="2" cols="50">' .
                             esc_textarea($custom_sub_term_texts[$sub_term_id] ?? '') . '</textarea></p>';
                    }
                }
            }
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Personas</label></th>
        <td>
            <div id="gtc-personas">
                <?php
                $personas = get_term_meta($term_id, 'gtc_personas', true);
                if (!is_array($personas)) {
                    $personas = array();
                }
                foreach ($personas as $index => $persona) {
                    gtc_render_persona_fields($index, $persona);
                }
                ?>
            </div>
            <button type="button" id="add-persona" class="button">Persona hinzufügen</button>
        </td>
    </tr>
    <script>
    jQuery(document).ready(function($) {
        var personaCount = <?php echo count($personas); ?>;
        $('#add-persona').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gtc_add_persona_fields',
                    index: personaCount
                },
                success: function(response) {
                    if (response.success) {
                        $('#gtc-personas').append(response.data);
                        personaCount++;
                    } else {
                        console.error('Fehler beim Hinzufügen der Persona');
                    }
                }
            });
        });

        $(document).on('click', '.delete-persona', function() {
            $(this).closest('.gtc-persona').remove();
            updatePersonaIndices();
        });

        function updatePersonaIndices() {
            $('.gtc-persona').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('h4').text('Persona ' + (index + 1));
                $(this).find('input, textarea, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/gtc_personas\[\d+\]/, 'gtc_personas[' + index + ']'));
                    }
                });
            });
        }

        $('.gtc-select2').select2();

        // Bildupload-Funktionalität
        $('.gtc-upload-image').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var customUploader = wp.media({
                title: 'Bild auswählen',
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            }).on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                $('#gtc_image').val(attachment.id);
                $('.gtc-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                $('.gtc-remove-image').show();
            }).open();
        });

        $('.gtc-remove-image').click(function(e) {
            e.preventDefault();
            $('#gtc_image').val('');
            $('.gtc-image-preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

function gtc_render_persona_fields($index, $persona) {
    ?>
    <div class="gtc-persona" data-index="<?php echo $index; ?>">
        <h4>Persona <?php echo $index + 1; ?></h4>
        <p>
            <label>Name:</label>
            <input type="text" name="gtc_personas[<?php echo $index; ?>][name]" value="<?php echo esc_attr($persona['name'] ?? ''); ?>">
        </p>
        <p>
            <label>Beschreibung:</label>
            <textarea name="gtc_personas[<?php echo $index; ?>][description]"><?php echo esc_textarea($persona['description'] ?? ''); ?></textarea>
        </p>
        <p>
            <label>Bild URL:</label>
            <input type="text" name="gtc_personas[<?php echo $index; ?>][image]" value="<?php echo esc_url($persona['image'] ?? ''); ?>">
        </p>
        <p>
            <label>Filter:</label>
            <?php
            $attributes = wc_get_attribute_taxonomies();
            foreach ($attributes as $attribute) {
                $attribute_name = 'pa_' . $attribute->attribute_name;
                $terms = get_terms(['taxonomy' => $attribute_name, 'hide_empty' => false]);
                if (!empty($terms)) {
                    echo '<p><strong>' . $attribute->attribute_label . ':</strong></p>';
                    foreach ($terms as $term) {
                        $checked = in_array($term->slug, $persona['filters'][$attribute_name] ?? []) ? 'checked' : '';
                        echo '<label><input type="checkbox" name="gtc_personas[' . $index . '][filters][' . $attribute_name . '][]" value="' . $term->slug . '" ' . $checked . '> ' . $term->name . '</label><br>';
                    }
                }
            }
            ?>
        </p>
        <button type="button" class="button delete-persona">Persona löschen</button>
    </div>
    <?php
}

add_action('wp_ajax_gtc_add_persona_fields', 'gtc_ajax_add_persona_fields');
function gtc_ajax_add_persona_fields() {
    $index = isset($_POST['index']) ? intval($_POST['index']) : 0;
    ob_start();
    gtc_render_persona_fields($index, array());
    $html = ob_get_clean();
    wp_send_json_success($html);
}

// Speichere die Info für Kategorien und Tags
add_action('edited_product_cat', 'gtc_save_info', 10, 2);
add_action('edited_product_tag', 'gtc_save_info', 10, 2);
add_action('created_product_cat', 'gtc_save_info', 10, 2);
add_action('created_product_tag', 'gtc_save_info', 10, 2);

function gtc_save_info($term_id, $tt_id) {
    $enable_card = isset($_POST['gtc_enable_card']) ? '1' : '0';
    update_term_meta($term_id, 'gtc_enable_card', $enable_card);

    if (isset($_POST['gtc_image'])) {
        $image_id = intval($_POST['gtc_image']);
        update_term_meta($term_id, 'gtc_image', $image_id);
    }

    if (isset($_POST['gtc_info'])) {
        $info = $_POST['gtc_info'];
        $info = array_map('wp_kses_post', $info);
        $info['pinterest_board_url'] = esc_url_raw($info['pinterest_board_url']);
        update_term_meta($term_id, 'gtc_info', $info);
    }

    if (isset($_POST['gtc_personas'])) {
        $personas = $_POST['gtc_personas'];
        foreach ($personas as &$persona) {
            $persona['name'] = sanitize_text_field($persona['name']);
            $persona['description'] = sanitize_textarea_field($persona['description']);
            $persona['image'] = esc_url_raw($persona['image']);
            if (isset($persona['filters'])) {
                foreach ($persona['filters'] as &$filter_terms) {
                    $filter_terms = array_map('sanitize_title', $filter_terms);
                }
            }
        }
        $personas = array_values($personas);
        update_term_meta($term_id, 'gtc_personas', $personas);
    }

    if (isset($_POST['gtc_selected_sub_terms'])) {
        $selected_sub_terms = array_map('intval', $_POST['gtc_selected_sub_terms']);
        update_term_meta($term_id, 'gtc_selected_sub_terms', $selected_sub_terms);
    }

    if (isset($_POST['gtc_custom_sub_term_texts'])) {
        $custom_sub_term_texts = array_map('sanitize_textarea_field', $_POST['gtc_custom_sub_term_texts']);
        update_term_meta($term_id, 'gtc_custom_sub_term_texts', $custom_sub_term_texts);
    }

    if (isset($_POST['gtc_custom_description'])) {
        $custom_description = wp_kses_post($_POST['gtc_custom_description']);
        update_term_meta($term_id, 'gtc_custom_description', $custom_description);
    }

    if (isset($_POST['gtc_custom_header_text'])) {
        $custom_header_text = wp_kses_post($_POST['gtc_custom_header_text']);
        update_term_meta($term_id, 'gtc_custom_header_text', $custom_header_text);
    }

    if (isset($_POST['gtc_selected_partner_shops'])) {
        $selected_partner_shops = array_map('intval', $_POST['gtc_selected_partner_shops']);
        update_term_meta($term_id, 'gtc_selected_partner_shops', $selected_partner_shops);
    }
}

function gtc_enqueue_wp_editor() {
    wp_enqueue_editor();
}
add_action('admin_enqueue_scripts', 'gtc_enqueue_wp_editor');

// Füge Felder zur Kategorie- und Tag-Erstellungsseite hinzu
add_action('product_cat_add_form_fields', 'gtc_add_info_fields');
add_action('product_tag_add_form_fields', 'gtc_add_info_fields');

function gtc_add_info_fields($taxonomy) {
    $card_type = ($taxonomy === 'product_cat') ? 'Kategorie' : 'Schlagwort';
    $partner_shops = get_option('gtc_partner_shops', []);
    ?>
    <div class="form-field">
        <label for="gtc_enable_card">
            <input type="checkbox" name="gtc_enable_card" id="gtc_enable_card" value="1">
            <?php echo $card_type; ?>-Karte aktivieren
        </label>
        <p class="description">Aktivieren Sie diese Option, um die <?php echo $card_type; ?>-Karte anzuzeigen.</p>
    </div>
    <div class="form-field">
        <label for="gtc_image"><?php echo $card_type; ?>-Bild</label>
        <div class="gtc-image-preview"></div>
        <input type="hidden" name="gtc_image" id="gtc_image" value="">
        <button type="button" class="button gtc-upload-image">Bild auswählen</button>
        <button type="button" class="button gtc-remove-image" style="display:none;">Bild entfernen</button>
    </div>
    <div class="form-field">
        <label for="gtc_info_contents">Was ist drin?</label>
        <?php
        wp_editor(
            '',
            'gtc_info_contents',
            array(
                'textarea_name' => 'gtc_info[contents]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="gtc_info_target_audience">Für wen?</label>
        <?php
        wp_editor(
            '',
            'gtc_info_target_audience',
            array(
                'textarea_name' => 'gtc_info[target_audience]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="gtc_info_age_group">Altersgruppe</label>
        <?php
        wp_editor(
            '',
            'gtc_info_age_group',
            array(
                'textarea_name' => 'gtc_info[age_group]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="gtc_info_special_features">Was ist besonders?</label>
        <?php
        wp_editor(
            '',
            'gtc_info_special_features',
            array(
                'textarea_name' => 'gtc_info[special_features]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="gtc_info[pinterest_board_url]">Pinterest Board URL</label>
        <input type="url" name="gtc_info[pinterest_board_url]" id="gtc_info[pinterest_board_url]">
        <p class="description">Geben Sie die URL zum spezifischen Pinterest-Board für diese Kategorie/dieses Schlagwort ein. Lassen Sie das Feld leer, um das Standard-Profil zu verwenden.</p>
    </div>
    <div class="form-field">
        <label>Partner Shops</label>
        <select name="gtc_selected_partner_shops[]" multiple="multiple" class="gtc-select2" style="width: 100%;">
            <?php foreach ($partner_shops as $index => $shop): ?>
                <option value="<?php echo $index; ?>"><?php echo esc_html($shop['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label for="gtc_custom_description">Benutzerdefinierte Beschreibung</label>
        <?php
        wp_editor('', 'gtc_custom_description', array(
            'textarea_name' => 'gtc_custom_description',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => true
        ));
        ?>
    </div>
    <div class="form-field">
        <label for="gtc_custom_header_text">Benutzerdefinierter Headertext</label>
        <?php
        wp_editor('', 'gtc_custom_header_text', array(
            'textarea_name' => 'gtc_custom_header_text',
            'media_buttons' => true,
            'textarea_rows' => 5,
            'teeny' => true
        ));
        ?>
    </div>
    <div class="form-field">
        <label>Ausgewählte Unterbegriffe</label>
        <select name="gtc_selected_sub_terms[]" multiple="multiple" class="gtc-select2" style="width: 100%;">
            <?php
            $terms = get_terms(['taxonomy' => ['product_cat', 'product_tag'], 'hide_empty' => false]);
            foreach ($terms as $term) {
                echo '<option value="' . esc_attr($term->term_id) . '">' . esc_html($term->name) . ' (' . $term->taxonomy . ')</option>';
            }
            ?>
        </select>
    </div>
    <div class="form-field">
        <label>Personas</label>
        <div id="gtc-personas">
            <!-- Neue Personas werden hier dynamisch hinzugefügt -->
        </div>
        <button type="button" id="add-persona" class="button">Persona hinzufügen</button>
    </div>
    <script>
    jQuery(document).ready(function($) {
        var personaCount = 0;

        $('#add-persona').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gtc_add_persona_fields',
                    index: personaCount
                },
                success: function(response) {
                    if (response.success) {
                        $('#gtc-personas').append(response.data);
                        personaCount++;
                    } else {
                        console.error('Fehler beim Hinzufügen der Persona');
                    }
                }
            });
        });

        $(document).on('click', '.delete-persona', function() {
            $(this).closest('.gtc-persona').remove();
            updatePersonaIndices();
        });

        function updatePersonaIndices() {
            $('.gtc-persona').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('h4').text('Persona ' + (index + 1));
                $(this).find('input, textarea, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/gtc_personas\[\d+\]/, 'gtc_personas[' + index + ']'));
                    }
                });
            });
        }

        $('.gtc-select2').select2();

        // Bildupload-Funktionalität
        $('.gtc-upload-image').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var customUploader = wp.media({
                title: 'Bild auswählen',
                library: {
                    type: 'image'
                },
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            }).on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                $('#gtc_image').val(attachment.id);
                $('.gtc-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                $('.gtc-remove-image').show();
            }).open();
        });

        $('.gtc-remove-image').click(function(e) {
            e.preventDefault();
            $('#gtc_image').val('');
            $('.gtc-image-preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Füge AJAX URL zum Admin-Bereich hinzu
add_action('admin_head', 'gtc_add_ajax_url');
function gtc_add_ajax_url() {
    ?>
    <script type="text/javascript">
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}

add_action('admin_init', 'gtc_partner_shops_settings_init');

function gtc_partner_shops_settings_init() {
    register_setting('gtc_partner_shops_options', 'gtc_partner_shops');

    add_settings_section(
        'gtc_partner_shops_section',
        'Partner Shops Einstellungen',
        'gtc_partner_shops_section_callback',
        'gtc-partner-shops-settings'
    );

    add_settings_field(
        'gtc_partner_shops_list',
        'Partner Shops',
        'gtc_partner_shops_list_callback',
        'gtc-partner-shops-settings',
        'gtc_partner_shops_section'
    );
}

function gtc_partner_shops_section_callback() {
    echo 'Definieren Sie hier Ihre Partner-Shops.';
}

function gtc_partner_shops_list_callback() {
    $partner_shops = get_option('gtc_partner_shops', []);
    ?>
    <div id="partner-shops-container">
        <?php foreach ($partner_shops as $index => $shop): ?>
            <div class="partner-shop">
              <input type="text" name="gtc_partner_shops[<?php echo $index; ?>][name]" value="<?php echo esc_attr($shop['name']); ?>" placeholder="Shop Name">
                <input type="text" name="gtc_partner_shops[<?php echo $index; ?>][url]" value="<?php echo esc_attr($shop['url']); ?>" placeholder="Shop URL">
                <div class="logo-upload">
                    <input type="hidden" name="gtc_partner_shops[<?php echo $index; ?>][logo]" value="<?php echo esc_attr($shop['logo']); ?>" class="logo-id">
                    <img src="<?php echo wp_get_attachment_image_url($shop['logo'], 'thumbnail'); ?>" style="max-width:100px;max-height:100px;<?php echo empty($shop['logo']) ? 'display:none;' : ''; ?>" class="logo-preview">
                    <button type="button" class="upload-logo-button button">Logo hochladen</button>
                    <button type="button" class="remove-logo-button button" <?php echo empty($shop['logo']) ? 'style="display:none;"' : ''; ?>>Logo entfernen</button>
                </div>
                <button type="button" class="remove-shop button">Shop entfernen</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-partner-shop" class="button button-primary">Partner-Shop hinzufügen</button>

    <script>
    jQuery(document).ready(function($) {
        var shopIndex = <?php echo count($partner_shops); ?>;

        $('#add-partner-shop').on('click', function() {
            var newShop = `
                <div class="partner-shop">
                    <input type="text" name="gtc_partner_shops[${shopIndex}][name]" placeholder="Shop Name">
                    <input type="text" name="gtc_partner_shops[${shopIndex}][url]" placeholder="Shop URL">
                    <div class="logo-upload">
                        <input type="hidden" name="gtc_partner_shops[${shopIndex}][logo]" class="logo-id">
                        <img src="" style="max-width:100px;max-height:100px;display:none;" class="logo-preview">
                        <button type="button" class="upload-logo-button button">Logo hochladen</button>
                        <button type="button" class="remove-logo-button button" style="display:none;">Logo entfernen</button>
                    </div>
                    <button type="button" class="remove-shop button">Shop entfernen</button>
                </div>
            `;
            $('#partner-shops-container').append(newShop);
            shopIndex++;
        });

        $(document).on('click', '.remove-shop', function() {
            $(this).closest('.partner-shop').remove();
        });

        $(document).on('click', '.upload-logo-button', function(e) {
            e.preventDefault();
            var button = $(this);
            var logoUpload = button.closest('.logo-upload');
            var logoIdInput = logoUpload.find('.logo-id');
            var logoPreview = logoUpload.find('.logo-preview');
            var removeButton = logoUpload.find('.remove-logo-button');

            var customUploader = wp.media({
                title: 'Logo auswählen',
                button: {
                    text: 'Dieses Bild verwenden'
                },
                multiple: false
            }).on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                logoIdInput.val(attachment.id);
                logoPreview.attr('src', attachment.url).show();
                removeButton.show();
            }).open();
        });

        $(document).on('click', '.remove-logo-button', function() {
            var logoUpload = $(this).closest('.logo-upload');
            logoUpload.find('.logo-id').val('');
            logoUpload.find('.logo-preview').attr('src', '').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Füge Select2 und Media Uploader im Admin-Bereich hinzu
add_action('admin_enqueue_scripts', 'gtc_enqueue_admin_scripts');
function gtc_enqueue_admin_scripts() {
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
    wp_enqueue_media();
}

// AJAX-Handler für das dynamische Laden der Textfelder für Unterbegriffe
add_action('wp_ajax_gtc_load_custom_text_fields', 'gtc_load_custom_text_fields');
function gtc_load_custom_text_fields() {
    if (!isset($_POST['term_id'])) {
        wp_send_json_error('Ungültige Anfrage');
    }

    $term_id = intval($_POST['term_id']);
    $custom_texts = get_term_meta($term_id, 'gtc_custom_sub_term_texts', true);

    $term = get_term($term_id);
    if (!$term || is_wp_error($term)) {
        wp_send_json_error('Begriff nicht gefunden');
    }

    $html = '<p><strong>' . esc_html($term->name) . ':</strong><br>';
    $html .= '<textarea name="gtc_custom_sub_term_texts[' . $term_id . ']" rows="3" cols="50">' .
             esc_textarea($custom_texts[$term_id] ?? '') . '</textarea></p>';

    wp_send_json_success($html);
}

// Speichern der Partner Shops
add_action('admin_init', 'gtc_save_partner_shops');
function gtc_save_partner_shops() {
    if (isset($_POST['gtc_partner_shops'])) {
        $partner_shops = $_POST['gtc_partner_shops'];
        foreach ($partner_shops as &$shop) {
            $shop['name'] = sanitize_text_field($shop['name']);
            $shop['url'] = esc_url_raw($shop['url']);
            $shop['logo'] = absint($shop['logo']);
        }
        update_option('gtc_partner_shops', $partner_shops);
    }
}
