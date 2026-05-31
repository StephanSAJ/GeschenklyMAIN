<?php
/**
 * Other actions (Compare, Wishlist)
 *
 * @author        WooThemes
 * @package       WooCommerce/Templates
 * @version       1.6.4
 */

$count_buttons = 2; //number of buttons to show

$wishlist = (  yit_get_option( 'shop-view-wishlist-button' ) == 'yes' && get_option( 'yith_wcwl_enabled' ) == 'yes' && shortcode_exists( 'yith_wcwl_add_to_wishlist' )  ) ? do_shortcode( '[yith_wcwl_add_to_wishlist use_button_style="no"]' ) : '';

$share = ( yit_get_option( 'shop-view-share-button' ) == 'yes' ) ? sprintf( '<div class="share-button"><a rel="nofollow" href="#" id="yit_share">' . __( '', 'yit' ) . '</a></div>' ) : '';

$buttons = array( $wishlist, $share );

foreach ( array( 'wishlist', 'share' ) as $var ) {
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

    echo '<div class="share-container"><p>' . __( 'share on: ', 'yit' ) . '</p>';
    yit_get_social_share( 'text' );
    echo '</div>';

endif;
?>


