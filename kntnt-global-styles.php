<?php
/**
 * Plugin Name:       Kntnt Global Styles
 * Plugin URI:        https://github.com/Kntnt/kntnt-global-styles
 * Description:       Manages a global CSS file editable in the block editor.
 * Version:           1.0.0
 * Author:            Thomas Barregren
 * Author URI:        https://www.kntnt.com/
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires PHP:      8.3
 * Requires at least: 6.8
 * Text Domain:       kntnt-global-styles
 * Domain Path:       /languages
 */

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

// Prevent direct file access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register the autoloader for the plugin's classes.
require_once __DIR__ . '/autoloader.php';

// Set the plugin file path for the Plugin class to use.
Plugin::set_plugin_file( __FILE__ );

// Initialize the plugin.
Plugin::get_instance();
