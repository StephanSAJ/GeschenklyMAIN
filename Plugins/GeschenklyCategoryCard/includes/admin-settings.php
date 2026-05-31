<?php
// Verhindere direkten Zugriff auf diese Datei
if (!defined('ABSPATH')) exit;

function wcc_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=wcc-settings&tab=general" class="nav-tab <?php echo empty($_GET['tab']) || $_GET['tab'] == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?page=wcc-settings&tab=partner_shops" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] == 'partner_shops' ? 'nav-tab-active' : ''; ?>">Partner Shops</a>
        </h2>
        <form action="options.php" method="post">
            <?php
            if (isset($_GET['tab']) && $_GET['tab'] == 'partner_shops') {
                settings_fields('wcc_partner_shops_options');
                do_settings_sections('wcc-partner-shops-settings');
            } else {
                settings_fields('wcc_options');
                do_settings_sections('wcc-settings');
            }
            submit_button('Einstellungen speichern');
            ?>
        </form>
    </div>
    <?php
}

// Füge Felder zur Kategorie- und Tag-Bearbeitungsseite hinzu
add_action('product_cat_edit_form_fields', 'wcc_info_fields', 10, 2);
add_action('product_tag_edit_form_fields', 'wcc_info_fields', 10, 2);

function wcc_info_fields($term, $taxonomy) {
    $term_id = $term->term_id;
    $info = get_term_meta($term_id, 'wcc_info', true);
    $selected_subcategories = get_term_meta($term_id, 'wcc_selected_subcategories', true);
    $selected_tags = get_term_meta($term_id, 'wcc_selected_tags', true);
    $custom_subcategory_texts = get_term_meta($term_id, 'wcc_custom_subcategory_texts', true);
    $custom_tag_texts = get_term_meta($term_id, 'wcc_custom_tag_texts', true);
    $enable_card = get_term_meta($term_id, 'wcc_enable_card', true);
    $card_type = ($taxonomy === 'product_cat') ? 'Kategorie' : 'Schlagwort';
    $image_id = get_term_meta($term_id, 'wcc_image', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    $partner_shops = get_option('wcc_partner_shops', []);
    $selected_partner_shops = get_term_meta($term_id, 'wcc_selected_partner_shops', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="wcc_enable_card"><?php echo $card_type; ?>-Karte aktivieren</label></th>
        <td>
            <input type="checkbox" name="wcc_enable_card" id="wcc_enable_card" value="1" <?php checked($enable_card, '1'); ?>>
            <label for="wcc_enable_card">Aktivieren Sie diese Option, um die <?php echo $card_type; ?>-Karte anzuzeigen.</label>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="wcc_image"><?php echo $card_type; ?>-Bild</label></th>
        <td>
            <div class="wcc-image-preview">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 200px; height: auto;">
                <?php endif; ?>
            </div>
            <input type="hidden" name="wcc_image" id="wcc_image" value="<?php echo esc_attr($image_id); ?>">
            <button type="button" class="button wcc-upload-image">Bild auswählen</button>
            <button type="button" class="button wcc-remove-image" <?php echo $image_id ? '' : 'style="display:none;"'; ?>>Bild entfernen</button>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="wcc_info_contents">Was ist drin?</label></th>
        <td>
            <?php
            wp_editor(
                $info['contents'] ?? '',
                'wcc_info_contents',
                array(
                    'textarea_name' => 'wcc_info[contents]',
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
        <th scope="row" valign="top"><label for="wcc_info_target_audience">Für wen?</label></th>
        <td>
            <?php
            wp_editor(
                $info['target_audience'] ?? '',
                'wcc_info_target_audience',
                array(
                    'textarea_name' => 'wcc_info[target_audience]',
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
        <th scope="row" valign="top"><label for="wcc_info_age_group">Altersgruppe</label></th>
        <td>
            <?php
            wp_editor(
                $info['age_group'] ?? '',
                'wcc_info_age_group',
                array(
                    'textarea_name' => 'wcc_info[age_group]',
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
        <th scope="row" valign="top"><label for="wcc_info_special_features">Was ist besonders?</label></th>
        <td>
            <?php
            wp_editor(
                $info['special_features'] ?? '',
                'wcc_info_special_features',
                array(
                    'textarea_name' => 'wcc_info[special_features]',
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
    <th scope="row" valign="top"><label for="wcc_info[pinterest_board_url]">Pinterest Board URL</label></th>
    <td>
        <input type="url" name="wcc_info[pinterest_board_url]" id="wcc_info[pinterest_board_url]" value="<?php echo esc_url($info['pinterest_board_url'] ?? ''); ?>">
        <p class="description">Geben Sie die URL zum spezifischen Pinterest-Board für diese Kategorie/dieses Schlagwort ein. Lassen Sie das Feld leer, um das Standard-Profil zu verwenden.</p>
    </td>
</tr>
    <tr class="form-field">
    <th scope="row" valign="top"><label>Partner Shops</label></th>
    <td>
        <select name="wcc_selected_partner_shops[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
            <?php foreach ($partner_shops as $index => $shop): ?>
                <option value="<?php echo $index; ?>" <?php echo in_array($index, (array)$selected_partner_shops) ? 'selected' : ''; ?>>
                    <?php echo esc_html($shop['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
    <tr class="form-field">
    <th scope="row" valign="top"><label for="wcc_custom_description">Benutzerdefinierte Beschreibung</label></th>
    <td>
        <?php
        $custom_description = get_term_meta($term_id, 'wcc_custom_description', true);
        wp_editor($custom_description, 'wcc_custom_description', array(
            'textarea_name' => 'wcc_custom_description',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => true
        ));
        ?>
    </td>
</tr>
    <tr class="form-field">
    <th scope="row" valign="top"><label for="wcc_custom_header_text">Benutzerdefinierter Headertext</label></th>
    <td>
        <?php
        $custom_header_text = get_term_meta($term_id, 'wcc_custom_header_text', true);
        wp_editor($custom_header_text, 'wcc_custom_header_text', array(
            'textarea_name' => 'wcc_custom_header_text',
            'media_buttons' => true,
            'textarea_rows' => 5,
            'teeny' => true
        ));
        ?>
    </td>
</tr>
<tr class="form-field">
<th scope="row" valign="top"><label>Ausgewählte Unterkategorien</label></th>
<td>
    <select name="wcc_selected_subcategories[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
        <?php
        $subcategories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'hierarchical' => true  // This ensures we get all levels
        ]);
        foreach ($subcategories as $subcategory) {
            $selected = in_array($subcategory->term_id, (array)$selected_subcategories) ? 'selected' : '';
            // Add indentation for child categories
            $indent = str_repeat('&nbsp;', 3 * count(get_ancestors($subcategory->term_id, 'product_cat')));
            echo '<option value="' . esc_attr($subcategory->term_id) . '" ' . $selected . '>' . $indent . esc_html($subcategory->name) . '</option>';
        }
        ?>
    </select>
</td>
</tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Benutzerdefinierte Texte für Unterkategorien</label></th>
        <td>
            <?php
            if (!empty($selected_subcategories)) {
                foreach ($selected_subcategories as $subcategory_id) {
                    $subcategory = get_term($subcategory_id, 'product_cat');
                    if ($subcategory) {
                        echo '<p><strong>' . esc_html($subcategory->name) . ':</strong><br>';
                        echo '<textarea name="wcc_custom_subcategory_texts[' . $subcategory_id . ']" rows="2" cols="50">' .
                             esc_textarea($custom_subcategory_texts[$subcategory_id] ?? '') . '</textarea></p>';
                    }
                }
            }
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Ausgewählte Tags</label></th>
        <td>
            <select name="wcc_selected_tags[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
                <?php
                $tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
                foreach ($tags as $tag) {
                    $selected = in_array($tag->term_id, (array)$selected_tags) ? 'selected' : '';
                    echo '<option value="' . esc_attr($tag->term_id) . '" ' . $selected . '>' . esc_html($tag->name) . '</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Benutzerdefinierte Texte für Tags</label></th>
        <td>
            <?php
            if (!empty($selected_tags)) {
                foreach ($selected_tags as $tag_id) {
                    $tag = get_term($tag_id, 'product_tag');
                    if ($tag) {
                        echo '<p><strong>' . esc_html($tag->name) . ':</strong><br>';
                        echo '<textarea name="wcc_custom_tag_texts[' . $tag_id . ']" rows="2" cols="50">' .
                             esc_textarea($custom_tag_texts[$tag_id] ?? '') . '</textarea></p>';
                    }
                }
            }
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label>Personas</label></th>
        <td>
            <div id="wcc-personas">
                <?php
                $personas = get_term_meta($term_id, 'wcc_personas', true);
                if (!is_array($personas)) {
                    $personas = array();
                }
                foreach ($personas as $index => $persona) {
                    wcc_render_persona_fields($index, $persona);
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
                    action: 'wcc_add_persona_fields',
                    index: personaCount
                },
                success: function(response) {
                    if (response.success) {
                        $('#wcc-personas').append(response.data);
                        personaCount++;
                    } else {
                        console.error('Fehler beim Hinzufügen der Persona');
                    }
                }
            });
        });

        $(document).on('click', '.delete-persona', function() {
            $(this).closest('.wcc-persona').remove();
            updatePersonaIndices();
        });

        function updatePersonaIndices() {
            $('.wcc-persona').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('h4').text('Persona ' + (index + 1));
                $(this).find('input, textarea, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/wcc_personas\[\d+\]/, 'wcc_personas[' + index + ']'));
                    }
                });
            });
        }

        $('.wcc-select2').select2();

        // Bildupload-Funktionalität
        $('.wcc-upload-image').click(function(e) {
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
                $('#wcc_image').val(attachment.id);
                $('.wcc-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                $('.wcc-remove-image').show();
            }).open();
        });

        $('.wcc-remove-image').click(function(e) {
            e.preventDefault();
            $('#wcc_image').val('');
            $('.wcc-image-preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

function wcc_render_persona_fields($index, $persona) {
    ?>
    <div class="wcc-persona" data-index="<?php echo $index; ?>">
        <h4>Persona <?php echo $index + 1; ?></h4>
        <p>
            <label>Name:</label>
            <input type="text" name="wcc_personas[<?php echo $index; ?>][name]" value="<?php echo esc_attr($persona['name'] ?? ''); ?>">
        </p>
        <p>
            <label>Beschreibung:</label>
            <textarea name="wcc_personas[<?php echo $index; ?>][description]"><?php echo esc_textarea($persona['description'] ?? ''); ?></textarea>
        </p>
        <p>
            <label>Bild URL:</label>
            <input type="text" name="wcc_personas[<?php echo $index; ?>][image]" value="<?php echo esc_url($persona['image'] ?? ''); ?>">
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
                        echo '<label><input type="checkbox" name="wcc_personas[' . $index . '][filters][' . $attribute_name . '][]" value="' . $term->slug . '" ' . $checked . '> ' . $term->name . '</label><br>';
                    }
                }
            }
            ?>
        </p>
        <button type="button" class="button delete-persona">Persona löschen</button>
    </div>
    <?php
}

add_action('wp_ajax_wcc_add_persona_fields', 'wcc_ajax_add_persona_fields');
function wcc_ajax_add_persona_fields() {
    $index = isset($_POST['index']) ? intval($_POST['index']) : 0;
    ob_start();
    wcc_render_persona_fields($index, array());
    $html = ob_get_clean();
    wp_send_json_success($html);
}

// Speichere die Info für Kategorien und Tags
add_action('edited_product_cat', 'wcc_save_info', 10, 2);
add_action('edited_product_tag', 'wcc_save_info', 10, 2);
add_action('created_product_cat', 'wcc_save_info', 10, 2);
add_action('created_product_tag', 'wcc_save_info', 10, 2);

function wcc_save_info($term_id, $tt_id) {
    $taxonomy = $_POST['taxonomy'] ?? '';

    $enable_card = isset($_POST['wcc_enable_card']) ? '1' : '0';
    update_term_meta($term_id, 'wcc_enable_card', $enable_card);

    if (isset($_POST['wcc_image'])) {
        $image_id = intval($_POST['wcc_image']);
        update_term_meta($term_id, 'wcc_image', $image_id);
    }

    if (isset($_POST['wcc_info'])) {
        $info = $_POST['wcc_info'];
        $info = array_map('wp_kses_post', $info);
        $info['pinterest_board_url'] = esc_url_raw($info['pinterest_board_url']);
        update_term_meta($term_id, 'wcc_info', $info);
    }

    if (isset($_POST['wcc_personas'])) {
        $personas = $_POST['wcc_personas'];
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
        // Neu-Indizierung der Personas
        $personas = array_values($personas);
        update_term_meta($term_id, 'wcc_personas', $personas);
    }

    if (isset($_POST['wcc_selected_subcategories'])) {
        $selected_subcategories = array_map('intval', $_POST['wcc_selected_subcategories']);
        update_term_meta($term_id, 'wcc_selected_subcategories', $selected_subcategories);
    }

    if (isset($_POST['wcc_selected_tags'])) {
        $selected_tags = array_map('intval', $_POST['wcc_selected_tags']);
        update_term_meta($term_id, 'wcc_selected_tags', $selected_tags);
    }

    if (isset($_POST['wcc_custom_subcategory_texts'])) {
        $custom_subcategory_texts = array_map('sanitize_textarea_field', $_POST['wcc_custom_subcategory_texts']);
        update_term_meta($term_id, 'wcc_custom_subcategory_texts', $custom_subcategory_texts);
    }

    if (isset($_POST['wcc_custom_tag_texts'])) {
        $custom_tag_texts = array_map('sanitize_textarea_field', $_POST['wcc_custom_tag_texts']);
        update_term_meta($term_id, 'wcc_custom_tag_texts', $custom_tag_texts);
    }

    if (isset($_POST['wcc_custom_description'])) {
        $custom_description = wp_kses_post($_POST['wcc_custom_description']);
        update_term_meta($term_id, 'wcc_custom_description', $custom_description);
    }

    if (isset($_POST['wcc_custom_header_text'])) {
        $custom_header_text = wp_kses_post($_POST['wcc_custom_header_text']);
        update_term_meta($term_id, 'wcc_custom_header_text', $custom_header_text);
    }

    if (isset($_POST['wcc_selected_partner_shops'])) {
        $selected_partner_shops = array_map('intval', $_POST['wcc_selected_partner_shops']);
        update_term_meta($term_id, 'wcc_selected_partner_shops', $selected_partner_shops);
    }
}

function wcc_enqueue_wp_editor() {
    wp_enqueue_editor();
}
add_action('admin_enqueue_scripts', 'wcc_enqueue_wp_editor');

// Füge Felder zur Kategorie- und Tag-Erstellungsseite hinzu
add_action('product_cat_add_form_fields', 'wcc_add_info_fields');
add_action('product_tag_add_form_fields', 'wcc_add_info_fields');

function wcc_add_info_fields($taxonomy) {
    $card_type = ($taxonomy === 'product_cat') ? 'Kategorie' : 'Schlagwort';
    $partner_shops = get_option('wcc_partner_shops', []);
    ?>
    <div class="form-field">
        <label for="wcc_enable_card">
            <input type="checkbox" name="wcc_enable_card" id="wcc_enable_card" value="1">
            <?php echo $card_type; ?>-Karte aktivieren
        </label>
        <p class="description">Aktivieren Sie diese Option, um die <?php echo $card_type; ?>-Karte anzuzeigen.</p>
    </div>
    <div class="form-field">
        <label for="wcc_image"><?php echo $card_type; ?>-Bild</label>
        <div class="wcc-image-preview"></div>
        <input type="hidden" name="wcc_image" id="wcc_image" value="">
        <button type="button" class="button wcc-upload-image">Bild auswählen</button>
        <button type="button" class="button wcc-remove-image" style="display:none;">Bild entfernen</button>
    </div>
    <div class="form-field">
        <label for="wcc_info_contents">Was ist drin?</label>
        <?php
        wp_editor(
            '',
            'wcc_info_contents',
            array(
                'textarea_name' => 'wcc_info[contents]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="wcc_info_target_audience">Für wen?</label>
        <?php
        wp_editor(
            '',
            'wcc_info_target_audience',
            array(
                'textarea_name' => 'wcc_info[target_audience]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="wcc_info_age_group">Altersgruppe</label>
        <?php
        wp_editor(
            '',
            'wcc_info_age_group',
            array(
                'textarea_name' => 'wcc_info[age_group]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="wcc_info_special_features">Was ist besonders?</label>
        <?php
        wp_editor(
            '',
            'wcc_info_special_features',
            array(
                'textarea_name' => 'wcc_info[special_features]',
                'textarea_rows' => 5,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => false
            )
        );
        ?>
    </div>
    <div class="form-field">
        <label for="wcc_info[pinterest_board_url]">Pinterest Board URL</label>
        <input type="url" name="wcc_info[pinterest_board_url]" id="wcc_info[pinterest_board_url]">
        <p class="description">Geben Sie die URL zum spezifischen Pinterest-Board für diese Kategorie/dieses Schlagwort ein. Lassen Sie das Feld leer, um das Standard-Profil zu verwenden.</p>
    </div>
    <div class="form-field">
        <label>Partner Shops</label>
        <select name="wcc_selected_partner_shops[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
            <?php foreach ($partner_shops as $index => $shop): ?>
                <option value="<?php echo $index; ?>"><?php echo esc_html($shop['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-field">
        <label for="wcc_custom_description">Benutzerdefinierte Beschreibung</label>
        <?php
        wp_editor('', 'wcc_custom_description', array(
            'textarea_name' => 'wcc_custom_description',
            'media_buttons' => true,
            'textarea_rows' => 10,
            'teeny' => true
        ));
        ?>
    </div>
    <div class="form-field">
        <label for="wcc_custom_header_text">Benutzerdefinierter Headertext</label>
        <?php
        wp_editor('', 'wcc_custom_header_text', array(
            'textarea_name' => 'wcc_custom_header_text',
            'media_buttons' => true,
            'textarea_rows' => 5,
            'teeny' => true
        ));
        ?>
    </div>
    <div class="form-field">
        <label>Ausgewählte Unterkategorien</label>
        <select name="wcc_selected_subcategories[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
            <?php
            $subcategories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
            foreach ($subcategories as $subcategory) {
                echo '<option value="' . esc_attr($subcategory->term_id) . '">' . esc_html($subcategory->name) . '</option>';
            }
            ?>
        </select>
    </div>
    <div class="form-field">
        <label>Ausgewählte Tags</label>
        <select name="wcc_selected_tags[]" multiple="multiple" class="wcc-select2" style="width: 100%;">
            <?php
            $tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]);
            foreach ($tags as $tag) {
                echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . '</option>';
            }?>
        </select>
    </div>
    <div class="form-field">
        <label>Personas</label>
        <div id="wcc-personas">
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
                    action: 'wcc_add_persona_fields',
                    index: personaCount
                },
                success: function(response) {
                    if (response.success) {
                        $('#wcc-personas').append(response.data);
                        personaCount++;
                    } else {
                        console.error('Fehler beim Hinzufügen der Persona');
                    }
                }
            });
        });

        $(document).on('click', '.delete-persona', function() {
            $(this).closest('.wcc-persona').remove();
            updatePersonaIndices();
        });

        function updatePersonaIndices() {
            $('.wcc-persona').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('h4').text('Persona ' + (index + 1));
                $(this).find('input, textarea, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/wcc_personas\[\d+\]/, 'wcc_personas[' + index + ']'));
                    }
                });
            });
        }

        $('.wcc-select2').select2();

        // Bildupload-Funktionalität
        $('.wcc-upload-image').click(function(e) {
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
                $('#wcc_image').val(attachment.id);
                $('.wcc-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
                $('.wcc-remove-image').show();
            }).open();
        });

        $('.wcc-remove-image').click(function(e) {
            e.preventDefault();
            $('#wcc_image').val('');
            $('.wcc-image-preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Füge AJAX URL zum Admin-Bereich hinzu
add_action('admin_head', 'wcc_add_ajax_url');
function wcc_add_ajax_url() {
    ?>
    <script type="text/javascript">
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    </script>
    <?php
}

add_action('admin_init', 'wcc_partner_shops_settings_init');

function wcc_partner_shops_settings_init() {
    register_setting('wcc_partner_shops_options', 'wcc_partner_shops');

    add_settings_section(
        'wcc_partner_shops_section',
        'Partner Shops Settings',
        'wcc_partner_shops_section_callback',
        'wcc-partner-shops-settings'
    );

    add_settings_field(
        'wcc_partner_shops_list',
        'Partner Shops',
        'wcc_partner_shops_list_callback',
        'wcc-partner-shops-settings',
        'wcc_partner_shops_section'
    );
}

function wcc_partner_shops_section_callback() {
    echo 'Define your partner shops here.';
}

function wcc_partner_shops_list_callback() {
    $partner_shops = get_option('wcc_partner_shops', []);
    ?>
    <div id="partner-shops-container">
        <?php foreach ($partner_shops as $index => $shop): ?>
            <div class="partner-shop">
              <input type="text" name="wcc_partner_shops[<?php echo $index; ?>][name]" value="<?php echo esc_attr($shop['name']); ?>" placeholder="Shop Name">
                <input type="text" name="wcc_partner_shops[<?php echo $index; ?>][url]" value="<?php echo esc_attr($shop['url']); ?>" placeholder="Shop URL">
                <div class="logo-upload">
                    <input type="hidden" name="wcc_partner_shops[<?php echo $index; ?>][logo]" value="<?php echo esc_attr($shop['logo']); ?>" class="logo-id">
                    <img src="<?php echo wp_get_attachment_image_url($shop['logo'], 'thumbnail'); ?>" style="max-width:100px;max-height:100px;<?php echo empty($shop['logo']) ? 'display:none;' : ''; ?>" class="logo-preview">
                    <button type="button" class="upload-logo-button button">Upload Logo</button>
                    <button type="button" class="remove-logo-button button" <?php echo empty($shop['logo']) ? 'style="display:none;"' : ''; ?>>Remove Logo</button>
                </div>
                <button type="button" class="remove-shop button">Remove Shop</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-partner-shop" class="button button-primary">Add Partner Shop</button>

    <script>
    jQuery(document).ready(function($) {
        var shopIndex = <?php echo count($partner_shops); ?>;

        $('#add-partner-shop').on('click', function() {
            var newShop = `
                <div class="partner-shop">
                    <input type="text" name="wcc_partner_shops[${shopIndex}][name]" placeholder="Shop Name">
                    <input type="text" name="wcc_partner_shops[${shopIndex}][url]" placeholder="Shop URL">
                    <div class="logo-upload">
                        <input type="hidden" name="wcc_partner_shops[${shopIndex}][logo]" class="logo-id">
                        <img src="" style="max-width:100px;max-height:100px;display:none;" class="logo-preview">
                        <button type="button" class="upload-logo-button button">Upload Logo</button>
                        <button type="button" class="remove-logo-button button" style="display:none;">Remove Logo</button>
                    </div>
                    <button type="button" class="remove-shop button">Remove Shop</button>
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
                title: 'Choose Logo',
                button: {
                    text: 'Use this image'
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
add_action('admin_enqueue_scripts', 'wcc_enqueue_admin_scripts');
function wcc_enqueue_admin_scripts() {
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
    wp_enqueue_media();
}

// AJAX-Handler für das dynamische Laden der Textfelder für Unterkategorien und Tags
add_action('wp_ajax_wcc_load_custom_text_fields', 'wcc_load_custom_text_fields');
function wcc_load_custom_text_fields() {
    if (!isset($_POST['term_id']) || !isset($_POST['taxonomy'])) {
        wp_send_json_error('Ungültige Anfrage');
    }

    $term_id = intval($_POST['term_id']);
    $taxonomy = sanitize_text_field($_POST['taxonomy']);

    if ($taxonomy === 'product_cat') {
        $custom_texts = get_term_meta($term_id, 'wcc_custom_subcategory_texts', true);
    } elseif ($taxonomy === 'product_tag') {
        $custom_texts = get_term_meta($term_id, 'wcc_custom_tag_texts', true);
    } else {
        wp_send_json_error('Ungültige Taxonomie');
    }

    $term = get_term($term_id, $taxonomy);
    if (!$term || is_wp_error($term)) {
        wp_send_json_error('Term nicht gefunden');
    }

    $html = '<p><strong>' . esc_html($term->name) . ':</strong><br>';
    $html .= '<textarea name="wcc_custom_' . $taxonomy . '_texts[' . $term_id . ']" rows="3" cols="50">' .
             esc_textarea($custom_texts[$term_id] ?? '') . '</textarea></p>';

    wp_send_json_success($html);
}

// Speichern der Partner Shops
add_action('admin_init', 'wcc_save_partner_shops');
function wcc_save_partner_shops() {
    if (isset($_POST['wcc_partner_shops'])) {
        $partner_shops = $_POST['wcc_partner_shops'];
        foreach ($partner_shops as &$shop) {
            $shop['name'] = sanitize_text_field($shop['name']);
            $shop['url'] = esc_url_raw($shop['url']);
            $shop['logo'] = absint($shop['logo']);
        }
        update_option('wcc_partner_shops', $partner_shops);
    }
}
