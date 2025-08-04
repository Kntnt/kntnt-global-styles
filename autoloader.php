<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR-4 compliant autoloader for plugin classes.
 *
 * Automatically loads classes from the classes/ directory when they are first used.
 * Maps class names directly to file names within the plugin namespace.
 *
 * @param string $class_name The fully qualified class name to load.
 *
 * @return void
 */
spl_autoload_register( function ( string $class_name ): void {

	// Only handle classes within our plugin namespace
	if ( ! str_starts_with( $class_name, __NAMESPACE__ . '\\' ) ) {
		return;
	}

	// Strip the namespace prefix to get the relative class name
	$relative_class_name = substr( $class_name, strlen( __NAMESPACE__ . '\\' ) );

	// Convert namespace separators to directory separators for sub-namespaces
	$relative_class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class_name );

	// Construct the expected file path
	$file_path = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $relative_class_name . '.php';

	// Load the file if it exists and is readable
	if ( is_readable( $file_path ) ) {
		require_once $file_path;
	}

} );