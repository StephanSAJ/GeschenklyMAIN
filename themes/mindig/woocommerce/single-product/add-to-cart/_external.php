<?php
/**
 * External product add to cart
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $post;
?>
<?php $meta_values = get_post_meta( get_the_ID(), '_product_url', true ); ?>
<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
    <p class="cart" data-url="<?php echo get_the_ID(); ?>">
        <a href="/redirect.php?product=<?php echo $post->post_name; ?>" onclick="ga('send', 'event', { eventCategory: 'Produktseite', eventAction: 'Klick'});" rel="nofollow" rel="noopener" class="single_add_to_cart_button button alt"><?php echo $button_text; ?></a>
	</p>
<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>