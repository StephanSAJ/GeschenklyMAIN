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
		// Trending-Badge auf den Listing-/Kategorie-Karten (server-seitig, cache-sicher).
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_trending_badge' ), 8 );
		// Markentreues Archiv-/Kategorie-Stylesheet (nur auf Shop-/Kategorie-/Tag-Seiten).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_archive_styles' ) );
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

		// Hybrid-Ranking: Damit inaktive Produkte nicht mit eingefrorenen Klick-
		// Scores dauerhaft "Top" blockieren, wird JEDEN Rollup zuerst alles auf den
		// ruhigen Basiswert zurueckgesetzt (_homepage_rating, sonst 0) und danach
		// fuer aktive Produkte mit dem aktuellen 30-Tage-Klick-Score ueberschrieben.
		$table = self::table_name();
		$wpdb->query(
			"UPDATE {$wpdb->postmeta} ps
			 LEFT JOIN {$wpdb->postmeta} hr
			   ON hr.post_id = ps.post_id AND hr.meta_key = '_homepage_rating'
			 SET ps.meta_value = COALESCE( hr.meta_value, 0 )
			 WHERE ps.meta_key = '_geschenkly_pop_score'"
		);

		// Zeitgewichteter Klick-Score je Produkt (letzte 30 Tage, juengere Klicks staerker).
		$rows = $wpdb->get_results(
			"SELECT product_id,
			        SUM(CASE WHEN created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY) THEN 3 ELSE 1 END) AS score
			 FROM {$table}
			 WHERE event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)
			 GROUP BY product_id"
		);

		foreach ( $rows as $row ) {
			update_post_meta( (int) $row->product_id, '_geschenkly_pop_score', (float) $row->score );
		}

		// Trend-Flag je Produkt: diese Woche vs. Vorwoche. Erst alle zuruecksetzen
		// (eine indizierte Query), dann fuer steigende Produkte auf 'up' setzen.
		$wpdb->query(
			"UPDATE {$wpdb->postmeta} SET meta_value = '' WHERE meta_key = '_geschenkly_trend' AND meta_value <> ''"
		);
		$trend_rows = $wpdb->get_results(
			"SELECT product_id,
			        SUM(CASE WHEN created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS this_week,
			        SUM(CASE WHEN created_at <  (UTC_TIMESTAMP() - INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS last_week
			 FROM {$table}
			 WHERE event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 14 DAY)
			 GROUP BY product_id"
		);
		foreach ( $trend_rows as $row ) {
			// 'Im Trend' nur bei klarer Steigerung und etwas Volumen (vermeidet Rauschen).
			if ( (int) $row->this_week >= 3 && (int) $row->this_week > (int) $row->last_week ) {
				update_post_meta( (int) $row->product_id, '_geschenkly_trend', 'up' );
			}
		}

		// View-Events sind nur fuer das Live-Badge relevant -> Tabelle schlank halten.
		$wpdb->query(
			"DELETE FROM {$table} WHERE event_type = 'view' AND created_at < ( UTC_TIMESTAMP() - INTERVAL 2 DAY )"
		);

		// Gecachte Top-Listen der Kategorien invalidieren, damit Badges aktuell bleiben.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_gky_topcat_ids_%' OR option_name LIKE '\_transient\_timeout\_gky_topcat_ids_%'"
		);
	}

	/**
	 * Liefert die Top-N-Produkt-IDs einer Kategorie nach _geschenkly_pop_score.
	 * Ergebnis wird pro Kategorie zwischengespeichert (eine Query pro Archivseite).
	 */
	public static function top_products_in_category( $term_id, $limit = 3 ) {
		// v2 im Key: invalidiert alte, nach pop_score gerankte Listen sofort.
		$cache_key = 'gky_topcat_ids2_' . $term_id . '_' . $limit;
		$ids       = get_transient( $cache_key );
		if ( false === $ids ) {
			// WICHTIG: dieselbe Rangordnung wie die Kategorie-Liste verwenden,
			// damit "Top N in {Kategorie}" auf den tatsaechlich zuerst gezeigten
			// Produkten landet. Die Liste sortiert nach dem kategorie-eigenen
			// Rating (bzw. dem globalen pop_score, wenn Event-Sort aktiviert ist).
			$sort_key = ( get_option( 'geschenkly_use_event_sort' ) === 'yes' )
				? '_geschenkly_pop_score'
				: '_category_rating_' . $term_id;

			$ids = get_posts(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'fields'                 => 'ids',
					'posts_per_page'         => $limit,
					'meta_key'               => $sort_key,
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'tax_query'              => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $term_id,
						),
					),
				)
			);
			$ids = array_map( 'intval', $ids );
			set_transient( $cache_key, $ids, HOUR_IN_SECONDS );
		}
		return $ids;
	}

	/**
	 * Rendert genau EIN priorisiertes Badge je Listing-Karte:
	 * 1) Top 3 in der aktuellen Kategorie, sonst 2) Im Trend, sonst nichts.
	 */
	public function render_trending_badge() {
		global $product;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}
		$id    = $product->get_id();
		$label = '';

		if ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$term = get_queried_object();
			if ( $term && ! is_wp_error( $term ) && isset( $term->term_id ) ) {
				$top = self::top_products_in_category( $term->term_id, 3 );
				$pos = array_search( (int) $id, $top, true );
				if ( false !== $pos ) {
					$label = '🏆 Top ' . ( $pos + 1 ) . ' in ' . $term->name;
				}
			}
		}

		if ( '' === $label && 'up' === get_post_meta( $id, '_geschenkly_trend', true ) ) {
			$label = '🔥 Im Trend';
		}

		if ( '' === $label ) {
			return;
		}

		echo '<span class="geschenkly-trend-badge">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Laedt das markentreue Archiv-/Kategorie-Stylesheet – nur auf Shop-/Kategorie-/
	 * Tag-Archiven. filemtime als Version => Browser/Page-Cache bricht bei jeder
	 * Dateiaenderung automatisch. Enthaelt auch das Trending-Badge-Styling.
	 */
	public function enqueue_archive_styles() {
		if ( ! function_exists( 'is_shop' ) ) {
			return;
		}
		if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}

		// Dateinamen bewusst versioniert (…-v2): manche Full-Page-/CDN-Caches
		// ignorieren den ?ver-Parameter und liefern sonst veraltetes CSS aus.
		$path = plugin_dir_path( __FILE__ ) . 'assets/css/geschenkly-archive-v7.css';
		$ver  = file_exists( $path ) ? filemtime( $path ) : GESCHENKLY_ANALYTICS_VERSION;
		wp_enqueue_style(
			'geschenkly-archive',
			plugin_dir_url( __FILE__ ) . 'assets/css/geschenkly-archive-v7.css',
			array(),
			$ver
		);

		// Ratgeber-Akkordeon + aktive-Filter-Chips/Treffer-Zaehler.
		$js_path = plugin_dir_path( __FILE__ ) . 'assets/js/geschenkly-archive-v4.js';
		$js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : GESCHENKLY_ANALYTICS_VERSION;
		wp_enqueue_script(
			'geschenkly-archive',
			plugin_dir_url( __FILE__ ) . 'assets/js/geschenkly-archive-v4.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script(
			'geschenkly-archive',
			'geschenklyArchive',
			array(
				'ratgeber' => __( 'Passende Ratgeber', 'geschenkly' ),
			)
		);
	}

	/**
	 * Stellt sicher, dass das DB-Schema aktuell ist (z. B. nach Plugin-Update ohne Reaktivierung).
	 */
	public function maybe_upgrade_db() {
		if ( get_option( 'geschenkly_analytics_db_version' ) !== GESCHENKLY_ANALYTICS_DB_VERSION ) {
			self::install();
		}

		// Cron selbstheilend sicherstellen: falls das stuendliche Rollup-Event
		// je verloren geht, hier neu einplanen (sonst aktualisiert sich "Top" nie).
		if ( ! wp_next_scheduled( 'geschenkly_rollup_popularity' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'geschenkly_rollup_popularity' );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'rating_js', plugin_dir_url( __FILE__ ) . 'js/rating.js', array( 'jquery' ), GESCHENKLY_ANALYTICS_VERSION, true );
		wp_enqueue_script( 'rating_new_design_js', plugin_dir_url( __FILE__ ) . 'js/ratingNewDesign.js', array( 'jquery' ), GESCHENKLY_ANALYTICS_VERSION, true );

		// Site-weite Header-/Menü-Auffrischung (markentreu, cache-/update-sicher).
		$header_path = plugin_dir_path( __FILE__ ) . 'assets/css/geschenkly-header-v2.css';
		$header_ver  = file_exists( $header_path ) ? filemtime( $header_path ) : GESCHENKLY_ANALYTICS_VERSION;
		wp_enqueue_style(
			'geschenkly-header',
			plugin_dir_url( __FILE__ ) . 'assets/css/geschenkly-header-v2.css',
			array(),
			$header_ver
		);

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
		// Bewusst ohne harte Nonce-Pruefung: dieser Tracking-Endpunkt muss auch
		// hinter Full-Page-Cache funktionieren (dort sind eingebettete Nonces
		// laengst abgelaufen). Es werden nur unkritische Klick-Telemetriedaten erfasst.
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
	public static function client_session_hash() {
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
