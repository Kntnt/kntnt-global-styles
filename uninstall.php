<?php

/**
 * Plugin uninstall script for Kntnt Global Styles.
 *
 * Removes all plugin data when the plugin is deleted through WordPress admin.
 * Cleans up database options and removes generated CSS files to ensure
 * no traces remain on the system after uninstallation.
 */

// Security check - ensure this script is called during plugin uninstall only
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Extract plugin slug and option name from the main plugin file path
$plugin_file = basename( WP_UNINSTALL_PLUGIN, '.php' );
$option_name = str_replace( '-', '_', $plugin_file );

// Remove plugin option from WordPress database
delete_option( $option_name );

// Clean up generated CSS file and plugin directory in uploads
$plugin_dir_path = wp_upload_dir()['basedir'] . '/' . $plugin_file;
$css_file_path = $plugin_dir_path . '/' . $plugin_file . '.css';

// Remove files and directory if they exist
if ( is_dir( $plugin_dir_path ) ) {

	// Delete the generated CSS file first
	if ( file_exists( $css_file_path ) ) {
		@unlink( $css_file_path );
	}

	// Remove the plugin directory (will only succeed if empty)
	@rmdir( $plugin_dir_path );

}