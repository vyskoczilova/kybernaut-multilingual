<?php
/**
 * Settings → Multilingual screen.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Registers, renders and sanitises the settings (ADR 0002).
 */
final class Settings_Page {

	const SLUG = 'kybernaut-multilingual';

	/**
	 * Register admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PLUGIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Register the option with its sanitiser.
	 */
	public static function register_setting() {
		register_setting(
			self::SLUG,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => Settings::defaults(),
			)
		);
	}

	/**
	 * Add the page under Settings.
	 */
	public static function add_page() {
		add_options_page(
			__( 'Multilingual', 'kybernaut-multilingual' ),
			__( 'Multilingual', 'kybernaut-multilingual' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Action links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ),
				esc_html__( 'Settings', 'kybernaut-multilingual' )
			)
		);
		return $links;
	}

	/**
	 * Sanitise and validate submitted settings (options.php has already unslashed them).
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$previous = get_option( Settings::OPTION );
		$previous = is_array( $previous ) ? $previous : Settings::defaults();

		if ( ! is_array( $input ) ) {
			return $previous;
		}

		$errors  = array();
		$default = array(
			'code'  => self::sanitize_code( $input['default']['code'] ?? '' ),
			'label' => sanitize_text_field( (string) ( $input['default']['label'] ?? '' ) ),
		);
		if ( '' === $default['code'] ) {
			$errors[]        = __( 'The default language code is not a valid hreflang code (e.g. "cs" or "cs-CZ"). The previous value was kept.', 'kybernaut-multilingual' );
			$default['code'] = $previous['default']['code'];
		}
		if ( '' === $default['label'] ) {
			$default['label'] = strtoupper( $default['code'] );
		}

		$seen      = array( $default['code'] => true );
		$languages = array();

		foreach ( (array) ( $input['languages'] ?? array() ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$raw_code = trim( (string) ( $row['code'] ?? '' ) );
			if ( '' === $raw_code ) {
				continue; // An empty code removes the row.
			}

			$code   = self::sanitize_code( $raw_code );
			$locale = self::sanitize_locale( $row['locale'] ?? '' );
			$label  = sanitize_text_field( (string) ( $row['label'] ?? '' ) );

			if ( '' === $code ) {
				/* translators: %s: submitted language code */
				$errors[] = sprintf( __( '"%s" is not a valid hreflang code, the row was skipped.', 'kybernaut-multilingual' ), esc_html( $raw_code ) ); // Core prints settings errors unescaped.
				continue;
			}
			if ( isset( $seen[ $code ] ) ) {
				/* translators: %s: language code */
				$errors[] = sprintf( __( 'Language "%s" is listed more than once, the duplicate was skipped.', 'kybernaut-multilingual' ), $code );
				continue;
			}

			$seen[ $code ] = true;
			$languages[]   = array(
				'code'   => $code,
				'label'  => '' === $label ? strtoupper( $code ) : $label,
				'locale' => $locale,
			);
		}

		foreach ( $errors as $i => $message ) {
			add_settings_error( Settings::OPTION, 'kbnt_ml_' . $i, $message, 'warning' );
		}

		return array(
			'default'   => $default,
			'languages' => $languages,
			'missing'   => Settings::sanitize_missing( $input['missing'] ?? '' ),
		);
	}

	/**
	 * Normalise an hreflang code: "en", "en-GB", "zh-Hant". Empty when invalid.
	 *
	 * @param mixed $code Raw code.
	 * @return string
	 */
	private static function sanitize_code( $code ) {
		$code = trim( (string) $code );
		if ( ! preg_match( '/^([a-zA-Z]{2,3})(?:-([a-zA-Z]{2}|[a-zA-Z]{4}|[0-9]{3}))?$/', $code, $m ) ) {
			return '';
		}
		$code = strtolower( $m[1] );
		if ( isset( $m[2] ) ) {
			$code .= '-' . ( 4 === strlen( $m[2] ) ? ucfirst( strtolower( $m[2] ) ) : strtoupper( $m[2] ) );
		}
		return $code;
	}

	/**
	 * Accept only installed locales; anything else falls back to en_US, which is always available.
	 *
	 * @param mixed $locale Raw locale.
	 * @return string
	 */
	private static function sanitize_locale( $locale ) {
		$locale = (string) $locale;
		return in_array( $locale, get_available_languages(), true ) ? $locale : 'en_US';
	}

	/**
	 * Render the settings screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::get();
		$name     = Settings::OPTION;
		$rows     = array_values( $settings['languages'] );
		$rows[]   = array(
			'code'   => '',
			'label'  => '',
			'locale' => '',
		);
		$map = Map::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Multilingual', 'kybernaut-multilingual' ); ?></h1>
			<p><?php esc_html_e( 'Pages inside a language root (a top-level page marked with a language in its "Language" box) are in that language. Link each translated page to its counterpart in the same box.', 'kybernaut-multilingual' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( self::SLUG ); ?>

				<h2><?php esc_html_e( 'Default language', 'kybernaut-multilingual' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="kbnt-ml-default-code"><?php esc_html_e( 'Hreflang code', 'kybernaut-multilingual' ); ?></label></th>
						<td><input id="kbnt-ml-default-code" class="small-text" type="text" name="<?php echo esc_attr( $name ); ?>[default][code]" value="<?php echo esc_attr( $settings['default']['code'] ); ?>" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="kbnt-ml-default-label"><?php esc_html_e( 'Switcher label', 'kybernaut-multilingual' ); ?></label></th>
						<td><input id="kbnt-ml-default-label" class="small-text" type="text" name="<?php echo esc_attr( $name ); ?>[default][label]" value="<?php echo esc_attr( $settings['default']['label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Locale', 'kybernaut-multilingual' ); ?></th>
						<td>
							<code><?php echo esc_html( get_locale() ); ?></code>
							<p class="description"><?php esc_html_e( 'The site language from Settings → General.', 'kybernaut-multilingual' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Other languages', 'kybernaut-multilingual' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Clear the code to remove a language. Fill the empty row and save to add one.', 'kybernaut-multilingual' ); ?></p>
				<table class="widefat striped" style="max-width:48em">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Hreflang code', 'kybernaut-multilingual' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Switcher label', 'kybernaut-multilingual' ); ?></th>
							<th scope="col"><?php esc_html_e( 'WordPress locale', 'kybernaut-multilingual' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Language root', 'kybernaut-multilingual' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $i => $row ) : ?>
							<tr>
								<td><input class="small-text" type="text" placeholder="en" aria-label="<?php esc_attr_e( 'Hreflang code', 'kybernaut-multilingual' ); ?>" name="<?php echo esc_attr( "{$name}[languages][{$i}][code]" ); ?>" value="<?php echo esc_attr( $row['code'] ); ?>"></td>
								<td><input class="small-text" type="text" placeholder="EN" aria-label="<?php esc_attr_e( 'Switcher label', 'kybernaut-multilingual' ); ?>" name="<?php echo esc_attr( "{$name}[languages][{$i}][label]" ); ?>" value="<?php echo esc_attr( $row['label'] ); ?>"></td>
								<td>
									<?php
									wp_dropdown_languages(
										array(
											'id'        => "kbnt-ml-locale-{$i}",
											'name'      => "{$name}[languages][{$i}][locale]",
											'selected'  => $row['locale'],
											'languages' => get_available_languages(),
											'show_available_translations' => false,
											'explicit_option_en_us'       => true,
										)
									);
									?>
								</td>
								<td>
									<?php
									if ( isset( $map['roots'][ $row['code'] ] ) ) {
										$root = $map['roots'][ $row['code'] ];
										printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $root ) ), esc_html( get_the_title( $root ) ) );
									} elseif ( '' !== $row['code'] ) {
										esc_html_e( 'Not set yet', 'kybernaut-multilingual' );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Language switcher', 'kybernaut-multilingual' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Missing translation', 'kybernaut-multilingual' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[missing]" value="<?php echo esc_attr( Settings::MISSING_HIDE ); ?>" <?php checked( $settings['missing'], Settings::MISSING_HIDE ); ?>> <?php esc_html_e( 'Hide the language', 'kybernaut-multilingual' ); ?></label><br>
								<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[missing]" value="<?php echo esc_attr( Settings::MISSING_HOME ); ?>" <?php checked( $settings['missing'], Settings::MISSING_HOME ); ?>> <?php esc_html_e( 'Link to the language home (its root page, or the site homepage)', 'kybernaut-multilingual' ); ?></label>
							</fieldset>
							<p class="description">
								<?php
								printf(
									/* translators: 1: block name, 2: shortcode */
									esc_html__( 'Add the switcher with the %1$s block or the %2$s shortcode.', 'kybernaut-multilingual' ),
									'<strong>' . esc_html__( 'Language switcher', 'kybernaut-multilingual' ) . '</strong>',
									'<code>[kybernaut_ml_switcher]</code>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
