<?php

/**
 * Plugin uninstall script.
 *
 * Removes all plugin data when the plugin is deleted through WordPress admin.
 * Cleans up database options and removes generated CSS files.
 *
 * @package Kntnt\Global_Styles
 * @since   2.0.0
 */

// Security check - ensure this is called during plugin uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Determine plugin slug and option name from the main plugin file
$plugin_file = basename( WP_UNINSTALL_PLUGIN, '.php' );
$option_name = str_replace( '-', '_', $plugin_file );

// Remove plugin option from WordPress database
delete_option( $option_name );

// Clean up generated CSS file and directory
$plugin_dir_path = wp_upload_dir()['basedir'] . '/' . $plugin_file;
$css_file_path = $plugin_dir_path . '/' . $plugin_file . '.css';

// Remove files and directory if they exist
if ( is_dir( $plugin_dir_path ) ) {

	// Delete the CSS file first
	if ( file_exists( $css_file_path ) ) {
		@unlink( $css_file_path );
	}

	// Remove the plugin directory (will only succeed if empty)
	@rmdir( $plugin_dir_path );

}