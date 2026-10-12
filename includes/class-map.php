<?php
/**
 * Cached map of language roots, page languages and translation links.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and caches the whole translation relation in one small array (ADR 0004).
 *
 * The map is the single judge of what is valid; admin and front end only read it.
 *
 * Shape:
 * - roots:   code => root page ID
 * - lang:    page ID => code, for every page inside a language root
 * - links:   translated page ID => counterpart key (page ID or 'home')
 * - reverse: counterpart key => [ code => translated page ID ]
 * - urls:    key => permalink, for 'home' and the public pages the front end links to
 * - front:   static front page ID (0 when the homepage shows posts)
 * - version: changes on every rebuild, used as a cache key
 */
final class Map {

	const TRANSIENT = 'kbnt_ml_map';

	/** Post meta: language code on a language root page. */
	const META_ROOT = '_kbnt_ml_root';

	/** Post meta: counterpart (page ID or 'home') on a translated page. */
	const META_LINK = '_kbnt_ml_translation_of';

	/** Counterpart key of the site homepage. */
	const HOME = 'home';

	/**
	 * Request-level copy of the map.
	 *
	 * @var array|null
	 */
	private static $map = null;

	/**
	 * Whether the persisted map is stale; deleted once on shutdown.
	 *
	 * @var bool
	 */
	private static $dirty = false;

	/**
	 * Invalidate the cache whenever something it depends on changes.
	 */
	public static function register_hooks() {
		add_action( 'save_post_page', array( __CLASS__, 'flush' ) ); // Also fires on status changes and trashing.
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 10, 2 );
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'on_meta_change' ), 10, 3 );
		}
		foreach ( array( Settings::OPTION, 'show_on_front', 'page_on_front', 'permalink_structure', 'home' ) as $option ) {
			add_action( "update_option_{$option}", array( __CLASS__, 'flush' ) );
			add_action( "add_option_{$option}", array( __CLASS__, 'flush' ) );
		}
	}

	/**
	 * Flush when a page is deleted.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function on_deleted_post( $post_id, $post ) {
		if ( $post instanceof \WP_Post && 'page' === $post->post_type ) {
			self::flush();
		}
	}

	/**
	 * Flush when one of the plugin's meta keys changes.
	 *
	 * @param int|int[] $meta_ids  Meta ID(s).
	 * @param int       $object_id Post ID.
	 * @param string    $meta_key  Meta key.
	 */
	public static function on_meta_change( $meta_ids, $object_id, $meta_key ) {
		if ( self::META_ROOT === $meta_key || self::META_LINK === $meta_key ) {
			self::flush();
		}
	}

	/**
	 * Mark the map stale. The transient is deleted once, on shutdown, however many changes happen.
	 */
	public static function flush() {
		self::$map = null;
		if ( ! self::$dirty ) {
			self::$dirty = true;
			add_action( 'shutdown', array( __CLASS__, 'persist_flush' ) );
		}
	}

	/**
	 * Delete the stale transient.
	 */
	public static function persist_flush() {
		delete_transient( self::TRANSIENT );
		self::$dirty = false;
	}

	/**
	 * The map, built on demand. While changes are pending it is built in memory only.
	 *
	 * @return array
	 */
	public static function get() {
		if ( null === self::$map ) {
			$map = self::$dirty ? false : get_transient( self::TRANSIENT );
			if ( ! is_array( $map ) || ! isset( $map['version'] ) ) {
				$map = self::build();
				if ( ! self::$dirty ) {
					set_transient( self::TRANSIENT, $map );
				}
			}
			self::$map = $map;
		}
		return self::$map;
	}

	/**
	 * Language code of a page (default language when outside any root).
	 *
	 * @param int $page_id Page ID.
	 * @return string
	 */
	public static function language_of( $page_id ) {
		return self::get()['lang'][ $page_id ] ?? Settings::default_code();
	}

	/**
	 * Counterpart key of a page: 'home' for the static front page, else the ID.
	 *
	 * @param int $page_id Page ID.
	 * @return int|string
	 */
	public static function key_of( $page_id ) {
		return self::get()['front'] === $page_id ? self::HOME : $page_id;
	}

	/**
	 * Translations of a default-language page, keyed by language code.
	 *
	 * @param int $page_id Page ID.
	 * @return array<string, int>
	 */
	public static function translations_of( $page_id ) {
		return self::get()['reverse'][ self::key_of( $page_id ) ] ?? array();
	}

	/**
	 * Whether another page already is the root of the language.
	 *
	 * @param string $code    Language code.
	 * @param int    $page_id Page asking.
	 * @return bool
	 */
	public static function root_taken( $code, $page_id ) {
		$roots = self::get()['roots'];
		return isset( $roots[ $code ] ) && $roots[ $code ] !== $page_id;
	}

	/**
	 * Whether another page already translates the counterpart into the language.
	 *
	 * @param int|string $target  Page ID or 'home'.
	 * @param string     $code    Language code.
	 * @param int        $page_id Page asking.
	 * @return bool
	 */
	public static function link_taken( $target, $code, $page_id ) {
		$reverse = self::get()['reverse'];
		$target  = is_int( $target ) ? self::key_of( $target ) : $target;
		return isset( $reverse[ $target ][ $code ] ) && $reverse[ $target ][ $code ] !== $page_id;
	}

	/**
	 * Build the map from two queries.
	 *
	 * @return array
	 */
	private static function build() {
		global $wpdb;

		$front = 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : 0;
		$map   = array(
			'roots'   => array(),
			'lang'    => array(),
			'links'   => array(),
			'reverse' => array(),
			'urls'    => array( self::HOME => home_url( '/' ) ),
			'front'   => $front,
			'version' => uniqid( '', true ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- One cheap query on rebuild only; result is cached.
		$pages = $wpdb->get_results(
			"SELECT ID, post_parent, post_status, post_password FROM {$wpdb->posts}
			WHERE post_type = 'page' AND post_status NOT IN ( 'trash', 'auto-draft', 'inherit' )
			ORDER BY ID ASC",
			OBJECT_K
		);
		if ( ! $pages ) {
			return $map;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- As above.
		$meta = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key IN ( %s, %s ) ORDER BY post_id ASC",
				self::META_ROOT,
				self::META_LINK
			)
		);

		$root_meta = array();
		$link_meta = array();
		foreach ( $meta as $row ) {
			$id = (int) $row->post_id;
			if ( isset( $pages[ $id ] ) ) {
				if ( self::META_ROOT === $row->meta_key ) {
					$root_meta[ $id ] = $row->meta_value;
				} else {
					$link_meta[ $id ] = $row->meta_value;
				}
			}
		}

		// Language roots: top-level pages with a configured language, first one per language wins.
		foreach ( $root_meta as $id => $code ) {
			if ( 0 === (int) $pages[ $id ]->post_parent && Settings::is_extra( $code ) && ! isset( $map['roots'][ $code ] ) ) {
				$map['roots'][ $code ] = $id;
			}
		}

		// Page languages: each root and its descendants (in-memory walk, no queries).
		foreach ( $map['roots'] as $code => $root ) {
			$map['lang'][ $root ] = $code;
			foreach ( get_page_children( $root, $pages ) as $child ) {
				$map['lang'][ (int) $child->ID ] = $code;
			}
		}

		// Translation links: from a translated page to a default-language page or the homepage.
		foreach ( $link_meta as $id => $target ) {
			$code   = $map['lang'][ $id ] ?? null;
			$target = self::HOME === $target ? self::HOME : absint( $target );
			if ( $target && $target === $front ) {
				$target = self::HOME;
			}
			if (
				null === $code
				|| ! $target
				|| ( is_int( $target ) && ( ! isset( $pages[ $target ] ) || isset( $map['lang'][ $target ] ) ) )
				|| isset( $map['reverse'][ $target ][ $code ] ) // One translation per language and counterpart; first one wins.
			) {
				continue;
			}
			$map['links'][ $id ]                = $target;
			$map['reverse'][ $target ][ $code ] = $id;
		}

		// Permalinks of every public page the front end may link to.
		_prime_post_caches( array_keys( $pages ), false, false );
		$linkable = array_merge( array_values( $map['roots'] ), array_keys( $map['links'] ), array_filter( $map['links'], 'is_int' ) );
		foreach ( array_unique( $linkable ) as $id ) {
			if ( 'publish' === $pages[ $id ]->post_status && '' === $pages[ $id ]->post_password ) {
				$map['urls'][ $id ] = get_permalink( $id );
			}
		}

		return $map;
	}
}
