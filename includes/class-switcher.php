<?php
/**
 * Language switcher: block (standalone or as Navigation menu items) and shortcode.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and caches the language switcher (ADR 0007).
 */
final class Switcher {

	/** Object cache group; persistent only with a persistent object cache, never written to the database. */
	const CACHE_GROUP = 'kbnt_ml_switcher';

	/** Markup variant: standalone `<nav><ul>` list. */
	const VARIANT_LIST = 'list';

	/** Markup variant: `<li>` items for the core Navigation block. */
	const VARIANT_MENU = 'menu';

	/**
	 * Register the block, the shortcode and the base style.
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register block, shortcode and style.
	 */
	public static function register() {
		wp_register_style( 'kbnt-ml-switcher', false, array(), VERSION );
		wp_add_inline_style(
			'kbnt-ml-switcher',
			'.kbnt-ml-switcher ul{display:flex;flex-wrap:wrap;gap:.5em;margin:0;padding:0;list-style:none}'
		);

		$block = register_block_type(
			dirname( PLUGIN_FILE ) . '/blocks/language-switcher',
			array( 'render_callback' => array( __CLASS__, 'render_block' ) )
		);

		// The editor script has no JSON translations; hand it the few strings it needs.
		if ( $block && is_admin() && ! empty( $block->editor_script_handles[0] ) ) {
			$strings = array(
				'Settings'              => array( __( 'Settings', 'kybernaut-multilingual' ) ),
				'Show current language' => array( __( 'Show current language', 'kybernaut-multilingual' ) ),
			);
			wp_add_inline_script(
				$block->editor_script_handles[0],
				'wp.i18n.setLocaleData(' . wp_json_encode( $strings ) . ', "kybernaut-multilingual");',
				'before'
			);
		}

		add_shortcode( 'kybernaut_ml_switcher', array( __CLASS__, 'render_shortcode' ) );
	}

	/**
	 * Block render callback. Inside a Navigation block it renders plain menu items.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Inner content (unused).
	 * @param \WP_Block $block      Block instance.
	 * @return string
	 */
	public static function render_block( $attributes, $content = '', $block = null ) {
		$show_current = ! isset( $attributes['showCurrent'] ) || (bool) $attributes['showCurrent'];

		// Only the Navigation block provides this context.
		if ( $block instanceof \WP_Block && array_key_exists( 'openSubmenusOnClick', $block->context ) ) {
			return self::render( self::VARIANT_MENU, $show_current );
		}

		$html = self::render( self::VARIANT_LIST, $show_current );
		// Block supports (alignment, spacing, typography) go on a wrapper.
		return '' === $html ? '' : '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
	}

	/**
	 * Shortcode callback: `[kybernaut_ml_switcher]`, optionally `show_current="no"`.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'show_current' => 'yes' ), $atts, 'kybernaut_ml_switcher' );
		return self::render( self::VARIANT_LIST, ! in_array( strtolower( $atts['show_current'] ), array( 'no', '0', 'false' ), true ) );
	}

	/**
	 * Switcher markup for the current view, from cache when possible.
	 *
	 * @param string $variant      self::VARIANT_LIST or self::VARIANT_MENU.
	 * @param bool   $show_current Whether to include the current language.
	 * @return string Empty when there is no other language to switch to.
	 */
	public static function render( $variant = self::VARIANT_LIST, $show_current = true ) {
		$key   = Context::key();
		$lang  = Context::language();
		$cache = implode( '|', array( Map::get()['version'], null === $key ? '-' : $key, $lang, determine_locale(), $variant, (int) $show_current ) );

		$html = wp_cache_get( $cache, self::CACHE_GROUP );
		if ( false === $html ) {
			$html = self::build( self::items( $key, $lang ), $variant, $show_current );
			wp_cache_set( $cache, $html, self::CACHE_GROUP, DAY_IN_SECONDS );
		}

		if ( '' !== $html && self::VARIANT_LIST === $variant ) {
			wp_enqueue_style( 'kbnt-ml-switcher' );
		}

		return $html;
	}

	/**
	 * Languages to show, in settings order. Other languages without a URL are left out.
	 *
	 * @param int|string|null $key  Map key of the current view.
	 * @param string          $lang Language code of the current view.
	 * @return array<int, array{code: string, label: string, url: string|null}> URL is null for the current language.
	 */
	private static function items( $key, $lang ) {
		$versions = Context::versions_of( $key );
		$fallback = Settings::MISSING_HOME === Settings::get()['missing'];
		$items    = array();

		foreach ( Settings::labels() as $code => $label ) {
			$url = null;
			if ( $code !== $lang ) {
				$url = $versions[ $code ] ?? ( $fallback ? Context::language_home( $code ) : null );
				if ( null === $url ) {
					continue;
				}
			}
			$items[] = array(
				'code'  => $code,
				'label' => $label,
				'url'   => $url,
			);
		}

		return $items;
	}

	/**
	 * Build the switcher markup.
	 *
	 * @param array  $items        Languages from items().
	 * @param string $variant      Markup variant.
	 * @param bool   $show_current Whether to include the current language.
	 * @return string
	 */
	private static function build( $items, $variant, $show_current ) {
		// Only the current language: nothing to switch to.
		if ( count( $items ) < 2 ) {
			return '';
		}

		$menu = self::VARIANT_MENU === $variant;
		$html = '';

		foreach ( $items as $item ) {
			$current = null === $item['url'];
			if ( $current && ! $show_current ) {
				continue;
			}

			$li_class = $menu ? 'wp-block-navigation-item kbnt-ml-switcher__item' : 'kbnt-ml-switcher__item';
			$a_class  = $menu ? ' class="wp-block-navigation-item__content"' : '';
			$label    = $menu ? '<span class="wp-block-navigation-item__label">' . esc_html( $item['label'] ) . '</span>' : esc_html( $item['label'] );

			if ( $current ) {
				$html .= sprintf(
					'<li class="%1$s is-current%2$s"><span%3$s aria-current="page" lang="%4$s">%5$s</span></li>',
					esc_attr( $li_class ),
					$menu ? ' current-menu-item' : '',
					$a_class,
					esc_attr( $item['code'] ),
					$label
				);
			} else {
				$html .= sprintf(
					'<li class="%1$s"><a%2$s href="%3$s" hreflang="%4$s" lang="%4$s">%5$s</a></li>',
					esc_attr( $li_class ),
					$a_class,
					esc_url( $item['url'] ),
					esc_attr( $item['code'] ),
					$label
				);
			}
		}

		if ( $menu ) {
			return $html;
		}

		return sprintf(
			'<nav class="kbnt-ml-switcher" aria-label="%1$s"><ul>%2$s</ul></nav>',
			esc_attr__( 'Language', 'kybernaut-multilingual' ),
			$html
		);
	}
}
