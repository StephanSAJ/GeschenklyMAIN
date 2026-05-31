<?php
$count_buttons = 1;
// Stephan Share Stellt sicher, dass $share definiert ist
$share = isset($share) ? $share : '';
//Ende
$buttons = array( $share );

foreach ( array( 'share' ) as $var ) {
    if ( empty( ${$var} ) ) {
        $count_buttons --;
    }
}

if( $count_buttons > 0 ) : ?>

    <div class="clearfix product-other-action buttons_<?php echo $count_buttons ?>" >
        <?php echo implode( "\n", $buttons ) ?>
    </div>

<?php endif;

if ( yit_get_option( 'shop-view-share-button' ) == 'yes' ) :

    echo '<div class="share-container">';
    yit_get_social_share( 'text' );
    echo '</div>';

endif; ?>

<?php
/**
 * Single Product Meta
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/meta.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
?>
<div class="product_meta">

	<?php do_action( 'woocommerce_product_meta_start' ); ?>

	<?php echo wc_get_product_category_list( $product->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $product->get_category_ids() ), 'woocommerce' ) . ' ', '</span>' ); ?>

	<?php do_action( 'woocommerce_product_meta_end' ); ?>

</div>
