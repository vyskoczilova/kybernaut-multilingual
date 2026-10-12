<?php
/**
 * Updates from GitHub Releases.
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the bundled Plugin Update Checker to the public GitHub repo (ADR 0008).
 */
final class Updater {

	/**
	 * Start the update checker. Loaded only in admin, cron and WP-CLI.
	 */
	public static function init() {
		$loader = dirname( PLUGIN_FILE ) . '/vendor/plugin-update-checker/plugin-update-checker.php';
		if ( ! is_readable( $loader ) ) {
			return;
		}
		require_once $loader;

		$checker = PucFactory::buildUpdateChecker( GITHUB_REPO, PLUGIN_FILE, 'kybernaut-multilingual' );
		$checker->getVcsApi()->enableReleaseAssets( '/kybernaut-multilingual\.zip$/' );
	}
}
