<?php
/**
 * Remove all plugin data on uninstall (ADR 0010).
 *
 * @package Kybernaut\Multilingual
 */

namespace Kybernaut\Multilingual;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-map.php';

delete_option( Settings::OPTION );
delete_transient( Map::TRANSIENT );
delete_site_option( 'external_updates-kybernaut-multilingual' );
wp_clear_scheduled_hook( 'puc_cron_check_updates-kybernaut-multilingual' );
delete_post_meta_by_key( Map::META_ROOT );
delete_post_meta_by_key( Map::META_LINK );
