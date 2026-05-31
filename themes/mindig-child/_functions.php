<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Theme's functions.php file.
 * This file bootstrap the entire framework.
 * @package Yithemes
 */

/*
 * WARNING: This file is part of the Your Inspiration Themes framework core.
 * Edit this section at your own risk.
 */

//let's start the game!
require_once('core/yit.php');

add_action( 'send_headers', 'rsssl_add_hsts_header' );
function rsssl_add_hsts_header() {
    header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains; preload' );
}

// prevent the category widget from using the category description as the list item title attribute
function sjc_disable_cat_desc_widget_list_titles ( $cat_args ) {
    $cat_args[ 'use_desc_for_title' ] = 0;
    return $cat_args;
}
add_filter( 'widget_categories_args', 'sjc_disable_cat_desc_widget_list_titles' );

function yit_remove_num_products() {
    remove_action( 'shop-page-meta', 'yit_wc_num_of_products', 10 );
}
add_action( 'after_setup_theme', 'yit_remove_num_products', 11 );

function yit_remove_wc_catalog_ordering() {
    remove_action( 'shop-page-meta', 'yit_wc_catalog_ordering', 10 );
}
add_action( 'after_setup_theme', 'yit_remove_wc_catalog_ordering', 11 );

add_filter( 'loop_shop_columns', 'wc_loop_shop_columns', 1, 10 );
 
// Remove WP embed script
function speed_stop_loading_wp_embed() {
if (!is_admin()) {
wp_deregister_script('wp-embed');
}
}
add_action('init', 'speed_stop_loading_wp_embed');

function block_wp_embed() {
    wp_deregister_script('wp-embed'); }
add_action('init', 'block_wp_embed');

// die AMP Endung ?ndern, um sie bei FastestCache auszuschliessen
add_filter( 'amp_query_var' , 'xyz_amp_change_endpoint' );

function xyz_amp_change_endpoint( $amp_endpoint ) {
    return 'geschenklyamp';
}

// Stop Heartbeat
add_action('init', 'stop_heartbeat', 1);
function stop_heartbeat()
	{
	global $pagenow;
	if ($pagenow != 'post.php' && $pagenow != 'post-new.php') wp_deregister_script('heartbeat');
	}

//* Remove WP emoji code
function remove_emoji()
	{
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_filter('the_content_feed', 'wp_staticize_emoji');
	remove_filter('comment_text_rss', 'wp_staticize_emoji');
	remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	add_filter('tiny_mce_plugins', 'remove_tinymce_emoji');
	}
add_action('init', 'remove_emoji');
function remove_tinymce_emoji($plugins)
	{
	if (!is_array($plugins))
		{
		return array();
		}
	return array_diff($plugins, array(
		'wpemoji'
	));
	}

/**
 * Disable responsive image support
 */
// Clean the up the image from wp_get_attachment_image()
add_filter( 'wp_get_attachment_image_attributes', function( $attr )
{
    if( isset( $attr['sizes'] ) )
        unset( $attr['sizes'] );
    if( isset( $attr['srcset'] ) )
        unset( $attr['srcset'] );
    return $attr;
 }, PHP_INT_MAX );
// Override the calculated image sizes
add_filter( 'wp_calculate_image_sizes', '__return_false',  PHP_INT_MAX );
// Override the calculated image sources
add_filter( 'wp_calculate_image_srcset', '__return_false', PHP_INT_MAX );
// Remove the reponsive stuff from the content
remove_filter( 'the_content', 'wp_make_content_images_responsive' );

add_filter( 'script_loader_tag', 'wsds_defer_scripts', 10, 3 );
function wsds_defer_scripts( $tag, $handle, $src ) {

	// The handles of the enqueued scripts we want to defer
	$defer_scripts = array(
		'script_fingerprintjs',
		'jquery',
		'scrolldepth',
		'wwcAmzAff-frontend',
		'wpshout-js-cookie-demo',
		'thickbox',
		'admin-bar',
		'bhittani_plugin_kksr_js',
		'woocommerce',
		'wc-cart-fragments',
		'prettyPhoto',
		'prettyPhoto-init',
		'jquery-selectBox',
		'jquery-yith-wcwl',
		'bootstrap-twitter',
		'yit-internal',
		'jquery-commonlibraries',
		'shortcodes',
		'owl-carousel',
		'jquery-placeholder',
		'yit-common',
		'yit_woocommerce',
		'yit_woocommerce_2_3',
		'masterslider-script',
	);

    if ( in_array( $handle, $defer_scripts ) ) {
        return '<script src="' . $src . '" defer="defer" type="text/javascript"></script>' . "\n";
    }

    return $tag;
}

/**
 * Making short link for our Ajax url
 */
add_action( 'wp_enqueue_scripts', function(){

    wp_enqueue_script( 'rating_js', get_stylesheet_directory_uri() . '/core/assets/js/frontend/rating.js', array( 'jquery' ), '1.0' );
    wp_localize_script( 'rating_js', 'rjs', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        )
    );
});


add_action('wp_ajax_update_rating'       , 'update_rating_callback');
add_action('wp_ajax_nopriv_update_rating', 'update_rating_callback');

function update_rating_callback(){

    if (!empty($_POST['rate'])) {

        global $wpdb;
        $rate = $_POST['rate'];
        $post_id = $_POST['post_id'];
        $wpdb->query("UPDATE n0b9xntw9aposts SET rating = rating+'$rate' WHERE ID = '$post_id'");

//        $wpdb->update(
//            'n0b9xntw9aamz_products',
//            array(
//                'rating' => 'rating' + $rate
//            ),
//            array(
//                'post_id' => $post_id
//            ),
//            $format = null, $where_format = null );

        unset ($_POST['rate']);

        $return = "All data wrote correctly!";
        wp_send_json_success($return);
    }
    else alert("Fail :(");
}

// Change "Default Sorting" to "Crepla sorting" on shop page and in WC Product Settings
function adding_sorting_name( $catalog_orderby ) {
    $catalog_new = array('click-rate' => 'User click rate (Crepla) sorting');
    $catalog_orderby = array_merge($catalog_orderby,  $catalog_new);
    return $catalog_orderby;
}
add_filter( 'woocommerce_catalog_orderby', 'adding_sorting_name' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'adding_sorting_name' );

//Add Alphabetical sorting option to shop page / WC Product Settings
function crepla_click_rate_woocommerce_shop_ordering( $sort_args ) {



    $orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );

    if ( 'click-rate' == $orderby_value ) {

        // Sorting handled later though a hook
        add_filter( 'posts_clauses', 'order_by_click_rate_post_clauses' );

//        global $wpdb;
//        $sort_args['orderby'] = $wpdb->get_results( "SELECT * FROM $wpdb->prefix.posts ORDER BY rating");
//        $sort_args['order'] = 'asc';
//        $sort_args['meta_key'] = '';
    }
//
//	if ( $_SERVER['REMOTE_ADDR'] == '77.120.95.66' )
//	{
//		mail('taras.sych@gmail.com', 'orderby', var_export($sort_args, true));
//	}

    return $sort_args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'crepla_click_rate_woocommerce_shop_ordering' );

/**
 * WP Core doesn't let us change the sort direction for invidual orderby params - https://core.trac.wordpress.org/ticket/17065.
 *
 * This lets us sort by meta value desc, and have a second orderby param.
 *
 * @access public
 * @param array $args
 * @return array
 */
function order_by_click_rate_post_clauses( $args ) {

    global $wpdb;
//    $args['orderby'] = "$wpdb->posts.rating + 0 DESC";
	$args['orderby'] = "$wpdb->posts.rating + 0 DESC, $wpdb->posts.post_date DESC";

//	if ( $_SERVER['REMOTE_ADDR'] == '77.120.95.66' )
//	{
//		$args['orderby'] = "$wpdb->posts.rating + 0 DESC, $wpdb->posts.post_date DESC";
////		mail('taras.sych@gmail.com', 'orderb444', var_export($args, true));
//	}

    return $args;
}
//User click rating by Crepla ends

// decreasing rating of product by 5% every Thursday
add_action( 'decrease_rating_five_percent', 'decrease_user_click_rating_five_percent' );
function decrease_user_click_rating_five_percent(){
    global $wpdb;
    $ratings[] = $wpdb->get_results("SELECT ID, rating FROM n0b9xntw9aposts WHERE rating > 1"); //forming $wpdb object
    foreach ($ratings as  $rating) {
        foreach($rating as $v){
            $container = get_object_vars($v); //we can't refer to $v as it is an object of $wpdb
            $percentage = round(($container['rating'] / 100 * 20), 1);
            $wpdb->query("UPDATE n0b9xntw9aposts SET rating = rating - ".$percentage." WHERE ID = ".$container['ID']);
        }
    }
}

// decreasing rating of product by 0.5 every Thursday
//add_action( 'decrease_rating', 'decrease_user_click_rating' );
//
//function decrease_user_click_rating() {
//
//    global $wpdb;
//    $wpdb->query("UPDATE n0b9xntw9aposts SET rating = rating - 0.5 WHERE rating > 1");
//}

// Make sure this event hasn't been scheduled
if( !wp_next_scheduled( 'decrease_rating' ) ) {
    // Schedule the event
    wp_schedule_event( time(), 'weekly', 'decrease_rating' );
    }
//Our weekly cron task ends

add_action( 'wp_enqueue_scripts', 'mindig_child_wp_enqueue_scripts' );

function mindig_child_wp_enqueue_scripts() {
	if ( is_page( 'anna-deine-geschenk-beraterin' ) ) {
		$url = str_replace( array( 'http:', 'https:' ), '', get_stylesheet_directory_uri() );
		wp_enqueue_style( 'mindig-child-main', "https://geschenkly.de/wp-content/themes/mindig/theme/assets/css/mindig-child-main.css" );
		wp_enqueue_script( 'mindig-child-main', "https://geschenkly.de/wp-content/themes/mindig/theme/assets/js/mindig-child-main.js", array( 'jquery' ), false, true );
	}
}