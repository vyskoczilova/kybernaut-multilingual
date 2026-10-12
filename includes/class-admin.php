<?php
/**
 * Page editing UI: "Language" meta box and Pages list column.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI for language roots and translation links (ADR 0001, 0003, 0010).
 */
final class Admin {

	const NONCE = 'kbnt_ml_page';

	/** Statuses offered as translation counterparts. */
	const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Register admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'add_meta_box' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'manage_page_posts_columns', array( __CLASS__, 'add_column' ) );
		add_action( 'manage_page_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
	}

	/**
	 * Add the "Language" box to the page editor sidebar.
	 */
	public static function add_meta_box() {
		add_meta_box( 'kbnt-ml-language', __( 'Language', 'kybernaut-multilingual' ), array( __CLASS__, 'render_meta_box' ), 'page', 'side' );
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Edited page.
	 */
	public static function render_meta_box( $post ) {
		$languages = Settings::languages();
		if ( ! $languages ) {
			printf(
				'<p>%s</p>',
				wp_kses(
					sprintf(
						/* translators: %s: settings screen URL */
						__( 'Add a language in <a href="%s">Settings → Multilingual</a> first.', 'kybernaut-multilingual' ),
						esc_url( admin_url( 'options-general.php?page=' . Settings_Page::SLUG ) )
					),
					array( 'a' => array( 'href' => array() ) )
				)
			);
			return;
		}

		wp_nonce_field( self::NONCE, self::NONCE );

		$lang = Map::language_of( $post->ID );

		if ( 0 === (int) $post->post_parent ) {
			self::render_root_field( $post, $languages );
		}

		if ( Settings::is_extra( $lang ) || 'auto-draft' === $post->post_status ) {
			self::render_link_field( $post, $lang );
		} else {
			self::render_translations( $post, Settings::labels() );
		}
	}

	/**
	 * "Language root" select for top-level pages.
	 *
	 * @param \WP_Post $post      Edited page.
	 * @param array    $languages Extra languages.
	 */
	private static function render_root_field( $post, $languages ) {
		$current = get_post_meta( $post->ID, Map::META_ROOT, true );
		?>
		<p>
			<label for="kbnt-ml-root"><strong><?php esc_html_e( 'Language root', 'kybernaut-multilingual' ); ?></strong></label><br>
			<select id="kbnt-ml-root" name="kbnt_ml_root" style="width:100%">
				<option value=""><?php esc_html_e( '— Not a language root —', 'kybernaut-multilingual' ); ?></option>
				<?php foreach ( $languages as $code => $language ) : ?>
					<?php $taken = Map::root_taken( $code, $post->ID ); ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $current, $code ); ?> <?php disabled( $taken ); ?>>
						<?php
						echo esc_html(
							$taken
								/* translators: %s: language label */
								? sprintf( __( '%s (used by another page)', 'kybernaut-multilingual' ), $language['label'] )
								: $language['label']
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'This page and all its subpages will be in the selected language.', 'kybernaut-multilingual' ); ?></span>
		</p>
		<?php
	}

	/**
	 * "Translation of" select for pages inside a language root.
	 *
	 * @param \WP_Post $post Edited page.
	 * @param string   $lang Page language.
	 */
	private static function render_link_field( $post, $lang ) {
		$current = (string) get_post_meta( $post->ID, Map::META_LINK, true );
		$is_translated = Settings::is_extra( $lang );
		$pages   = get_pages(
			array(
				'post_status' => self::STATUSES,
				'sort_column' => 'menu_order,post_title',
			)
		);

		$depth = array();
		?>
		<p>
			<label for="kbnt-ml-link"><strong><?php esc_html_e( 'Translation of', 'kybernaut-multilingual' ); ?></strong></label><br>
			<select id="kbnt-ml-link" name="kbnt_ml_translation_of" style="width:100%">
				<option value=""><?php esc_html_e( '— None —', 'kybernaut-multilingual' ); ?></option>
				<option value="<?php echo esc_attr( Map::HOME ); ?>" <?php selected( $current, Map::HOME ); ?> <?php disabled( $is_translated && Map::link_taken( Map::HOME, $lang, $post->ID ) ); ?>>
					<?php esc_html_e( 'Site homepage', 'kybernaut-multilingual' ); ?>
				</option>
				<?php
				foreach ( $pages as $page ) {
					$depth[ $page->ID ] = isset( $depth[ $page->post_parent ] ) ? $depth[ $page->post_parent ] + 1 : 0;
					if ( Settings::is_extra( Map::language_of( $page->ID ) ) || $page->ID === $post->ID || ! current_user_can( 'read_post', $page->ID ) ) {
						continue;
					}
					$taken  = $is_translated && Map::link_taken( $page->ID, $lang, $post->ID );
					$title  = '' === $page->post_title ? '#' . $page->ID : $page->post_title;
					$status = get_post_status_object( $page->post_status );
					if ( 'publish' !== $page->post_status && $status ) {
						/* translators: 1: page title, 2: post status label, e.g. Draft */
						$title = sprintf( __( '%1$s (%2$s)', 'kybernaut-multilingual' ), $title, $status->label );
					}
					if ( $taken ) {
						/* translators: %s: page title */
						$title = sprintf( __( '%s (already translated)', 'kybernaut-multilingual' ), $title );
					}
					$title = str_repeat( '— ', $depth[ $page->ID ] ) . $title;
					printf(
						'<option value="%1$d" %2$s %3$s>%4$s</option>',
						(int) $page->ID,
						selected( $current, (string) $page->ID, false ),
						disabled( $taken, true, false ),
						esc_html( $title )
					);
				}
				?>
			</select>
			<span class="description">
				<?php
				echo esc_html(
					$is_translated
						? __( 'The page in the default language this page translates.', 'kybernaut-multilingual' )
						: __( 'Only used when this page is inside a language root. Save and reload after changing the parent page.', 'kybernaut-multilingual' )
				);
				?>
			</span>
		</p>
		<?php
	}

	/**
	 * Read-only list of translations of a default-language page.
	 *
	 * @param \WP_Post $post   Edited page.
	 * @param array    $labels Language labels.
	 */
	private static function render_translations( $post, $labels ) {
		$translations = Map::translations_of( $post->ID );

		echo '<p><strong>' . esc_html__( 'Translations', 'kybernaut-multilingual' ) . '</strong><br>';
		if ( ! $translations ) {
			esc_html_e( 'None. To add one, create a page inside a language root and link it to this page.', 'kybernaut-multilingual' );
		}
		foreach ( $translations as $code => $id ) {
			printf(
				'%1$s: <a href="%2$s">%3$s</a><br>',
				esc_html( $labels[ $code ] ?? $code ),
				esc_url( get_edit_post_link( $id ) ),
				esc_html( get_the_title( $id ) )
			);
		}
		echo '</p>';
	}

	/**
	 * Save the meta box fields.
	 *
	 * @param int      $post_id Page ID.
	 * @param \WP_Post $post    Page.
	 */
	public static function save( $post_id, $post ) {
		if (
			! isset( $_POST[ self::NONCE ] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| wp_is_post_revision( $post_id )
			|| ! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		// Language root: only top-level pages, configured languages, one page per language.
		if ( isset( $_POST['kbnt_ml_root'] ) ) {
			$code = sanitize_text_field( wp_unslash( $_POST['kbnt_ml_root'] ) );
			if ( 0 === (int) $post->post_parent && Settings::is_extra( $code ) && ! Map::root_taken( $code, $post_id ) ) {
				update_post_meta( $post_id, Map::META_ROOT, $code );
			} else {
				delete_post_meta( $post_id, Map::META_ROOT );
			}
		}

		// Translation link: only pages inside a root, to a default-language page or the homepage, one per language.
		if ( isset( $_POST['kbnt_ml_translation_of'] ) ) {
			$raw    = sanitize_text_field( wp_unslash( $_POST['kbnt_ml_translation_of'] ) );
			$target = Map::HOME === $raw ? Map::HOME : absint( $raw );
			$lang   = Map::language_of( $post_id ); // Reflects a root change above.

			$valid = $target && Settings::is_extra( $lang ) && ! Map::link_taken( $target, $lang, $post_id );
			if ( $valid && is_int( $target ) ) {
				$page  = get_post( $target );
				$valid = $page && 'page' === $page->post_type && $target !== $post_id
					&& in_array( $page->post_status, self::STATUSES, true ) && ! Settings::is_extra( Map::language_of( $target ) );
			}

			if ( $valid ) {
				update_post_meta( $post_id, Map::META_LINK, (string) $target );
			} else {
				delete_post_meta( $post_id, Map::META_LINK );
			}
		}
	}

	/**
	 * Add the "Language" column to the Pages list.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public static function add_column( $columns ) {
		if ( ! Settings::languages() ) {
			return $columns;
		}
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['kbnt_ml_language'] = __( 'Language', 'kybernaut-multilingual' );
			}
		}
		return $new;
	}

	/**
	 * Render the "Language" column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Page ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'kbnt_ml_language' !== $column ) {
			return;
		}

		$map    = Map::get();
		$labels = Settings::labels();
		$lang   = Map::language_of( $post_id );
		$parts  = array();

		static $primed = false;
		if ( ! $primed ) {
			// Load every linked page once instead of one query per row.
			$ids = array_merge( array_keys( $map['links'] ), array_filter( $map['links'], 'is_int' ) );
			_prime_post_caches( array_unique( $ids ), false, false );
			$primed = true;
		}

		if ( in_array( $post_id, $map['roots'], true ) ) {
			$parts[] = esc_html__( 'language root', 'kybernaut-multilingual' );
		}

		if ( isset( $map['lang'][ $post_id ] ) ) {
			if ( isset( $map['links'][ $post_id ] ) ) {
				$target  = $map['links'][ $post_id ];
				/* translators: %s: linked page title or "Site homepage" */
				$parts[] = sprintf( esc_html__( 'translation of %s', 'kybernaut-multilingual' ), self::page_link( $target ) );
			} else {
				$parts[] = '<em>' . esc_html__( 'not linked', 'kybernaut-multilingual' ) . '</em>';
			}
		} else {
			foreach ( Map::translations_of( $post_id ) as $code => $id ) {
				$parts[] = esc_html( $labels[ $code ] ?? $code ) . ': ' . self::page_link( $id );
			}
		}

		echo '<strong>' . esc_html( $labels[ $lang ] ?? $lang ) . '</strong>';
		if ( $parts ) {
			echo ' · ' . implode( ' · ', $parts ); // phpcs:ignore WordPress.Security.EscapeOutput -- Parts are escaped above.
		}
	}

	/**
	 * Escaped edit link for a map key.
	 *
	 * @param int|string $id Page ID or 'home'.
	 * @return string
	 */
	private static function page_link( $id ) {
		if ( Map::HOME === $id ) {
			return esc_html__( 'Site homepage', 'kybernaut-multilingual' );
		}
		return sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $id ) ), esc_html( get_the_title( $id ) ) );
	}
}
