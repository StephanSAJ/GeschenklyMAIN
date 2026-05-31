<?php
/**
 * Plugin Name: Geschenkly Analytics Plugin
 * Description: Erfasst Klick-/Interaktionsdaten und stellt sie als WordPress-eigene Analytics per REST-API bereit – ohne externen Server.
 * Version: 2.0
 * Author: Geschenkly
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'GESCHENKLY_ANALYTICS_VERSION', '2.0.0' );
define( 'GESCHENKLY_ANALYTICS_DB_VERSION', '1' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-geschenkly-analytics-rest.php';

class Geschenkly_Analytics_Plugin {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_update_rating', array( $this, 'update_rating_callback' ) );
		add_action( 'wp_ajax_nopriv_update_rating', array( $this, 'update_rating_callback' ) );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'add_data_attributes' ), 10 );
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade_db' ) );
		add_action( 'geschenkly_rollup_popularity', array( $this, 'rollup_popularity' ) );
		add_action(
			'rest_api_init',
			function () {
				$controller = new Geschenkly_Analytics_REST();
				$controller->register_routes();
			}
		);
	}

	/**
	 * Name der Event-Tabelle (mit Tabellen-Präfix).
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'geschenkly_events';
	}

	/**
	 * Legt die Event-Tabelle an bzw. aktualisiert sie.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(20) NOT NULL DEFAULT 'click',
			category_id BIGINT UNSIGNED NULL,
			category_name VARCHAR(191) NULL,
			tag_id BIGINT UNSIGNED NULL,
			tag_name VARCHAR(191) NULL,
			meta TEXT NULL,
			session_hash CHAR(32) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY product_created (product_id, created_at),
			KEY type_created (event_type, created_at),
			KEY category_id (category_id),
			KEY tag_id (tag_id)
		) {$charset_collate};";

		dbDelta( $sql );
		update_option( 'geschenkly_analytics_db_version', GESCHENKLY_ANALYTICS_DB_VERSION );

		// Stuendliches Popularitaets-Rollup einplanen (ersetzt den teuren Theme-Decay-Loop).
		if ( ! wp_next_scheduled( 'geschenkly_rollup_popularity' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'geschenkly_rollup_popularity' );
		}
	}

	/**
	 * Bei Deaktivierung den Cron wieder entfernen.
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'geschenkly_rollup_popularity' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'geschenkly_rollup_popularity' );
		}
	}

	/**
	 * Pflegt einen zentralen, indizierbaren Popularitaets-Score je Produkt
	 * (_geschenkly_pop_score) aus der Event-Tabelle. Ersetzt die per-Kategorie-
	 * Meta-Explosion als Sortier-Grundlage.
	 */
	public function rollup_popularity() {
		global $wpdb;

		// Einmaliges Seeding: bestehende _homepage_rating-Werte als Startwert uebernehmen,
		// damit die bisherige Reihenfolge erhalten bleibt, bevor genug Events vorliegen.
		if ( ! get_option( 'geschenkly_pop_seeded' ) ) {
			$wpdb->query(
				"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
				 SELECT pm.post_id, '_geschenkly_pop_score', pm.meta_value
				 FROM {$wpdb->postmeta} pm
				 LEFT JOIN {$wpdb->postmeta} ex
				   ON ex.post_id = pm.post_id AND ex.meta_key = '_geschenkly_pop_score'
				 WHERE pm.meta_key = '_homepage_rating' AND ex.meta_id IS NULL"
			);
			update_option( 'geschenkly_pop_seeded', 1 );
		}

		// Zeitgewichteter Klick-Score je Produkt (letzte 30 Tage, juengere Klicks staerker).
		$table = self::table_name();
		$rows  = $wpdb->get_results(
			"SELECT product_id,
			        SUM(CASE WHEN created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY) THEN 3 ELSE 1 END) AS score
			 FROM {$table}
			 WHERE event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)
			 GROUP BY product_id"
		);

		foreach ( $rows as $row ) {
			update_post_meta( (int) $row->product_id, '_geschenkly_pop_score', (float) $row->score );
		}
	}

	/**
	 * Stellt sicher, dass das DB-Schema aktuell ist (z. B. nach Plugin-Update ohne Reaktivierung).
	 */
	public function maybe_upgrade_db() {
		if ( get_option( 'geschenkly_analytics_db_version' ) !== GESCHENKLY_ANALYTICS_DB_VERSION ) {
			self::install();
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'rating_js', plugin_dir_url( __FILE__ ) . 'js/rating.js', array( 'jquery' ), GESCHENKLY_ANALYTICS_VERSION, true );
		wp_enqueue_script( 'rating_new_design_js', plugin_dir_url( __FILE__ ) . 'js/ratingNewDesign.js', array( 'jquery' ), GESCHENKLY_ANALYTICS_VERSION, true );

		$data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'geschenkly_event' ),
		);
		wp_localize_script( 'rating_js', 'rjs', $data );
		wp_localize_script( 'rating_new_design_js', 'rjs', $data );
	}

	/**
	 * Erfasst ein Event in der lokalen Tabelle.
	 */
	public static function record_event( $args ) {
		global $wpdb;

		$defaults = array(
			'product_id'    => 0,
			'event_type'    => 'click',
			'category_id'   => null,
			'category_name' => null,
			'tag_id'        => null,
			'tag_name'      => null,
			'meta'          => null,
			'session_hash'  => null,
		);
		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['product_id'] ) ) {
			return false;
		}

		return $wpdb->insert(
			self::table_name(),
			array(
				'product_id'    => intval( $args['product_id'] ),
				'event_type'    => sanitize_key( $args['event_type'] ),
				'category_id'   => null !== $args['category_id'] ? intval( $args['category_id'] ) : null,
				'category_name' => null !== $args['category_name'] ? sanitize_text_field( $args['category_name'] ) : null,
				'tag_id'        => null !== $args['tag_id'] ? intval( $args['tag_id'] ) : null,
				'tag_name'      => null !== $args['tag_name'] ? sanitize_text_field( $args['tag_name'] ) : null,
				'meta'          => null !== $args['meta'] ? sanitize_textarea_field( $args['meta'] ) : null,
				'session_hash'  => $args['session_hash'],
				'created_at'    => current_time( 'mysql', true ), // GMT für konsistente Zeitfenster.
			)
		);
	}

	/**
	 * AJAX-Callback: erfasst einen Klick lokal (statt ihn an eine externe API zu senden).
	 */
	public function update_rating_callback() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'geschenkly_event' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		if ( empty( $_POST['post_id'] ) || empty( $_POST['post_title'] ) ) {
			wp_send_json_error( 'Missing post_id or post_title' );
			return;
		}

		$post_id     = intval( $_POST['post_id'] );
		$category_id = ( isset( $_POST['category_id'] ) && '' !== $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : null;
		$tag_id      = ( isset( $_POST['tag_id'] ) && '' !== $_POST['tag_id'] ) ? intval( $_POST['tag_id'] ) : null;

		self::record_event(
			array(
				'product_id'    => $post_id,
				'event_type'    => 'click',
				'category_id'   => $category_id,
				'category_name' => self::term_name( $category_id ),
				'tag_id'        => $tag_id,
				'tag_name'      => self::term_name( $tag_id ),
				'session_hash'  => self::client_session_hash(),
			)
		);

		wp_send_json_success( 'Event recorded' );
	}

	/**
	 * Liefert den Namen eines Terms oder null.
	 */
	private static function term_name( $term_id ) {
		if ( ! $term_id ) {
			return null;
		}
		$term = get_term( $term_id );
		return ( $term && ! is_wp_error( $term ) ) ? $term->name : null;
	}

	/**
	 * Pseudonymer Session-Hash (IP + User-Agent + Salt) für Dedupe/Rate-Limiting.
	 */
	private static function client_session_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return md5( $ip . '|' . $ua . '|' . wp_salt() );
	}

	public function add_data_attributes() {
		global $product;
		if ( ! $product ) {
			return;
		}
		echo '<div class="product-info" data-product-id="' . esc_attr( $product->get_id() ) . '" data-product-title="' . esc_attr( $product->get_name() ) . '"></div>';
	}
}

register_activation_hook( __FILE__, array( 'Geschenkly_Analytics_Plugin', 'install' ) );
register_deactivation_hook( __FILE__, array( 'Geschenkly_Analytics_Plugin', 'deactivate' ) );
new Geschenkly_Analytics_Plugin();
