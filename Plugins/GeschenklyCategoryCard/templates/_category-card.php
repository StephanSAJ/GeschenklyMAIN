<?php
if (!defined('ABSPATH')) exit;

/**
 * Lädt die beliebtesten Produkte aus dem Cache oder generiert sie neu.
 */
function wcc_get_mostpopular_products_cached($category_id) {
    $transient_key = 'wcc_mostpopular_products_'.$category_id;
    $data = get_transient($transient_key);
    if ($data === false) {
        $data = wcc_get_mostpopular_products($category_id);
        set_transient($transient_key, $data, HOUR_IN_SECONDS);
    }
    return $data;
}

/**
 * Lädt aufstrebende Produkte aus dem Cache oder generiert sie neu.
 */
function wcc_get_rising_products_cached($category_id, $limit = 4) {
    $transient_key = 'wcc_rising_products_'.$category_id.'_'.$limit;
    $data = get_transient($transient_key);
    if ($data === false) {
        $data = wcc_get_rising_products($category_id, $limit);
        set_transient($transient_key, $data, HOUR_IN_SECONDS);
    }
    return $data;
}

/**
 * Lädt Trend-Unterkategorien aus dem Cache oder generiert sie neu.
 */
function wcc_get_trending_subcategories_cached($category_id, $selected_subcategories) {
    $transient_key = 'wcc_trending_subcategories_'.$category_id;
    $data = get_transient($transient_key);
    if ($data === false) {
        $data = wcc_get_trending_subcategories($category_id, $selected_subcategories);
        set_transient($transient_key, $data, HOUR_IN_SECONDS);
    }
    return $data;
}

/**
 * Lädt Produkte mit 6-monats Popularität aus dem Cache oder generiert sie neu.
 */
function wcc_get_six_months_popular_products_cached($category_id, $limit = 4) {
    $transient_key = 'wcc_six_months_popular_products_'.$category_id.'_'.$limit;
    $data = get_transient($transient_key);
    if ($data === false) {
        $data = wcc_get_six_months_popular_products($category_id, $limit);
        set_transient($transient_key, $data, HOUR_IN_SECONDS);
    }
    return $data;
}

/**
 * Gibt die Anzahl der Produkte in einer Kategorie zurück.
 */
function get_product_count_in_category($category_id) {
    $transient_key = 'wcc_product_count_category_'.$category_id;
    $count = get_transient($transient_key);
    if ($count === false) {
        $args = array(
            'status' => 'publish',
            'limit' => -1,
            'return' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );
        $products = wc_get_products($args);
        $count = count($products);
        set_transient($transient_key, $count, HOUR_IN_SECONDS);
    }
    return $count;
}

/**
 * Gibt zufällige Produkte aus einer bestimmten Kategorie zurück.
 */
function get_random_products_from_category($category_id, $count = 12, $exclude = array()) {
    $args = array(
        'status' => 'publish',
        'limit' => $count,
        'orderby' => 'rand',
        'exclude' => $exclude,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
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

$category_id = get_queried_object_id();
wcc_record_category_visit($category_id);
$term_meta = get_term_meta($category_id);
$category_info = isset($term_meta['wcc_info'][0]) ? maybe_unserialize($term_meta['wcc_info'][0]) : array();
$all_popular_products = wcc_get_mostpopular_products_cached($category_id);
$personas = isset($term_meta['wcc_personas'][0]) ? maybe_unserialize($term_meta['wcc_personas'][0]) : array();
$selected_subcategories = isset($term_meta['wcc_selected_subcategories'][0]) ? maybe_unserialize($term_meta['wcc_selected_subcategories'][0]) : array();
$selected_tags = isset($term_meta['wcc_selected_tags'][0]) ? maybe_unserialize($term_meta['wcc_selected_tags'][0]) : array();
$custom_subcategory_texts = isset($term_meta['wcc_custom_subcategory_texts'][0]) ? maybe_unserialize($term_meta['wcc_custom_subcategory_texts'][0]) : array();
$custom_tag_texts = isset($term_meta['wcc_custom_tag_texts'][0]) ? maybe_unserialize($term_meta['wcc_custom_tag_texts'][0]) : array();
$rising_products = wcc_get_rising_products_cached($category_id, 4);
$custom_description = isset($term_meta['wcc_custom_description'][0]) ? $term_meta['wcc_custom_description'][0] : '';
$category_image_id_meta = isset($term_meta['category_image'][0]) ? $term_meta['category_image'][0] : '';
$custom_header_text = isset($term_meta['wcc_custom_header_text'][0]) ? $term_meta['wcc_custom_header_text'][0] : '';
$wcc_image = isset($term_meta['wcc_image'][0]) ? $term_meta['wcc_image'][0] : '';

$term = get_term($category_id);
$category_name = $term->name;
$category_description = $term->description;
$trending_subcategories = wcc_get_trending_subcategories_cached($category_id, $selected_subcategories);

/**
 * Preload der ersten 3 Produktbilder zur Verbesserung des LCP.
 */
function wcc_optimize_first_product_images($all_popular_products) {
    if (empty($all_popular_products)) return;
    $preload_count = min(3, count($all_popular_products));
    add_action('wp_head', function() use ($all_popular_products, $preload_count) {
        for ($i = 0; $i < $preload_count; $i++) {
            $image_id = get_post_thumbnail_id($all_popular_products[$i]['id']);
            $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
            if ($image_url) {
                $priority = $i === 0 ? 'high' : 'low';
                echo '<link rel="preload" as="image" href="' . esc_url($image_url) . '" fetchpriority="' . $priority . '">';
            }
        }
    }, 1);
}
wcc_optimize_first_product_images($all_popular_products);

/**
 * Funktion zum optimierten Rendering der Produktbilder.
 */
function render_optimized_product_image($product, $index) {
    $image_id = get_post_thumbnail_id($product['id']);
    $image_url = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
    $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

    $loading = $index < 3 ? 'eager' : 'lazy';
    $decoding = $index < 3 ? 'sync' : 'async';
    $fetchpriority = $index === 0 ? 'high' : 'low';
    $class = $index === 0 ? 'product-image lcp-image' : 'product-image';

    $srcset = wp_get_attachment_image_srcset($image_id, 'woocommerce_thumbnail');
    $sizes = wp_get_attachment_image_sizes($image_id, 'woocommerce_thumbnail');

    printf(
        '<img src="%1$s"
             alt="%2$s"
             width="300"
             height="300"
             loading="%3$s"
             decoding="%4$s"
             fetchpriority="%5$s"
             class="%6$s"
             srcset="%7$s"
             sizes="%8$s">',
        esc_url($image_url),
        esc_attr($image_alt),
        $loading,
        $decoding,
        $fetchpriority,
        $class,
        $srcset,
        $sizes
    );
}

/**
 * Optimiertes Laden des Kategoriebildes.
 */
function render_optimized_category_image($category_name, $wcc_image) {
    $category_image_url = '';
    if ($wcc_image) {
        $category_image_url = wp_get_attachment_image_url($wcc_image, 'medium'); // Mittelgroße Größe statt full
    }
    $default_image = '/wp-content/uploads/2024/07/category-placeholder.webp';

    printf(
        '<img width="180"
             height="180"
             fetchpriority="high"
             src="%s"
             alt="%s"
             class="category-image"
             loading="eager"
             decoding="sync">',
        esc_url($category_image_url ?: $default_image),
        esc_attr($category_name)
    );
}

/**
 * Progressive Image Loading: Hole Platzhalter und Vollbild URLs.
 */
function get_progressive_image_url($image_id, $size) {
    $placeholder = wp_get_attachment_image_url($image_id, 'thumbnail');
    $full = wp_get_attachment_image_url($image_id, $size);
    return [
        'placeholder' => $placeholder,
        'full' => $full
    ];
}

/**
 * CSS Optimierungen für Bilder etc.
 */
add_action('wp_head', function() {
    ?>
    <style>
        .product-image-container {
            aspect-ratio: 1/1;
            width: 100%;
            contain: strict;
        }
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .category-image {
            width: 180px;
            height: 180px;
            object-fit: cover;
        }
    </style>
    <?php
}, 1);
?>
<div class="category-card-wrapper" id="category-card" data-category-id="<?php echo esc_attr($category_id); ?>">
    <div class="category-card">
        <div class="category-content">
            <div class="category-info-section">
                <div class="category-info-header">
                    <div class="category-image-wrapper">
                      <?php render_optimized_category_image($category_name, $wcc_image); ?>
                    </div>
                    <div class="category-title-wrapper">
                        <h1><?php echo esc_html($category_name); ?></h1>
                        <?php if (!empty($custom_header_text)): ?>
                            <p class="category-tagline"><?php echo wp_kses_post($custom_header_text); ?></p>
                        <?php else: ?>
                            <p class="category-tagline">Entdecke einzigartige Geschenkideen, die begeistern</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div>
              <h2>Aktuell beliebte Geschenkideen</h2>
              <div class="product-grid" id="popularProducts">
              <?php
              $initial_display_count = 12;
              $index = 0;
              foreach (array_slice($all_popular_products, 0, $initial_display_count) as $product):
                  $wc_product = wc_get_product($product['id']);
                  $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop ➜', 'woocommerce');
              ?>
                  <div class="product-item">
                    <div class="product-image-container">
                        <a href="<?php echo esc_url(get_permalink($product['id'])); ?>">
                            <?php render_optimized_product_image($product, $index); ?>
                        </a>
                    </div>
                      <a class="product-card-link" href="<?php echo esc_url(get_permalink($product['id'])); ?>"><?php echo esc_html($product['name']); ?></a>
                      <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                         href="/redirect.php?product=<?php echo esc_attr($wc_product ? $wc_product->get_slug() : ''); ?>"
                         class="ansehen-button">
                          <?php echo esc_html($button_text); ?>
                      </a>
                      <div class="awp-wishlist-button-container">
                          <button class="awp-add-to-wishlist"
                              data-product-id="<?php echo esc_attr($product['id']); ?>"
                              data-product-name="<?php echo esc_attr($product['name']); ?>"
                              data-product-url="<?php echo esc_url(get_permalink($product['id'])); ?>"
                              aria-label="Zur Wunschliste hinzufügen">
                              <i class="fa fa-heart" aria-hidden="true"></i>
                          </button>
                      </div>
                  </div>
              <?php $index++; endforeach; ?>
              </div>
              <div class="show-more-container">
                  <button class="show-more" id="showMorePopularProducts" data-product-type="popular">
                      MEHR ANZEIGEN
                      <i class="fas fa-chevron-down"></i>
                  </button>
              </div>
              <div class="category-info-content">
                  <div class="info-item">
                      <h3><i class="fas fa-gift"></i> Was ist drin?</h3>
                      <div><?php echo wp_kses_post($category_info['contents'] ?? ''); ?></div>
                  </div>
                  <div class="info-item">
                      <h3><i class="fas fa-users"></i> Für wen?</h3>
                      <div><?php echo wp_kses_post($category_info['target_audience'] ?? ''); ?></div>
                  </div>
                  <div class="info-item">
                      <h3><i class="fas fa-birthday-cake"></i> Altersgruppe</h3>
                      <div><?php echo wp_kses_post($category_info['age_group'] ?? ''); ?></div>
                  </div>
                  <div class="info-item">
                      <h3><i class="fas fa-star"></i> Was ist besonders?</h3>
                      <div><?php echo wp_kses_post($category_info['special_features'] ?? ''); ?></div>
                  </div>
              </div>
              <div class="category-info-footer">
                  <div class="trend-link">
                      <i class="fas fa-chart-line"></i>
                      <span>Ähnlich im Trend:</span>
                      <?php if (!empty($trending_subcategories)): ?>
                          <?php foreach ($trending_subcategories as $subcategory):
                              $subcategory_id = $subcategory['_id'];
                              $subcategory_name = $subcategory['category_name'];
                              $subcategory_url = get_term_link((int) $subcategory_id, 'product_cat');
                              if (is_wp_error($subcategory_url)) {
                                  continue;
                              }
                          ?>
                              <a href="<?php echo esc_url($subcategory_url); ?>" class="trend-button"><?php echo esc_html($subcategory_name); ?></a>
                          <?php endforeach; ?>
                      <?php else: ?>
                          <span class="no-trend">Keine Trend-Kategorien verfügbar.</span>
                      <?php endif; ?>
                  </div>
                  <div class="pinterest-link">
                      <i class="fab fa-pinterest"></i>
                      <span>Unsere Ideen:</span>
                      <?php
                      $pinterest_url = !empty($category_info['pinterest_board_url'])
                          ? $category_info['pinterest_board_url']
                          : 'https://www.pinterest.de/geschenkly';
                      ?>
                      <a href="<?php echo esc_url($pinterest_url); ?>" target="_blank" rel="noopener noreferrer" class="pinterest-button">
                          Auf Pinterest entdecken
                      </a>
                  </div>
              </div>
            </div>
            <div class="feedback-hint">
                 <strong>Deine Meinung zählt</strong>: Mit  <i class="far fa-thumbs-up reaction-icon" aria-hidden="true"></i> oder  <i class="far fa-thumbs-down reaction-icon" aria-hidden="true"></i> bewerten, ob das Geschenk zum Anlass passend ist. Mit <i class="fa fa-heart"></i> setzt du die Geschenkidee auf deine Wunschliste.
            </div>
        <?php
        $product_count = get_product_count_in_category($category_id);
        $random_products = get_random_products_from_category($category_id);
        ?>
        <h2>Geschenke Inspiration</h2>
        <div class="random-products-section">
            <p>Lass dich überraschen und finde dein nächstes Geschenk! Bewerte zufällig ausgewählte Geschenkideen und erstelle so deine persönliche Favoritenliste. Das Beste daran: Deine Favoriten werden gesammelt  und können dir kostenlos per E-Mail zugeschickt werden. Über das Herz-Symbol kannst du deine Wunschliste füllen und dann verwalten und verschicken.</p>
            <div class="product-grid" id="randomProducts">
                <?php foreach ($random_products as $product): ?>
                    <div class="product-item" data-product-id="<?php echo $product['id']; ?>">
                        <div class="product-image-container">
                            <a href="<?php echo esc_url($product['url']); ?>">
                                <?php echo get_the_post_thumbnail($product['id'], 'woocommerce_thumbnail', array('loading'=>'lazy')); ?>
                            </a>
                            <div class="product-reaction-container">
                                <button class="reaction-button like-button"
                                        data-product-id="<?php echo esc_attr($product['id']); ?>"
                                        data-category-id="<?php echo esc_attr($category_id); ?>"
                                        aria-label="Dieses Produkt liken">
                                    <i class="far fa-thumbs-up reaction-icon" aria-hidden="true"></i>
                                </button>
                                <button class="reaction-button dislike-button"
                                        data-product-id="<?php echo esc_attr($product['id']); ?>"
                                        data-category-id="<?php echo esc_attr($category_id); ?>"
                                        aria-label="Dieses Produkt nicht mögen">
                                    <i class="far fa-thumbs-down reaction-icon" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="awp-wishlist-button-container">
                                <button class="awp-add-to-wishlist"
                                    data-product-id="<?php echo esc_attr($product['id']); ?>"
                                    data-product-name="<?php echo esc_attr($product['name']); ?>"
                                    data-product-url="<?php echo esc_url($product['url']); ?>"
                                    aria-label="Zur Wunschliste hinzufügen">
                                    <i class="fa fa-heart" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <a class="product-card-link" href="<?php echo esc_url($product['url']); ?>"><?php echo esc_html($product['name']); ?></a>
                        <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                           href="/redirect.php?product=<?php echo esc_attr($product['slug']); ?>"
                           class="ansehen-button">
                            <?php echo esc_html($product['button_text']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
<script>
jQuery(document).ready(function($) {
    var categoryId = <?php echo json_encode($category_id); ?>;
    var likedProducts = JSON.parse(localStorage.getItem('likedProducts')) || [];
    var dislikedProducts = [];
    var initialProductCount = <?php echo count($random_products); ?>;

    function updateLikedItemsCount() {
        var count = likedProducts.length;
        $('#liked-items-count').text(count);
    }

    function generateProductHtml(product) {
        return `
            <div class="product-item" data-product-id="${product.id}">
                <div class="product-image-container">
                    <a href="${product.url}">
                        <img src="${product.image}" alt="${product.name}">
                    </a>
                    <div class="awp-wishlist-button-container">
                        <button class="awp-add-to-wishlist"
                            data-product-id="${product.id}"
                            data-product-name="${product.name}"
                            data-product-url="${product.url}"
                            aria-label="Zur Wunschliste hinzufügen">
                            <i class="fa fa-heart" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="product-reaction-container">
                        <button class="reaction-button like-button" data-product-id="${product.id}" data-category-id="${categoryId}">
                            <i class="far fa-heart reaction-icon" aria-hidden="true"></i>
                        </button>
                        <button class="reaction-button dislike-button" data-product-id="${product.id}" data-category-id="${categoryId}">
                            <i class="far fa-thumbs-down reaction-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <a class="product-card-link" href="${product.url}">${product.name}</a>
                <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                   href="/redirect.php?product=${product.slug}"
                   class="ansehen-button">
                    ${product.button_text}
                </a>
            </div>
        `;
    }

    function loadNewProducts() {
        var excludedProducts = likedProducts.concat(dislikedProducts).concat(
            $.map($('#randomProducts .product-item'), function(item) {
                return $(item).data('product-id');
            })
        );
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_get_random_products',
                category_id: categoryId,
                count: 1,
                exclude: excludedProducts,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var newProductHtml = generateProductHtml(response.data[0]);
                    $('#randomProducts').append(newProductHtml);
                } else {
                    console.error('Fehler beim Laden neuer Produkte:', response.data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX-Fehler:', textStatus, errorThrown);
            }
        });
    }

    function handleProductReaction(productId, isLike) {
        var $productItem = $(`.product-item[data-product-id="${productId}"]`);
        var feedbackType = isLike ? 'like' : 'dislike';
        var isAlreadyLiked = likedProducts.includes(productId);
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_record_product_feedback',
                nonce: wcc_ajax.nonce,
                product_id: productId,
                category_id: categoryId,
                feedback_type: feedbackType
            },
            success: function(response) {
                if (response.success) {
                    if (isLike) {
                        if (!isAlreadyLiked) {
                            likedProducts.push(productId);
                            localStorage.setItem('likedProducts', JSON.stringify(likedProducts));
                            updateLikedItemsCount();
                        }
                        $productItem.addClass('liked');
                        $productItem.find('.like-button').addClass('active').find('i').removeClass('far').addClass('fas');
                        $productItem.find('.dislike-button').removeClass('active').find('i').removeClass('fas').addClass('far');
                        $productItem.css('border', '2px solid #4CAF50').css('box-shadow', '0 0 10px rgba(76, 175, 80, 0.5)');
                        $productItem.fadeOut(400, function() {
                            $(this).prependTo('#randomProducts').fadeIn(400);
                        });
                    } else {
                        if (isAlreadyLiked) {
                            likedProducts = likedProducts.filter(id => id != productId);
                            localStorage.setItem('likedProducts', JSON.stringify(likedProducts));
                            updateLikedItemsCount();
                        }
                        dislikedProducts.push(productId);
                        $productItem.removeClass('liked');
                        $productItem.find('.like-button').removeClass('active').find('i').removeClass('fas').addClass('far');
                        $productItem.find('.dislike-button').addClass('active').find('i').removeClass('far').addClass('fas');
                        $productItem.css('border', '').css('box-shadow', '');
                        $productItem.fadeOut(400, function() {
                            $(this).remove();
                            loadNewProducts();
                        });
                    }
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX-Fehler:', textStatus, errorThrown);
                alert('Es gab einen Fehler bei der Verbindung zum Server.');
            }
        });
    }

    function createPreviewBox() {
        var $previewBox = $(`
            <div id="preview-box" class="geschenkly-preview-box" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.5); max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; font-size: 20px;">Favoritenliste</h3>
                        <button id="close-preview-box" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #888;">&times;</button>
                    </div>
                    <div id="preview-products"></div>
                    <div id="email-form" class="geschenkly-email-form" style="margin-top: 15px;">
                        <div style="display: flex;">
                            <input type="email" id="email-input" placeholder="Deine E-Mail-Adresse" style="flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-right: none; border-radius: 4px 0 0 4px;">
                            <button id="send-button" style="background-color: #ffd700; border: none; padding: 10px 15px; cursor: pointer; border-radius: 0 4px 4px 0;">
                                <i class="fa fa-paper-plane" aria-hidden="true" style="color: #333;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        $('body').append($previewBox);
        $('#close-preview-box').on('click', function() {
            $('#preview-box').fadeOut();
        });
        $previewBox.on('click', function(e) {
            if ($(e.target).closest('#preview-box > div').length === 0) {
                $('#preview-box').fadeOut();
            }
        });
    }

    function updatePreviewBox() {
        var $previewProducts = $('#preview-products');
        $previewProducts.empty();
        if (likedProducts.length === 0) {
            $previewProducts.html('<p style="text-align: center; color: #666;">Keine gelikten Produkte vorhanden.</p>');
            return;
        }
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_get_liked_products_info',
                product_ids: likedProducts,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    response.data.forEach(function(product) {
                        var $productItem = $(`
                            <div class="preview-product-item" data-product-id="${product.id}" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding: 10px; background-color: #f9f9f9; border-radius: 4px;">
                                <div style="display: flex; align-items: center; flex-grow: 1;">
                                    <img src="${product.image}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 4px;">
                                    <span style="font-size: 14px;">${product.name}</span>
                                </div>
                                <button class="remove-product" style="background: none; border: none; color: #999; cursor: pointer; font-size: 16px; width: 20px; height: 20px; display: flex; justify-content: center; align-items: center; padding: 0; margin-left: 10px;">&times;</button>
                            </div>
                        `);
                        $previewProducts.append($productItem);
                    });
                } else {
                    console.error('Error loading product info:', response.data);
                    $previewProducts.html('<p style="text-align: center; color: #ff4d4d;">Fehler beim Laden der Produktinformationen.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                $previewProducts.html('<p style="text-align: center; color: #ff4d4d;">Fehler beim Laden der Produktinformationen.</p>');
            }
        });
        $('#preview-box').fadeIn();
    }

    function initializeLikedItemsIcon() {
        var $annaSupport = $('.anna-support');
        if ($annaSupport.length) {
            var $iconsContainer = $annaSupport.siblings('.geschenkly-icons-container');
            if (!$iconsContainer.length) {
                $iconsContainer = $('<div class="geschenkly-icons-container"></div>');
                $annaSupport.after($iconsContainer);
            }
            var $likedItemsIcon = $(`
                <div id="liked-items-icon" class="geschenkly-icon" style="cursor: pointer;">
                    <i class="far fa-thumbs-up reaction-icon" aria-hidden="true"></i>
                    <span id="liked-items-count">0</span>
                </div>
            `);
            $iconsContainer.append($likedItemsIcon);
            $likedItemsIcon.on('click', function(e) {
                e.preventDefault();
                updatePreviewBox();
            });
        }
    }

    function removeProduct(productId) {
        likedProducts = likedProducts.filter(id => id != productId);
        localStorage.setItem('likedProducts', JSON.stringify(likedProducts));
        $(`.preview-product-item[data-product-id="${productId}"]`).fadeOut(function() {
            $(this).remove();
            if (likedProducts.length === 0) {
                $('#preview-box').fadeOut();
            }
        });
        updateLikedItemsCount();
    }

    function initializeEventListeners() {
        $('#randomProducts').on('click', '.reaction-button', function() {
            var $button = $(this);
            var productId = $button.data('product-id');
            var isLike = $button.hasClass('like-button');
            handleProductReaction(productId, isLike);
        });
        $('#preview-products').on('click', '.remove-product', function() {
            var productId = $(this).closest('.preview-product-item').data('product-id');
            removeProduct(productId);
        });
        $('#send-button').on('click', function() {
            const email = $('#email-input').val();
            if (email) {
                $.ajax({
                    url: 'https://schindler-ventures.de:3004/api/receive-product-ids',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        email: email,
                        productIds: likedProducts
                    }),
                    success: function(response) {
                        alert('Deine gelikten Produkte wurden erfolgreich gespeichert!');
                        $('#preview-box').fadeOut();
                        $('#email-input').val('');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('API-Fehler:', textStatus, errorThrown);
                        alert('Es gab einen Fehler beim Speichern der Produkte. Bitte versuche es später erneut.');
                    }
                });
            } else {
                alert('Bitte gib eine gültige E-Mail-Adresse ein.');
            }
        });
        $('#loadMoreProducts').on('click', function() {
            loadNewProducts();
        });
        $('body').on('click', '.awp-add-to-wishlist', function(event) {
            event.preventDefault();
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            var productUrl = $(this).data('product-url');
            if (typeof awpAddToWishlist === 'function') {
                awpAddToWishlist({
                    productId: productId,
                    productName: productName,
                    productUrl: productUrl
                });
            } else {
                console.error('awpAddToWishlist Funktion nicht definiert.');
            }
        });
    }

    function init() {
        createPreviewBox();
        initializeLikedItemsIcon();
        initializeEventListeners();
        updateLikedItemsCount();
        likedProducts.forEach(function(productId) {
            var $productItem = $(`.product-item[data-product-id="${productId}"]`);
            if ($productItem.length) {
                $productItem.addClass('liked');
                $productItem.find('.like-button').addClass('active').find('i').removeClass('far').addClass('fas');
                $productItem.css('border', '2px solid #4CAF50').css('box-shadow', '0 0 10px rgba(76, 175, 80, 0.5)');
            }
        });
    }

    init();
});
</script>
<style>
.product-item.liked {
    border: 2px solid #4CAF50;
    box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
}
.product-item.liked .like-button {
    background-color: #4CAF50;
    color: white;
}
.awp-add-to-wishlist i {
    color: inherit;
}
</style>
        </div>
        <h2>Alle Geschenkideen - <?php echo $product_count; ?> <?php echo esc_html($category_name); ?></h2>
        <div class="product-grid" id="newProducts">
            <?php
            $args = array(
                'status' => 'publish',
                'limit' => 12,
                'orderby' => 'date',
                'order' => 'DESC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $category_id,
                    ),
                ),
            );
            $new_products = wc_get_products($args);
            if (!empty($new_products)) :
                foreach ($new_products as $wc_product):
                    ?>
                    <div class="product-item">
                        <div class="product-image-container">
                            <a href="<?php echo get_permalink($wc_product->get_id()); ?>">
                                <?php echo get_the_post_thumbnail($wc_product->get_id(), 'woocommerce_thumbnail', array('loading'=>'lazy')); ?>
                            </a>
                            <div class="awp-wishlist-button-container">
                                <button class="awp-add-to-wishlist"
                                    data-product-id="<?php echo esc_attr($wc_product->get_id()); ?>"
                                    data-product-name="<?php echo esc_attr($wc_product->get_name()); ?>"
                                    data-product-url="<?php echo esc_url(get_permalink($wc_product->get_id())); ?>"
                                    aria-label="Zur Wunschliste hinzufügen">
                                    <i class="fa fa-heart" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <a class="product-card-link" href="<?php echo get_permalink($wc_product->get_id()); ?>"><?php echo $wc_product->get_name(); ?></a>
                        <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                           href="/redirect.php?product=<?php echo esc_attr($wc_product->get_slug()); ?>"
                           class="ansehen-button">
                            <?php
                            $button_text = get_post_meta($wc_product->get_id(), 'button_text', true);
                            if (empty($button_text)) {
                                $button_text = $wc_product->add_to_cart_text();
                            }
                            echo esc_html($button_text);
                            ?>
                        </a>
                    </div>
                    <?php
                endforeach;
            else :
                echo '<p>Keine neuen Produkte gefunden.</p>';
            endif;
            ?>
        </div>
        <div class="show-more-container">
            <button class="show-more" id="showMoreNewProducts" data-product-type="new">
                MEHR ANZEIGEN
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <div class="personas-section">
            <h2>Geschenkefilter nach Typ</h2>
            <div class="personas-slider">
                <?php foreach ($personas as $index => $persona): ?>
                    <div class="persona-card" data-persona-id="<?php echo esc_attr($index); ?>">
                        <img src="<?php echo esc_url($persona['image'] ?? ''); ?>" alt="<?php echo esc_attr($persona['name'] ?? ''); ?>" class="persona-image">
                        <div class="persona-info">
                            <h3><?php echo esc_html($persona['name'] ?? ''); ?></h3>
                            <p><?php echo esc_html($persona['description'] ?? ''); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="gift-filter-wrapper">
            <div class="gift-box-icon">
                <i class="fas fa-gift"></i>
            </div>
            <div class="filter-text">
                <h3>Finde dein perfektes Geschenk</h3>
                <p>Nutze unsere Filteroptionen, um das ideale Geschenk zu entdecken</p>
            </div>
            <div class="filter-toggle">
                <i class="fas fa-sliders-h"></i>
            </div>
        </div>
        <div class="filter-section" id="filterSection" style="display: none;">
            <h2>Finde dein Geschenk</h2>
            <div class="filter-group">
                <h3>Altersempfehlung</h3>
                <div class="filter-buttons" id="ageFilters">
                    <?php
                    $age_terms = get_terms(['taxonomy' => 'pa_alter', 'hide_empty' => false]);
                    foreach ($age_terms as $term) {
                        echo '<button class="filter-button" data-filter="pa_alter" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            <div class="filter-group">
                <h3>Passende Beziehung zum Geschenk-Empfänger</h3>
                <div class="filter-buttons" id="relationshipFilters">
                    <?php
                    $relationship_terms = get_terms(['taxonomy' => 'pa_beziehung', 'hide_empty' => false]);
                    foreach ($relationship_terms as $term) {
                        echo '<button class="filter-button" data-filter="pa_beziehung" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            <div class="filter-group">
                <h3>Geschenk-Eigenschaften</h3>
                <div class="filter-buttons" id="propertyFilters">
                    <?php
                    $property_terms = get_terms(['taxonomy' => 'pa_eigenschaften', 'hide_empty' => false]);
                    foreach ($property_terms as $term) {
                        echo '<button class="filter-button" data-filter="pa_eigenschaften" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            <div class="filter-group">
                <h3>Passendes Geschlecht für das Geschenk</h3>
                <div class="filter-buttons" id="genderFilters">
                    <?php
                    $gender_terms = get_terms(['taxonomy' => 'pa_geschlecht', 'hide_empty' => false]);
                    foreach ($gender_terms as $term) {
                        echo '<button class="filter-button" data-filter="pa_geschlecht" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
            <div class="filter-group">
                <h3>Passende Lebensphase</h3>
                <div class="filter-buttons" id="lifephaseFilters">
                    <?php
                    $lifephase_terms = get_terms(['taxonomy' => 'pa_lebensphase', 'hide_empty' => false]);
                    foreach ($lifephase_terms as $term) {
                        echo '<button class="filter-button" data-filter="pa_lebensphase" data-value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</button>';
                    }
                    ?>
                </div>
            </div>
        </div>
        <h2>Gefilterte Geschenke</h2>
        <div id="filteredProducts" class="product-grid"></div>
        <div class="show-more-container">
            <button class="show-more" id="showMoreFiltered" style="display: none;" data-product-type="showMoreFiltered">
                MEHR ANZEIGEN
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        <h2>Aufsteiger des Tages</h2>
        <div class="product-grid" id="risingProducts">
        <?php if (empty($rising_products)): ?>
        <p class="no-products-message">Heute sticht noch kein Geschenk besonders hervor.</p>
        <?php else: ?>
        <?php foreach ($rising_products as $product):
        $wc_product = wc_get_product($product['id']);
        $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop ➜', 'woocommerce');
        ?>
        <div class="product-item">
        <div class="product-image-container">
            <a href="<?php echo esc_url(get_permalink($product['id'])); ?>">
                <?php echo get_the_post_thumbnail($product['id'], 'woocommerce_thumbnail', array('loading'=>'lazy')); ?>
            </a>
            <div class="awp-wishlist-button-container">
                <button class="awp-add-to-wishlist"
                    data-product-id="<?php echo esc_attr($product['id']); ?>"
                    data-product-name="<?php echo esc_attr($product['name']); ?>"
                    data-product-url="<?php echo esc_url(get_permalink($product['id'])); ?>"
                    aria-label="Zur Wunschliste hinzufügen">
                    <i class="fa fa-heart" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <a class="product-card-link" href="<?php echo esc_url(get_permalink($product['id'])); ?>"><?php echo esc_html($product['name']); ?></a>
        <p class="wachstum">Aufrufe: +<?php echo number_format($product['growth_percentage'], 2); ?>%</p>
        <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
           href="/redirect.php?product=<?php echo esc_attr($wc_product ? $wc_product->get_slug() : ''); ?>"
           class="ansehen-button">
            <?php echo esc_html($button_text); ?>
        </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <?php if (!empty($rising_products)): ?>
        <div class="show-more-container">
        <button class="show-more" id="showMoreRisingProducts" data-product-type="rising">
        MEHR ANZEIGEN
        <i class="fas fa-chevron-down"></i>
        </button>
        </div>
        <?php endif; ?>
        <h2>Geschenklys Klassiker</h2>
        <div class="product-grid" id="sixMonthsPopularProducts">
            <?php
            $six_months_popular_products = wcc_get_six_months_popular_products_cached($category_id, 4);
            foreach ($six_months_popular_products as $product):
                $wc_product = wc_get_product($product['id']);
                $button_text = $wc_product ? $wc_product->add_to_cart_text() : __('Zum Shop ➜', 'woocommerce');
            ?>
            <div class="product-item">
                <div class="product-image-container">
                    <a href="<?php echo esc_url(get_permalink($product['id'])); ?>">
                        <?php echo get_the_post_thumbnail($product['id'], 'woocommerce_thumbnail', array('loading'=>'lazy')); ?>
                    </a>
                    <div class="awp-wishlist-button-container">
                        <button class="awp-add-to-wishlist"
                            data-product-id="<?php echo esc_attr($product['id']); ?>"
                            data-product-name="<?php echo esc_attr($product['name']); ?>"
                            data-product-url="<?php echo esc_url(get_permalink($product['id'])); ?>"
                            aria-label="Zur Wunschliste hinzufügen">
                            <i class="fa fa-heart" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <a class="product-card-link" href="<?php echo esc_url(get_permalink($product['id'])); ?>"><?php echo esc_html($product['name']); ?></a>
                <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                   href="/redirect.php?product=<?php echo esc_attr($wc_product ? $wc_product->get_slug() : ''); ?>"
                   class="ansehen-button">
                    <?php echo esc_html($button_text); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <h2>Weitere Kategorien passend zu "<?php echo esc_html($category_name); ?>"</h2>
        <div class="subcategories-and-tags">
            <?php
            if (!empty($selected_subcategories) || !empty($selected_tags)) {
                foreach ($selected_subcategories as $subcategory_id) {
                    $subcategory = get_term($subcategory_id, 'product_cat');
                    if ($subcategory && !is_wp_error($subcategory)) {
                        $custom_text = isset($custom_subcategory_texts[$subcategory_id]) ? $custom_subcategory_texts[$subcategory_id] : $subcategory->description;
                        $subcat_image_id = get_term_meta($subcategory_id, 'wcc_image', true);
                        $image_url = $subcat_image_id ? wp_get_attachment_image_url($subcat_image_id, 'full') : '';
                        echo '<a class="category-tag-card" href="' . esc_url(get_term_link($subcategory)) . '">';
                        if ($image_url) {
                            echo '<div class="category-tag-image" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
                        } else {
                            echo '<div class="category-tag-image" style="background-image: url(\'' . esc_url('https://geschenkly.de/wp-content/uploads/2024/07/DALL·E-2024-07-26-16.28.35-Create-an-illustration-for-the-Gifts-for-Men-category-designed-to-fit-into-a-placeholder-space-with-a-friendly-and-inviting-vibe.-The-scene-should-.webp') . '\');"></div>';
                        }
                        echo '<div class="category-tag-content">';
                        echo '<h3>' . esc_html($subcategory->name) . '</h3>';
                        echo '<p>' . esc_html($custom_text) . '</p>';
                        echo '</div>';
                        echo '</a>';
                    }
                }
                foreach ($selected_tags as $tag_id) {
                    $tag = get_term($tag_id, 'product_tag');
                    if ($tag && !is_wp_error($tag)) {
                        $custom_text = isset($custom_tag_texts[$tag_id]) ? $custom_tag_texts[$tag_id] : $tag->description;
                        $tag_image_id = get_term_meta($tag_id, 'wcc_image', true);
                        $image_url = $tag_image_id ? wp_get_attachment_image_url($tag_image_id, 'full') : '';
                        echo '<a class="category-tag-card" href="' . esc_url(get_term_link($tag)) . '">';
                        if ($image_url) {
                            echo '<div class="category-tag-image" style="background-image: url(\'' . esc_url($image_url) . '\');"></div>';
                        } else {
                            echo '<div class="category-tag-image" style="background-image: url(\'' . esc_url('https://geschenkly.de/wp-content/uploads/2024/07/DALL·E-2024-07-26-16.28.35-Create-an-illustration-for-the-Gifts-for-Men-category-designed-to-fit-into-a-placeholder-space-with-a-friendly-and-inviting-vibe.-The-scene-should-.webp') . '\');"></div>';
                        }
                        echo '<div class="category-tag-content">';
                        echo '<h3>' . esc_html($tag->name) . '</h3>';
                        echo '<p>' . esc_html($custom_text) . '</p>';
                        echo '</div>';
                        echo '</a>';
                    }
                }
            } else {
                echo '<p>Keine Unterkategorien oder Tags ausgewählt.</p>';
            }
            ?>
        </div>
        <?php if (!empty($custom_description)): ?>
            <div class="custom-description-card">
                <h3>Wissenswertes zum Thema "<?php echo esc_html($category_name); ?>"</h3>
                <div class="custom-description-content">
                    <?php echo wpautop($custom_description); ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="filter-section category-insights">
			<h3>Unsere Partner-Shops für "<?php echo esc_html($category_name); ?>"</h3>
            <div class="filter-buttons partner-grid">
                <?php
                $selected_partner_shops = isset($term_meta['wcc_selected_partner_shops'][0]) ? maybe_unserialize($term_meta['wcc_selected_partner_shops'][0]) : array();
                $partner_shops = get_option('wcc_partner_shops', []);
                if (!empty($selected_partner_shops) && !empty($partner_shops)) {
                    foreach ($selected_partner_shops as $shop_index) {
                        if (isset($partner_shops[$shop_index])) {
                            $shop = $partner_shops[$shop_index];
                            $logo_url = wp_get_attachment_image_url($shop['logo'], 'medium');
                            if (!$logo_url) {
                                $logo_url = 'http://placehold.it/100x100';
                            }
                            ?>
                            <a rel="sponsored" href="<?php echo esc_url($shop['url']); ?>" class="filter-button partner-card" target="_blank">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($shop['name']); ?> Logo">
                                <span><?php echo esc_html($shop['name']); ?></span>
                            </a>
                            <?php
                        }
                    }
                } else {
                    echo '<p>Keine Partner-Shops für diese Kategorie ausgewählt.</p>';
                }
                ?>
            </div>
			<div id="awp-wishlist-app"></div>
        </div>
<script>
var popularProductsOffset = 12;
jQuery(document).ready(function($) {
    var categoryId = <?php echo json_encode($category_id); ?>;
    var categoryName = <?php echo json_encode($category_name); ?>;
    var allPopularProducts = <?php echo json_encode($all_popular_products); ?>;
    var risingProductsOffset = <?php echo count($rising_products); ?>;
    var sixMonthsPopularProductsOffset = <?php echo count(wcc_get_six_months_popular_products_cached($category_id, 4)); ?>;
    var newProductsOffset = 12;
    var filteredProductsOffset = 0;
    var activeFilters = {};
    var $filterSection = $('#filterSection');
    var $filterWrapper = $('.gift-filter-wrapper');
    var $filterToggleIcon = $('.filter-toggle i');
    var $filterTextHeading = $('.filter-text h3');

    function applyPersonaFilter(personaId) {
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_apply_persona_filter',
                persona_id: personaId,
                category_id: categoryId,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    activeFilters = response.data.filters;
                    filteredProductsOffset = 0;
                    applyFilters();
                    updateFilterButtonStates();
                    $('html, body').animate({
                        scrollTop: $("#filteredProducts").offset().top
                    }, 1000);
                } else {
                    $('#filteredProducts').html('<p>Fehler beim Anwenden des Filters.</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#filteredProducts').html('<p>Fehler beim Anwenden des Filters.</p>');
            }
        });
    }

    function updateFilterButtonStates() {
        $('.filter-button').each(function() {
            var filter = $(this).data('filter');
            var value = $(this).data('value');
            if (activeFilters[filter] && activeFilters[filter].includes(value)) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }

    function applyFilters() {
        var limit = 12;
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_filter_products',
                category_id: categoryId,
                filters: activeFilters,
                offset: filteredProductsOffset,
                limit: limit,
                nonce: wcc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    var productsHtml = '';
                    if (response.data.length > 0) {
                        response.data.forEach(function(product) {
                            productsHtml += `
                            <div class="product-item" data-product-id="${product.id}">
                                <div class="product-image-container">
                                    <a href="${product.url}">
                                        <img src="${product.image}" alt="${product.name}">
                                    </a>
                                    <div class="awp-wishlist-button-container">
                                        <button class="awp-add-to-wishlist"
                                            data-product-id="${product.id}"
                                            data-product-name="${product.name}"
                                            data-product-url="${product.url}"
                                            aria-label="Zur Wunschliste hinzufügen">
                                            <i class="fa fa-heart" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                                <a class="product-card-link" href="${product.url}">${product.name}</a>
                                <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                                   href="/redirect.php?product=${product.slug}"
                                   class="ansehen-button">
                                    ${product.button_text || 'Zum Shop ➜'}
                                </a>
                            </div>
                            `;
                        });
                        if (filteredProductsOffset === 0) {
                            $('#filteredProducts').html(productsHtml);
                        } else {
                            $('#filteredProducts').append(productsHtml);
                        }
                        filteredProductsOffset += response.data.length;
                        if (response.data.length < limit) {
                            $('#showMoreFiltered').hide();
                        } else {
                            $('#showMoreFiltered').show();
                        }
                    } else {
                        if (filteredProductsOffset === 0) {
                            $('#filteredProducts').html('<p>Keine Produkte gefunden.</p>');
                        }
                        $('#showMoreFiltered').hide();
                    }
                } else {
                    if (filteredProductsOffset === 0) {
                        $('#filteredProducts').html('<p>Fehler beim Laden der Produkte.</p>');
                    }
                    $('#showMoreFiltered').hide();
                }
                updateFilterButtonStates();
            },
            error: function(xhr, status, error) {
                if (filteredProductsOffset === 0) {
                    $('#filteredProducts').html('<p>Fehler beim Laden der Produkte.</p>');
                }
                $('#showMoreFiltered').hide();
            }
        });
    }

    function loadMoreProducts(productType) {
        var offset = 0;
        var action = '';
        switch (productType) {
            case 'popular':
                if (popularProductsOffset >= allPopularProducts.length) {
                    $('#showMorePopularProducts').hide();
                    return;
                }
                var productsToShow = allPopularProducts.slice(popularProductsOffset, popularProductsOffset + 12);
                appendProducts(productsToShow, '#popularProducts', true);
                popularProductsOffset += productsToShow.length;
                if (popularProductsOffset >= allPopularProducts.length) {
                    $('#showMorePopularProducts').hide();
                }
                break;
            case 'new':
                offset = newProductsOffset;
                action = 'wcc_load_more_new_products';
                break;
            case 'rising':
                offset = risingProductsOffset;
                action = 'wcc_load_more_rising_products';
                break;
            case 'sixMonthsPopular':
                offset = sixMonthsPopularProductsOffset;
                action = 'wcc_load_more_six_months_popular_products';
                break;
            case 'showMoreFiltered':
                applyFilters();
                break;
        }
        if (productType !== 'popular' && productType !== 'showMoreFiltered') {
            $.ajax({
                url: wcc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    category_id: categoryId,
                    offset: offset,
                    limit: 12,
                    nonce: wcc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $container = $('#' + productType + 'Products');
                        if (typeof response.data === 'string') {
                            $container.append(response.data);
                            var addedProducts = $(response.data).filter('.product-item').length;
                            updateOffset(productType, addedProducts);
                            if (addedProducts === 0) {
                                $('#showMore' + productType.charAt(0).toUpperCase() + productType.slice(1) + 'Products').hide();
                            }
                        } else if (Array.isArray(response.data) && response.data.length > 0) {
                            var productsHtml = response.data.map(generateProductHtml).join('');
                            $container.append(productsHtml);
                            updateOffset(productType, response.data.length);
                        } else {
                            $('#showMore' + productType.charAt(0).toUpperCase() + productType.slice(1) + 'Products').hide();
                        }
                    }
                }
            });
        }
    }

    function updateOffset(productType, addedCount) {
        switch (productType) {
            case 'popular':
                popularProductsOffset += addedCount;
                break;
            case 'new':
                newProductsOffset += addedCount;
                break;
            case 'rising':
                risingProductsOffset += addedCount;
                break;
            case 'sixMonthsPopular':
                sixMonthsPopularProductsOffset += addedCount;
                break;
        }
    }

    function appendProducts(products, containerId, highlight = false) {
        var productsHtml = products.map(product => generateProductHtml(product)).join('');
        $(containerId).append(productsHtml);
        if (highlight) {
            $(containerId).find('.product-item.new-product').each(function(index) {
                $(this).delay(100 * index).animate({backgroundColor: '#ffff99'}, 500).animate({backgroundColor: 'transparent'}, 500);
            });
        }
    }

    function generateProductHtml(product) {
        return `
            <div class="product-item" data-product-id="${product.id}">
                <div class="product-image-container">
                    <a href="${product.url}">
                        ${product.image}
                    </a>
                    <div class="awp-wishlist-button-container">
                        <button class="awp-add-to-wishlist"
                            data-product-id="${product.id}"
                            data-product-name="${product.name}"
                            data-product-url="${product.url}"
                            aria-label="Zur Wunschliste hinzufügen">
                            <i class="fa fa-heart" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <a class="product-card-link" href="${product.url}">${product.name}</a>
                <a rel="nofollow" target="_blank" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Teaser']);"
                   href="/redirect.php?product=${encodeURIComponent(product.slug)}"
                   class="ansehen-button">
                    ${product.button_text || 'Zum Shop ➜'}
                </a>
            </div>
        `;
    }

    function toggleFilterSection() {
        $filterSection.slideToggle(400, function() {
            if ($filterSection.is(':visible')) {
                $filterTextHeading.text('Filter ausblenden');
                $filterToggleIcon.removeClass('fa-sliders-h').addClass('fa-times');
                $filterWrapper.addClass('active');
            } else {
                $filterTextHeading.text('Finde dein perfektes Geschenk');
                $filterToggleIcon.removeClass('fa-times').addClass('fa-sliders-h');
                $filterWrapper.removeClass('active');
            }
        });
    }

    $('.show-more').on('click', function() {
        var productType = $(this).data('product-type');
        loadMoreProducts(productType);
    });

    $('.filter-button').on('click', function() {
        var filter = $(this).data('filter');
        var value = $(this).data('value');
        $(this).toggleClass('active');
        if (!activeFilters[filter]) {
            activeFilters[filter] = [];
        }
        var index = activeFilters[filter].indexOf(value);
        if (index > -1) {
            activeFilters[filter].splice(index, 1);
        } else {
            activeFilters[filter].push(value);
        }
        if (activeFilters[filter].length === 0) {
            delete activeFilters[filter];
        }
        filteredProductsOffset = 0;
        applyFilters();
    });

    $(document).on('click', '.persona-card', function() {
        var personaId = $(this).data('persona-id');
        applyPersonaFilter(personaId);
    });

    $filterWrapper.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleFilterSection();
    });

    $filterSection.on('click', function(e) {
        e.stopPropagation();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest($filterWrapper).length && !$(e.target).closest($filterSection).length) {
            if ($filterSection.is(':visible')) {
                toggleFilterSection();
            }
        }
    });

    $('.like-button, .dislike-button').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var productId = $button.data('product-id');
        var categoryId = $button.data('category-id');
        var feedbackType = $button.hasClass('like-button') ? 'like' : 'dislike';
        var productName = $button.closest('.product-item').find('h4').text().trim();
        $.ajax({
            url: wcc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wcc_record_product_feedback',
                nonce: wcc_ajax.nonce,
                product_id: productId,
                product_name: productName,
                category_id: categoryId,
                feedback_type: feedbackType
            },
            success: function(response) {
                if (response.success) {
                    $button.addClass('active');
                    $button.find('i').removeClass('far').addClass('fas');
                    var oppositeButton = $button.hasClass('like-button') ? $button.siblings('.dislike-button') : $button.siblings('.like-button');
                    oppositeButton.removeClass('active').prop('disabled', true);
                    oppositeButton.find('i').removeClass('fas').addClass('far');
                } else {
                    alert('Fehler: ' + response.data.message);
                }
            },
            error: function() {
                alert('Es gab einen Fehler bei der Verbindung zum Server.');
            }
        });
    });

    $('#category-title').text(categoryName || 'Alle Kategorien');
});
</script>
    </div>
</div>
