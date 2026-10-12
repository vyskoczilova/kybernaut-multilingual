<?php
/**
 * Front-end context: which page is shown, its language and its language versions.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the current request against the map. Cheap: no queries beyond the cached map.
 */
final class Context {

	/**
	 * Map key of the current view: page ID, 'home', or null for other views.
	 *
	 * @return int|string|null
	 */
	public static function key() {
		if ( is_front_page() && ! is_paged() ) {
			return Map::HOME;
		}
		if ( is_page() ) {
			return (int) get_queried_object_id();
		}
		return null;
	}

	/**
	 * Language code of the current view.
	 *
	 * @return string
	 */
	public static function language() {
		$key = self::key();
		return is_int( $key ) ? Map::language_of( $key ) : Settings::default_code();
	}

	/**
	 * Public URLs of all language versions of the current view, keyed by code,
	 * default language first. Empty unless at least two versions exist.
	 *
	 * @return array<string, string>
	 */
	public static function versions() {
		return self::versions_of( self::key() );
	}

	/**
	 * Public URLs of all language versions of a map key.
	 *
	 * @param int|string|null $key Page ID, 'home' or null.
	 * @return array<string, string>
	 */
	public static function versions_of( $key ) {
		if ( null === $key ) {
			return array();
		}

		$map    = Map::get();
		$target = isset( $map['lang'][ $key ] ) ? ( $map['links'][ $key ] ?? null ) : $key;
		if ( null === $target || empty( $map['reverse'][ $target ] ) ) {
			return array();
		}

		$versions = array();
		foreach ( array( Settings::default_code() => $target ) + $map['reverse'][ $target ] as $code => $id ) {
			if ( isset( $map['urls'][ $id ] ) ) {
				$versions[ $code ] = $map['urls'][ $id ];
			}
		}

		return count( $versions ) > 1 ? $versions : array();
	}

	/**
	 * Home URL of a language: its root page, or the site homepage for the default language.
	 *
	 * @param string $code Language code.
	 * @return string|null Null when the language has no public home.
	 */
	public static function language_home( $code ) {
		$map = Map::get();
		$key = $map['roots'][ $code ] ?? ( Settings::default_code() === $code ? Map::HOME : null );
		return null === $key ? null : ( $map['urls'][ $key ] ?? null );
	}
}
