<?php
/**
 * Front-end output: locale switching and hreflang links.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks the front end (ADR 0005, 0006).
 */
final class Frontend {

	/**
	 * Register front-end hooks.
	 */
	public static function register_hooks() {
		add_action( 'wp', array( __CLASS__, 'switch_locale' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'print_hreflang' ), 2 );
	}

	/**
	 * Switch the WordPress locale once the queried page is known.
	 *
	 * `<html lang>`, Yoast's og:locale and schema inLanguage follow automatically.
	 */
	public static function switch_locale() {
		if ( is_admin() ) {
			return;
		}
		$locale = Settings::locale( Context::language() );
		if ( get_locale() !== $locale ) {
			switch_to_locale( $locale );
		}
	}

	/**
	 * Print hreflang alternates and x-default for pages with translations.
	 */
	public static function print_hreflang() {
		$versions = Context::versions();
		if ( ! $versions ) {
			return;
		}

		foreach ( $versions as $code => $url ) {
			printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $code ), esc_url( $url ) );
		}

		$default = Settings::default_code();
		if ( isset( $versions[ $default ] ) ) {
			printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( $versions[ $default ] ) );
		}
	}
}
