<?php
/**
 * Loop Add to Cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/add-to-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     9.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

// Guard: wenn beide Buttons deaktiviert sind, nichts anzeigen
if ( yit_get_option( 'shop-add-to-cart-button' ) == 'no' && yit_get_option( 'shop-view-details-button' ) == 'no' ) {
    return;
}

$is_wishlist = function_exists( 'yith_wcwl_is_wishlist' ) && yith_wcwl_is_wishlist();

$args = isset( $args ) ? $args : array();

// aria-describedby (wie in WC 9.2.0)
$aria_describedby = isset( $args['aria-describedby_text'] ) ? sprintf( 'aria-describedby="woocommerce_loop_add_to_cart_link_describedby_%s"', esc_attr( $product->get_id() ) ) : '';

?>
<div class="product-buttons">

    <?php
    // ADD TO CART (Mindig-Logik)
    if ( yit_get_option( 'shop-add-to-cart-button' ) == 'yes' && yit_get_option( 'shop-enable' ) == 'yes' ) {

        if ( ! $product->is_in_stock() ) : ?>
            <a href="<?php echo esc_url( apply_filters( 'out_of_stock_add_to_cart_url', get_permalink( $product->get_id() ) ) ); ?>" class="out-of-stock btn btn-flat"><?php echo esc_html( apply_filters( 'out_of_stock_add_to_cart_text', __( 'Out Of Stock', 'yit' ) ) ); ?></a>
        <?php else :

            // Basis-Link-Daten (wir nutzen weiterhin Redirect mit Product-Slug)
            $link = array(
                'url'      => $product->add_to_cart_url(),
                'label'    => $product->add_to_cart_text(),
                'class'    => isset( $args['class'] ) ? $args['class'] : ( isset( $class ) ? $class : 'button' ),
                'quantity' => isset( $args['quantity'] ) ? $args['quantity'] : ( isset( $quantity ) ? $quantity : 1 ),
            );

            $product_type = $product->get_type();
            $handler = apply_filters( 'woocommerce_add_to_cart_handler', $product_type, $product );

            // Anpassungen je Produkttyp (wie in deinem alten Template)
            switch ( $handler ) {
                case "variable" :
                    $link['url']   = apply_filters( 'variable_add_to_cart_url', $link['url'] );
                    $link['label'] = apply_filters( 'variable_add_to_cart_text', $link['label'] );
                    $link['class'] = apply_filters( 'add_to_cart_class', $link['class'] );
                    break;
                case "grouped" :
                    $link['url']   = apply_filters( 'grouped_add_to_cart_url', $link['url'] );
                    $link['label'] = apply_filters( 'grouped_add_to_cart_text', $link['label'] );
                    break;
case "external":
    $link['url']   = esc_url( $product->add_to_cart_url() );

    // Nimmt den in WooCommerce gespeicherten Button-Text
    $custom_button_text = $product->get_button_text();

    // Fallback, falls leer:
    if ( empty( $custom_button_text ) ) {
        $custom_button_text = $product->add_to_cart_text();
    }

    $link['label'] = esc_html( $custom_button_text );
    break;
                default :
                    if ( $product->is_purchasable() ) {
                        $link['url']      = apply_filters( 'add_to_cart_url', $link['url'] );
                        $link['label']    = apply_filters( 'add_to_cart_text', $link['label'] );
                        $link['class']    = apply_filters( 'add_to_cart_class', $link['class'] );
                        $link['quantity'] = apply_filters( 'add_to_cart_quantity', $link['quantity'] );
                    } else {
                        $link['url']   = apply_filters( 'not_purchasable_url', $link['url'] );
                        $link['label'] = apply_filters( 'not_purchasable_text', $link['label'] );
                    }
                    break;
            }

            // Wir erzeugen die Mindig-Weiterleitungs-URL (immer Redirect mit Produkt-Slug)
            $redirect_url = sprintf(
                '%s?product=%s',
                esc_url_raw( '/redirect.php' ),
                esc_attr( $product->get_slug() )
            );

            // Zusammenbau der Attribute (entsprechend neuer WC-Args supporten)
            $attributes_str = isset( $args['attributes'] ) ? wc_implode_html_attributes( $args['attributes'] ) : '';

            $quantity_attr = esc_attr( isset( $args['quantity'] ) ? $args['quantity'] : 1 );
            $class_attr    = esc_attr( $link['class'] );
            $label_escaped = esc_html( $link['label'] );

            // Finale Link-HTML (mit Tracking onclick und rel/nofollow)
            $link_html = sprintf(
                '<a href="%s" rel="nofollow" target="_blank" %s data-quantity="%s" class="btn btn-flat %s" %s onclick="_paq.push([\'trackEvent\', \'Conversion\', \'Klick zum Shop\', \'Teaser\']);">%s</a>',
                esc_url( $redirect_url ),
                $aria_describedby,
                $quantity_attr,
                $class_attr,
                $attributes_str,
                $label_escaped
            );

            /**
             * Filterbar: wie WooCommerce es erwartet — ermöglicht Plugins/Child-Themes, den Link zu verändern.
             * Wir geben $product und $args mit.
             */
            echo apply_filters( 'woocommerce_loop_add_to_cart_link', $link_html, $product, $args );

        endif;
    }

    // QUICK VIEW / VIEW DETAILS / WISHLIST
    if ( ! $is_wishlist && ( ! isset( $hide_quick_view ) ) ) {
        if ( yit_get_option( 'shop-quick-view-enable' ) == 'yes' ) {
            if ( function_exists( 'YITH_WCQV_Frontend' ) && shortcode_exists( 'yith_quick_view' ) ) {
                echo do_shortcode( '[yith_quick_view product_id="' . $product->get_id() . '"]' );
            } elseif ( ( YIT_Mobile()->isMobile() && YIT_Mobile()->is( 'iPad' ) ) || ! YIT_Mobile()->isMobile() ) {
                $text     = apply_filters( 'quick_view_text', __( 'Quick View', 'yit' ) );
                $sc_index = function_exists( 'YIT_Shortcodes' ) && YIT_Shortcodes()->is_inside ? '-' . YIT_Shortcodes()->index() : '';
                echo '<a id="quick-view-trigger-' . esc_attr( $product->get_id() ) . $sc_index . '" href="#" class="trigger-quick-view btn btn-alternative details" data-item_id="' . $product->get_id() . '">' . esc_html( $text ) . '</a>';
            }
        } else if ( yit_get_option( 'shop-view-details-button' ) == 'yes' ) {
            $text = apply_filters( 'view_details_text', __( 'View Details', 'yit' ) );
            echo '<a href="' . esc_url( get_permalink( $product->get_id() ) ) . '" rel="nofollow" title="' . esc_attr( $text ) . '" class="btn btn-alternative details">' . esc_html( $text ) . '</a>';
        }
    }
    ?>
</div>

<?php if ( strpos( $product->add_to_cart_text(), 'Amazon' ) !== false ) : ?>
    <div style="font-size: 8px;">* als Amazon-Partner verdienen wir an qualifizierten Verkäufen</div>
<?php endif; ?>

<?php if ( strpos( $product->add_to_cart_text(), 'Ebay' ) !== false ) : ?>
    <div style="font-size: 8px;">* als Ebay-Partner verdienen wir an qualifizierten Verkäufen</div>
<?php endif; ?>

<?php if ( isset( $args['aria-describedby_text'] ) ) : ?>
    <span id="woocommerce_loop_add_to_cart_link_describedby_<?php echo esc_attr( $product->get_id() ); ?>" class="screen-reader-text">
        <?php echo esc_html( $args['aria-describedby_text'] ); ?>
    </span>
<?php endif; ?>
