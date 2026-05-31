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
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product, $post; // $post für Ihre redirect.php benötigt

// Hole die Meta-Daten für die Produkt-URL (aus Ihrer alten Version beibehalten)
// NOTE: $product_url wird in dieser Datei nicht mehr benötigt, wenn Sie den <a>-Tag verwenden.
// Wir definieren ihn nur, um $button_text zu holen.
$product_url = $product->get_product_url();
$button_text = $product->single_add_to_cart_text();

// Ihre individuelle Logik (aus alter Version beibehalten)
// Überprüfe, ob der Buttontext "Amazon" oder "Ebay" enthält
$is_amazon = strpos( $button_text, 'Amazon' ) !== false;
$is_ebay = strpos( $button_text, 'Ebay' ) !== false;

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="cart" method="get">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
	
<p class="cart" data-url="<?php echo esc_attr( get_the_ID() ); ?>">
	<a href="<?php echo esc_url( "/redirect.php?product={$post->post_name}" ); ?>" 
		id="track-external-link-<?php echo esc_attr( $post->post_name ); ?>" 
		data-product-slug="<?php echo esc_attr( $post->post_name ); ?>"
		rel="nofollow noopener" 
		target="_blank" 
		class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
		<?php echo esc_html( $button_text ); ?>
	</a>
</p>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
</form>

<?php if ( $is_amazon || $is_ebay ) : ?>
	<p class="cart partner-notice">
		* als <?php echo $is_amazon ? 'Amazon' : 'Ebay'; ?>-Partner verdienen wir an qualifizierten Verkäufen
	</p>
<?php endif; ?>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>