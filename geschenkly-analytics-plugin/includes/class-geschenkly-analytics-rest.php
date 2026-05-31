<?php
/**
 * REST-API für die Geschenkly Analytics.
 *
 * Ersetzt den früheren externen Node.js/MongoDB-Server (schindler-ventures.de:3002).
 * Alle Endpunkte liefern exakt die Datenstruktur, die das Frontend (product-card.js)
 * erwartet, lesen aber aus der WordPress-eigenen Event-Tabelle.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Geschenkly_Analytics_REST {

	const NS        = 'geschenkly/v1';
	const CACHE_TTL = 300; // Sekunden – Lese-Ergebnisse werden zwischengespeichert.

	private function table() {
		return Geschenkly_Analytics_Plugin::table_name();
	}

	/**
	 * Registriert alle Routen unter /wp-json/geschenkly/v1/.
	 */
	public function register_routes() {
		$id_args = array(
			'id' => array(
				'validate_callback' => function ( $param ) {
					return is_numeric( $param );
				},
			),
		);

		$read_routes = array(
			'popularity'          => 'popularity',
			'trend'               => 'trend',
			'monthly-clicks'      => 'monthly_clicks',
			'hourly'              => 'hourly',
			'top-categories-tags' => 'top_categories_tags',
			'trend-details'       => 'trend_details',
		);

		foreach ( $read_routes as $slug => $method ) {
			register_rest_route(
				self::NS,
				'/analytics/' . $slug . '/(?P<id>\d+)',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, $method ),
					'permission_callback' => '__return_true',
					'args'                => $id_args,
				)
			);
		}

		register_rest_route(
			self::NS,
			'/like',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'like' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/feedback',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'feedback' ),
				'permission_callback' => '__return_true',
			)
		);

		// Live-Badge: bewusst ohne Nonce, damit es auch hinter Full-Page-Cache
		// funktioniert. Integritaet kommt aus DISTINCT Session-Hashes, nicht aus Nonces.
		register_rest_route(
			self::NS,
			'/view',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'view' ),
				'permission_callback' => '__return_true',
			)
		);

		// Wunschliste per E-Mail an den Nutzer (ersetzt den externen Dienst auf Port 3004).
		register_rest_route(
			self::NS,
			'/wishlist',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'wishlist_email' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Versendet die gemerkten Produkte per E-Mail an die angegebene Adresse.
	 * Antwort: { success, count } oder WP_Error.
	 */
	public function wishlist_email( $request ) {
		$params = $request->get_json_params();
		$email  = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
		$ids    = ( isset( $params['productIds'] ) && is_array( $params['productIds'] ) )
			? array_map( 'intval', $params['productIds'] )
			: array();

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'geschenkly_bad_email', 'Ungültige E-Mail-Adresse', array( 'status' => 400 ) );
		}

		$ids = array_slice( array_filter( array_unique( $ids ) ), 0, 100 );
		if ( empty( $ids ) ) {
			return new WP_Error( 'geschenkly_empty', 'Keine Produkte übergeben', array( 'status' => 400 ) );
		}

		$items = array();
		foreach ( $ids as $id ) {
			if ( 'publish' !== get_post_status( $id ) || 'product' !== get_post_type( $id ) ) {
				continue;
			}
			$items[] = array(
				'name' => get_the_title( $id ),
				'url'  => get_permalink( $id ),
			);
		}
		if ( empty( $items ) ) {
			return new WP_Error( 'geschenkly_no_items', 'Keine gültigen Produkte gefunden', array( 'status' => 400 ) );
		}

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject  = sprintf( 'Deine Wunschliste bei %s', $blogname );

		$lines   = array();
		$lines[] = '<p>Hallo,</p>';
		$lines[] = '<p>hier ist deine gespeicherte Wunschliste:</p>';
		$lines[] = '<ul>';
		foreach ( $items as $item ) {
			$lines[] = '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
		}
		$lines[] = '</ul>';
		$lines[] = '<p>Viel Freude beim Schenken!<br>' . esc_html( $blogname ) . '</p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$sent    = wp_mail( $email, $subject, implode( "\n", $lines ), $headers );

		if ( ! $sent ) {
			return new WP_Error( 'geschenkly_mail_failed', 'E-Mail konnte nicht gesendet werden', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'count'   => count( $items ),
			)
		);
	}

	/**
	 * Erfasst einen Produkt-View (Heartbeat) und liefert die Live-Kennzahlen zurueck.
	 * Antwort: { viewersNow, viewsToday }
	 */
	public function view( $request ) {
		$params     = $request->get_json_params();
		$product_id = isset( $params['productId'] ) ? intval( $params['productId'] ) : 0;
		if ( ! $product_id ) {
			return new WP_Error( 'geschenkly_bad_request', 'Missing productId', array( 'status' => 400 ) );
		}

		Geschenkly_Analytics_Plugin::record_event(
			array(
				'product_id'   => $product_id,
				'event_type'   => 'view',
				'session_hash' => Geschenkly_Analytics_Plugin::client_session_hash(),
			)
		);

		return rest_ensure_response( $this->live_snapshot( $product_id ) );
	}

	/**
	 * Live-Kennzahlen je Produkt – beide auf DISTINCT Sessions, damit der
	 * 60s-Heartbeat die Zahlen nicht aufblaeht.
	 */
	private function live_snapshot( $product_id ) {
		global $wpdb;
		$table = $this->table();

		$viewers_now = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_hash) FROM {$table}
				 WHERE product_id = %d AND event_type = 'view'
				 AND created_at >= ( UTC_TIMESTAMP() - INTERVAL 180 SECOND )",
				$product_id
			)
		);
		$views_today = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_hash) FROM {$table}
				 WHERE product_id = %d AND event_type = 'view' AND created_at >= UTC_DATE()",
				$product_id
			)
		);

		return array(
			'viewersNow' => max( 1, $viewers_now ),
			'viewsToday' => $views_today,
		);
	}

	/* --------------------------------------------------------------------- */
	/* Lese-Endpunkte                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * Beliebtheit/Rang des Produkts innerhalb seiner Hauptkategorie.
	 * Antwort: { category, rank, totalProducts }
	 */
	public function popularity( $request ) {
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_pop_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! $terms || is_wp_error( $terms ) ) {
			$resp = array( 'category' => null );
			set_transient( $cache_key, $resp, self::CACHE_TTL );
			return rest_ensure_response( $resp );
		}

		$cat         = $terms[0];
		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $cat->term_id,
					),
				),
			)
		);

		$resp = array(
			'category'      => $cat->name,
			'rank'          => $this->rank_in_set( $product_id, $product_ids ),
			'totalProducts' => count( $product_ids ),
		);

		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/**
	 * Wochentrend (diese Woche vs. Vorwoche).
	 * Antwort: { trend, direction } oder { trend: null }
	 */
	public function trend( $request ) {
		global $wpdb;
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_trend_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table     = $this->table();
		$this_week = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY)",
				$product_id
			)
		);
		$last_week = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 14 DAY) AND created_at < (UTC_TIMESTAMP() - INTERVAL 7 DAY)",
				$product_id
			)
		);

		if ( 0 === $this_week && 0 === $last_week ) {
			$resp = array( 'trend' => null );
		} else {
			$base = max( $last_week, 1 );
			$resp = array(
				'trend'     => (int) round( ( ( $this_week - $last_week ) / $base ) * 100 ),
				'direction' => ( $this_week >= $last_week ) ? 'up' : 'down',
			);
		}

		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/**
	 * Kaufwünsche (Klicks) der letzten 30 Tage.
	 * Antwort: { totalClicks }
	 */
	public function monthly_clicks( $request ) {
		global $wpdb;
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_monthly_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table = $this->table();
		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)",
				$product_id
			)
		);

		$resp = array( 'totalClicks' => $total );
		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/**
	 * Klicks pro Stunde (letzte 7 Tage), gruppiert nach Tagesstunde.
	 * Antwort: [ { hour, clicks }, ... ]
	 */
	public function hourly( $request ) {
		global $wpdb;
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_hourly_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table = $this->table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT HOUR(created_at) AS hour, COUNT(*) AS clicks FROM {$table} WHERE product_id = %d AND event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 7 DAY) GROUP BY HOUR(created_at)",
				$product_id
			)
		);

		$resp = array();
		foreach ( $rows as $row ) {
			$resp[] = array(
				'hour'   => (int) $row->hour,
				'clicks' => (int) $row->clicks,
			);
		}

		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/**
	 * Top-Kategorien und -Tags, aus denen heraus das Produkt angeklickt wurde.
	 * Antwort: { topCategories: [{ _id:{category_name}, totalClicks }], topTags: [...] }
	 */
	public function top_categories_tags( $request ) {
		global $wpdb;
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_topcat_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table = $this->table();

		$cat_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT category_name, COUNT(*) AS totalClicks FROM {$table} WHERE product_id = %d AND event_type = 'click' AND category_name IS NOT NULL AND category_name <> '' GROUP BY category_name ORDER BY totalClicks DESC LIMIT 5",
				$product_id
			)
		);
		$tag_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tag_name, COUNT(*) AS totalClicks FROM {$table} WHERE product_id = %d AND event_type = 'click' AND tag_name IS NOT NULL AND tag_name <> '' GROUP BY tag_name ORDER BY totalClicks DESC LIMIT 5",
				$product_id
			)
		);

		$top_categories = array();
		foreach ( $cat_rows as $row ) {
			$top_categories[] = array(
				'_id'         => array( 'category_name' => $row->category_name ),
				'totalClicks' => (int) $row->totalClicks,
			);
		}
		$top_tags = array();
		foreach ( $tag_rows as $row ) {
			$top_tags[] = array(
				'_id'         => array( 'tag_name' => $row->tag_name ),
				'totalClicks' => (int) $row->totalClicks,
			);
		}

		$resp = array(
			'topCategories' => $top_categories,
			'topTags'       => $top_tags,
		);

		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/**
	 * Wöchentlicher Beliebtheitsverlauf (letzte 6 Wochen), skaliert auf 0–5.
	 * Antwort: [ { week, popularity }, ... ]
	 */
	public function trend_details( $request ) {
		global $wpdb;
		$product_id = intval( $request['id'] );
		$cache_key  = 'gky_trenddet_' . $product_id;
		$cached     = get_transient( $cache_key );
		if ( false !== $cached ) {
			return rest_ensure_response( $cached );
		}

		$table = $this->table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT YEARWEEK(created_at, 3) AS yw, COUNT(*) AS c FROM {$table} WHERE product_id = %d AND event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 6 WEEK) GROUP BY yw ORDER BY yw ASC",
				$product_id
			)
		);

		$max = 0;
		foreach ( $rows as $row ) {
			$max = max( $max, (int) $row->c );
		}

		$resp = array();
		$week = 1;
		foreach ( $rows as $row ) {
			$resp[] = array(
				'week'       => $week,
				'popularity' => $max > 0 ? round( ( (int) $row->c / $max ) * 5, 1 ) : 0,
			);
			$week++;
		}

		set_transient( $cache_key, $resp, self::CACHE_TTL );
		return rest_ensure_response( $resp );
	}

	/* --------------------------------------------------------------------- */
	/* Schreib-Endpunkte                                                     */
	/* --------------------------------------------------------------------- */

	public function like( $request ) {
		// Ohne harte Nonce-Pruefung, damit es hinter Full-Page-Cache funktioniert
		// (eingebettete Nonces sind dort abgelaufen). Nur unkritische Like-Telemetrie.
		$params     = $request->get_json_params();
		$product_id = isset( $params['productId'] ) ? intval( $params['productId'] ) : 0;
		if ( ! $product_id ) {
			return new WP_Error( 'geschenkly_bad_request', 'Missing productId', array( 'status' => 400 ) );
		}

		Geschenkly_Analytics_Plugin::record_event(
			array(
				'product_id' => $product_id,
				'event_type' => 'like',
			)
		);

		global $wpdb;
		$table = $this->table();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND event_type = 'like'",
				$product_id
			)
		);

		return rest_ensure_response(
			array(
				'success'   => true,
				'likeCount' => $count,
			)
		);
	}

	public function feedback( $request ) {
		// Ohne harte Nonce-Pruefung (Cache-Kompatibilitaet). Feedback wird beim
		// Speichern via sanitize_textarea_field bereinigt (Tags werden entfernt).
		$params     = $request->get_json_params();
		$product_id = isset( $params['productId'] ) ? intval( $params['productId'] ) : 0;
		$feedback   = isset( $params['feedback'] ) ? sanitize_textarea_field( $params['feedback'] ) : '';
		if ( ! $product_id || '' === trim( $feedback ) ) {
			return new WP_Error( 'geschenkly_bad_request', 'Missing productId or feedback', array( 'status' => 400 ) );
		}

		Geschenkly_Analytics_Plugin::record_event(
			array(
				'product_id' => $product_id,
				'event_type' => 'feedback',
				'meta'       => $feedback,
			)
		);

		return rest_ensure_response( array( 'success' => true ) );
	}

	/* --------------------------------------------------------------------- */
	/* Helfer                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Rang des Produkts innerhalb einer ID-Menge nach Klicks der letzten 30 Tage
	 * (Competition Ranking: 1 + Anzahl strikt beliebterer Produkte).
	 */
	private function rank_in_set( $product_id, $product_ids ) {
		global $wpdb;
		if ( empty( $product_ids ) ) {
			return 1;
		}

		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
		$table        = $this->table();
		$sql          = $wpdb->prepare(
			"SELECT product_id, COUNT(*) AS c FROM {$table} WHERE event_type = 'click' AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY) AND product_id IN ($placeholders) GROUP BY product_id",
			$product_ids
		);
		$rows = $wpdb->get_results( $sql );

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ intval( $row->product_id ) ] = (int) $row->c;
		}

		$mine   = isset( $counts[ $product_id ] ) ? $counts[ $product_id ] : 0;
		$better = 0;
		foreach ( $counts as $count ) {
			if ( $count > $mine ) {
				$better++;
			}
		}

		return $better + 1;
	}
}
