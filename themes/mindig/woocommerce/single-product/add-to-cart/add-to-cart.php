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
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $product;

if ( yit_get_option( 'shop-add-to-cart-button' ) == 'no' && yit_get_option( 'shop-view-details-button' ) == 'no' ) {
    return;
}

$is_wishlist = function_exists( 'yith_wcwl_is_wishlist' ) && yith_wcwl_is_wishlist();

?>

<div class="product-buttons">

    <?php
    if ( yit_get_option( 'shop-add-to-cart-button' ) == 'yes' && yit_get_option( 'shop-enable' ) == 'yes' ) {
        if ( ! $product->is_in_stock() ) : ?>
            <a href="<?php echo esc_url( apply_filters( 'out_of_stock_add_to_cart_url', get_permalink( $product->get_id() ) ) ); ?>" class="out-of-stock btn btn-flat"><?php echo esc_html( apply_filters( 'out_of_stock_add_to_cart_text', __( 'Out Of Stock', 'yit' ) ) ); ?></a>
        <?php else :
            $link = array(
                'url'      => $product->add_to_cart_url(),
                'label'    => $product->add_to_cart_text(),
                'class'    => isset( $class ) ? $class : 'button',
                'quantity' => isset( $quantity ) ? $quantity : 1
            );

            $product_type = $product->get_type();
            $handler = apply_filters( 'woocommerce_add_to_cart_handler', $product_type, $product );

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
                case "external" :
                    $link['url']   = esc_url( $product->add_to_cart_url() );
                    $link['label'] = apply_filters( 'external_add_to_cart_text', __( 'ZUM SHOP', 'yit' ) );
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

            echo apply_filters( 'woocommerce_loop_add_to_cart_link',
                sprintf( '<a rel="nofollow" target="_blank" onclick="_paq.push([\'trackEvent\', \'Conversion\', \'Klick zum Shop\', \'Teaser\']);" href="/redirect.php?product=%s" class="btn btn-flat %s">%s</a>',
                    esc_attr( $product->get_slug() ),
                    esc_attr( $link['class'] ),
                    esc_html( $product->add_to_cart_text() )
                ),
                $product, $args );
        endif;
    }

    if ( ! $is_wishlist && ( ! isset( $hide_quick_view ) ) ) {
        if ( yit_get_option( 'shop-quick-view-enable' ) == 'yes' ) {
            if( function_exists( 'YITH_WCQV_Frontend' ) && shortcode_exists('yith_quick_view') ) {
                echo do_shortcode('[yith_quick_view product_id="' . $product->get_id() . '"]' );
            } elseif( ( YIT_Mobile()->isMobile() && YIT_Mobile()->is( 'iPad' ) ) || ! YIT_Mobile()->isMobile() ) {
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
