<?php
/**
 * Plugin Name:       Kybernaut Multilingual
 * Plugin URI:        https://github.com/vyskoczilova/kybernaut-multilingual
 * Description:       Lightweight translations of a few pages: nest them under a language root page (e.g. /en/), link them to their counterparts and get hreflang, locale switching and a language switcher.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Karolína Vyskočilová
 * Author URI:        https://kybernaut.cz
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kybernaut-multilingual
 * Domain Path:       /languages
 *
 * @package Kybernaut\Multilingual
 * @author  Karolína Vyskočilová (https://kybernaut.cz)
 * @license GPL-3.0-or-later
 *
 * Copyright (C) 2026 Karolína Vyskočilová
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version. It is distributed WITHOUT ANY WARRANTY; see the
 * LICENSE file or <https://www.gnu.org/licenses/>.
 */

namespace Kybernaut\Multilingual;

defined( 'ABSPATH' ) || exit;

const VERSION     = '1.0.0';
const PLUGIN_FILE = __FILE__;
const GITHUB_REPO = 'https://github.com/vyskoczilova/kybernaut-multilingual/';

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-map.php';
require_once __DIR__ . '/includes/class-context.php';
require_once __DIR__ . '/includes/class-frontend.php';
require_once __DIR__ . '/includes/class-switcher.php';

Settings::register_hooks();
Map::register_hooks();
Frontend::register_hooks();
Switcher::register_hooks();

if ( is_admin() ) {
	require_once __DIR__ . '/includes/class-settings-page.php';
	require_once __DIR__ . '/includes/class-admin.php';
	Settings_Page::register_hooks();
	Admin::register_hooks();
}

if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once __DIR__ . '/includes/class-updater.php';
	Updater::init();
}

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'kybernaut-multilingual', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
	}
);

register_activation_hook( __FILE__, array( Settings::class, 'activate' ) );
