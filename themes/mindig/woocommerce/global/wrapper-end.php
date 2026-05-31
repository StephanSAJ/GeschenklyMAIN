<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/wrapper-end.php.
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
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$template = wc_get_theme_slug_for_templates();

switch ( $template ) {
	case 'twentyten' :
		echo '</div></div>';
		break;
	case 'twentyeleven' :
		echo '</div>';
		get_sidebar( 'shop' );
		echo '</div>';
		break;
	case 'twentytwelve' :
		echo '</div></div>';
		break;
	case 'twentythirteen' :
		echo '</div></div>';
		break;
	case 'twentyfourteen' :
		echo '</div></div></div>';
		get_sidebar( 'content' );
		break;
	case 'twentyfifteen' :
		echo '</div></div>';
		break;
	case 'twentysixteen' :
		echo '</main></div>';
		break;
	default :

		$mobile = YIT_Mobile()->isMobile();
		$sidebar = yit_get_sidebars();

		echo '</div>';
		if ( is_product() && ( ! isset( $sidebar['layout'] ) || $sidebar['layout'] == 'sidebar-no' ) && yit_get_option( 'shop-single-layout-page' ) == 'creative' && ! $mobile ) {
			echo '<div class="sidebar clearfix col-sm-3" role="secondary">';
			do_action( 'yit_product_single_boxmeta' );
			echo '</div>';
		}
		yit_primary_sidebar_two();
		yit_primary_sidebar();
		yit_end_primary();
		break;
}