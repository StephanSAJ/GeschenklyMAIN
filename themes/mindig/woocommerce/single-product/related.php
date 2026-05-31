<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( $related_products ) :

    // Ensure all images of related products are lazy loaded correctly.
    if ( function_exists( 'wp_increase_content_media_count' ) ) {
        $content_media_count = wp_increase_content_media_count( 0 );
        if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
            wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
        }
    }

    global $product;

    // Custom: Determine tag count (from your Mindig version)
    $tag_count = count( wp_get_post_tags( $product->get_id() ) );

    ?>

    <section class="related products">

        <?php
        // Custom heading
        $heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );

        if ( $heading ) : ?>
<p class="clearfix related products" style="font-size:18px;">
    <?php _e( 'Geschenkideen die dir gefallen werden', 'yit' ); ?>
</p>
        <?php endif; ?>

        <?php woocommerce_product_loop_start(); ?>

        <?php foreach ( $related_products as $related_product ) : ?>

            <?php
            $post_object = get_post( $related_product->get_id() );
            setup_postdata( $GLOBALS['post'] = $post_object );
            wc_get_template_part( 'content', 'product' );
            ?>

        <?php endforeach; ?>

        <?php woocommerce_product_loop_end(); ?>

        <?php // Custom: Output product tags similar to old Mindig template ?>
        <?php echo $product->get_tags( ', ', '<span class="tagged_as">' . _n( 'Tag:', 'Tags:', $tag_count, 'woocommerce' ) . ' ', '</span>' ); ?>

    </section>

    <?php
endif;

wp_reset_postdata();
