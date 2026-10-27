<?php
/**
 * Plugin settings: default language, extra languages and switcher behaviour.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Read access to the `kbnt_ml_settings` option.
 */
final class Settings {

	const OPTION = 'kbnt_ml_settings';

	/** Switcher behaviour when a translation is missing: hide the language. */
	const MISSING_HIDE = 'hide';

	/** Switcher behaviour when a translation is missing: link to the language home. */
	const MISSING_HOME = 'home';

	/**
	 * Request-level cache of the normalised settings.
	 *
	 * @var array|null
	 */
	private static $settings = null;

	/**
	 * Drop the request-level cache when the option changes.
	 */
	public static function register_hooks() {
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
		add_action( 'add_option_' . self::OPTION, array( __CLASS__, 'flush' ) );
	}

	/**
	 * Store default settings on first activation (autoloaded, read on every request).
	 */
	public static function activate() {
		add_option( self::OPTION, self::defaults(), '', true );
	}

	/**
	 * Default settings derived from the site locale.
	 *
	 * @return array
	 */
	public static function defaults() {
		$parts = explode( '_', get_locale() );
		$code  = strtolower( $parts[0] );

		return array(
			'default'   => array(
				'code'  => $code,
				'label' => strtoupper( $parts[1] ?? $code ), // Country for the switcher label, e.g. cs_CZ → CZ.
			),
			'languages' => 'en' === $code ? array() : array(
				array(
					'code'   => 'en',
					'label'  => 'EN',
					'locale' => 'en_US',
				),
			),
			'missing'   => self::MISSING_HIDE,
		);
	}

	/**
	 * Normalised settings.
	 *
	 * @return array{default: array{code: string, label: string}, languages: array<string, array{code: string, label: string, locale: string}>, missing: string}
	 */
	public static function get() {
		if ( null === self::$settings ) {
			$saved    = get_option( self::OPTION );
			$defaults = self::defaults();
			$saved    = is_array( $saved ) ? $saved : $defaults;

			// Rows may come from an older version or a manual option edit: fill in the keys readers rely on.
			$languages = array();
			foreach ( (array) ( $saved['languages'] ?? array() ) as $language ) {
				if ( is_array( $language ) && ! empty( $language['code'] ) ) {
					$languages[ $language['code'] ] = $language + array(
						'label'  => strtoupper( $language['code'] ),
						'locale' => 'en_US',
					);
				}
			}

			$default = ! empty( $saved['default']['code'] ) ? $saved['default'] : $defaults['default'];

			self::$settings = array(
				'default'   => $default + array( 'label' => strtoupper( $default['code'] ) ),
				'languages' => $languages,
				'missing'   => self::sanitize_missing( $saved['missing'] ?? '' ),
			);
		}

		return self::$settings;
	}

	/**
	 * Whitelist the missing-translation behaviour.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_missing( $value ) {
		return self::MISSING_HOME === $value ? self::MISSING_HOME : self::MISSING_HIDE;
	}

	/**
	 * Drop the request-level cache (after the option changes).
	 */
	public static function flush() {
		self::$settings = null;
	}

	/**
	 * Hreflang code of the default language.
	 *
	 * @return string
	 */
	public static function default_code() {
		return self::get()['default']['code'];
	}

	/**
	 * Extra languages keyed by hreflang code.
	 *
	 * @return array<string, array{code: string, label: string, locale: string}>
	 */
	public static function languages() {
		return self::get()['languages'];
	}

	/**
	 * Whether the code is a configured extra language.
	 *
	 * @param string $code Hreflang code.
	 * @return bool
	 */
	public static function is_extra( $code ) {
		return is_string( $code ) && isset( self::get()['languages'][ $code ] );
	}

	/**
	 * Switcher labels of all languages, default first, keyed by code.
	 *
	 * @return array<string, string>
	 */
	public static function labels() {
		$settings = self::get();
		$labels   = array( $settings['default']['code'] => $settings['default']['label'] );
		foreach ( $settings['languages'] as $code => $language ) {
			$labels[ $code ] = $language['label'];
		}
		return $labels;
	}

	/**
	 * WP locale for a language code (site locale for the default language).
	 *
	 * @param string $code Hreflang code.
	 * @return string
	 */
	public static function locale( $code ) {
		$languages = self::languages();
		return isset( $languages[ $code ] ) ? $languages[ $code ]['locale'] : get_locale();
	}
}
