<?php
/**
 * External product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/external.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $post;
?>
<?php $meta_values = get_post_meta( get_the_ID(), '_product_url', true ); ?>
<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>
    <form class="cart" data-url="<?php echo get_the_ID(); ?>">
        <a href="/redirect.php?product=<?php echo $post->post_name; ?>" onclick="_paq.push(['trackEvent', 'Conversion', 'Klick zum Shop', 'Produktseite']);" rel="nofollow" target="_blank" rel="noopener" class="single_add_to_cart_button button alt"><?php echo $button_text; ?></a>
	</form>
  <?php if(strpos($button_text,'Amazon') !== false){ ?>
   <form class="cart" style="font-size: 9px; margin: 0px 0px 0px 0px;">* als Amazon-Partner verdienen wir an qualifizierten Verkäufen</form>
<?php } ?>
  <?php if(strpos($button_text,'Ebay') !== false){ ?>
   <form class="cart" style="font-size: 9px; margin: 0px 0px 0px 0px;">* als Ebay-Partner verdienen wir an qualifizierten Verkäufen</form>
<?php } ?>
<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>