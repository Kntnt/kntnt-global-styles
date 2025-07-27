<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

/**
 * Main editor functionality for the CSS Global Styles.
 *
 * Handles CSS sanitization, file saving operations, and AJAX requests
 * from the block editor sidebar panel. Also includes CSS minification functionality.
 *
 * @package Kntnt\Global_Styles
 * @since   2.0.0
 */
final class Editor {

	/**
	 * A simple function to minify a CSS string.
	 *
	 * Removes comments, unnecessary whitespace, and optimizes formatting
	 * for smaller file sizes while maintaining CSS functionality.
	 *
	 * @param string $css The CSS code to be minified.
	 *
	 * @return string The minified CSS code.
	 * @since 2.0.0
	 *
	 */
	public static function minifier( string $css ): string {

		// Remove all CSS comments (/* ... */) including multi-line comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove line breaks and tabs for compact output
		$css = str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $css );

		// Remove spaces around CSS syntax characters for compactness
		$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );

		// Collapse multiple spaces into single spaces (needed for some CSS values)
		$css = preg_replace( '/\s+/', ' ', $css );

		// Remove unnecessary trailing semicolons before closing braces
		$css = str_replace( ';}', '}', $css );

		// Clean up any remaining whitespace at start/end
		return trim( $css );

	}

	/**
	 * Handles the AJAX request to save CSS from the sidebar panel.
	 *
	 * Processes the CSS content, saves it to database and file, then returns
	 * a JSON response with success/error information for the frontend.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function handle_ajax_save(): void {
		check_ajax_referer( 'kntnt-global-styles-save-nonce', 'nonce' );

		if ( ! current_user_can( Plugin::get_capability() ) ) {
			wp_send_json_error( [
				'message' => __( 'Unauthorized use.', 'kntnt-global-styles' ),
				'code' => 'unauthorized',
			] );
		}

		$css_content = $_POST['css_content'] ?? '';

		if ( $this->save_css_content( $css_content ) ) {

			// Clear file cache to ensure fresh data
			Assets::clear_file_cache();

			// Trigger cache flush if available
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

			wp_send_json_success( [
				'message' => __( 'CSS saved successfully.', 'kntnt-global-styles' ),
				'css_content' => $css_content,
				'file_info' => [
					'css_file_url' => Plugin::get_css_url(),
					'css_file_path' => Plugin::get_css_path(),
					'file_exists' => file_exists( Plugin::get_css_path() ),
					'file_size' => file_exists( Plugin::get_css_path() ) ? filesize( Plugin::get_css_path() ) : 0,
				],
				'timestamp' => current_time( 'timestamp' ),
			] );
		}
		else {
			wp_send_json_error( [
				'message' => __( 'Failed to save CSS file. Please check file permissions.', 'kntnt-global-styles' ),
				'code' => 'file_save_failed',
			] );
		}
	}

	/**
	 * Saves CSS content to both database and file.
	 *
	 * Central method for processing and saving CSS content. Handles sanitization,
	 * database storage, file writing, and action hook triggering.
	 *
	 * @param string $css The CSS content to save.
	 *
	 * @return bool True on success, false on failure.
	 * @since 2.0.0
	 *
	 */
	private function save_css_content( string $css ): bool {
		// Sanitize the CSS input
		$sanitized_css = $this->sanitize_css( stripslashes( $css ) );

		// Save to database
		Plugin::set_option( [ 'css' => $sanitized_css ] );

		// Save to file
		$file_saved = $this->save_css_to_file( $sanitized_css );

		// Trigger action hook on successful save
		if ( $file_saved ) {
			do_action( 'kntnt-global-styles-saved', $sanitized_css );
		}

		return $file_saved;
	}

	/**
	 * Sanitizes a block of CSS code for safe storage and output.
	 *
	 * This function assumes the user has permission to add arbitrary CSS.
	 * However, we want to prevent accidental cross-site scripting (XSS)
	 * attacks by removing potentially malicious HTML tags rather than
	 * strictly validating the CSS syntax.
	 *
	 * @param string $css The raw CSS string from user input.
	 *
	 * @return string The sanitized CSS string, safe for output.
	 * @since 2.0.0
	 *
	 */
	private function sanitize_css( string $css ): string {

		// Remove leading and trailing whitespace for data hygiene
		$css = trim( $css );

		// Strip null bytes to prevent null byte injection attacks
		$css = str_replace( "\0", '', $css );

		// Use WordPress core function to strip all HTML and PHP tags
		// This prevents XSS attacks from malicious script tags
		$css = wp_strip_all_tags( $css );

		return $css;

	}

	/**
	 * Saves CSS content to a dedicated file in the uploads directory.
	 *
	 * Uses WordPress Filesystem API for secure file operations. Applies minification
	 * filter if available, otherwise uses built-in minifier. Creates directory
	 * structure if needed.
	 *
	 * @param string $css_content The CSS content to be saved.
	 *
	 * @return bool True on success, false on failure.
	 * @since 2.0.0
	 *
	 */
	private function save_css_to_file( string $css_content ): bool {

		global $wp_filesystem;

		// Initialize WordPress Filesystem API
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			error_log( 'Kntnt Global Styles: Failed to initialize WP_Filesystem' );
			return false;
		}

		// Get target directory and file paths
		$target_dir = Plugin::get_css_dir();
		$target_file = Plugin::get_css_path();

		// Ensure we have valid paths before proceeding
		if ( empty( $target_dir ) || empty( $target_file ) ) {
			error_log( 'Kntnt Global Styles: Upload directory not available' );
			return false;
		}

		// Create target directory if it doesn't exist
		if ( ! $wp_filesystem->is_dir( $target_dir ) ) {
			if ( ! $wp_filesystem->mkdir( $target_dir, FS_CHMOD_DIR ) ) {
				error_log( 'Kntnt Global Styles: Failed to create directory: ' . $target_dir );
				return false;
			}
		}

		// Verify directory is writable
		if ( ! $wp_filesystem->is_writable( $target_dir ) ) {
			error_log( 'Kntnt Global Styles: Directory not writable: ' . $target_dir );
			return false;
		}

		// Apply CSS processing (minification filter or built-in minifier)
		if ( has_filter( 'kntnt-global-styles-minimize' ) ) {
			// Use custom filter if available
			$css_content = apply_filters( 'kntnt-global-styles-minimize', $css_content );
		}
		else {
			// Use built-in minifier as default
			$css_content = self::minifier( $css_content );
		}

		// Write CSS content to file with proper permissions
		$result = $wp_filesystem->put_contents( $target_file, $css_content, FS_CHMOD_FILE );
		if ( ! $result ) {
			error_log( 'Kntnt Global Styles: Failed to write CSS file: ' . $target_file );
			return false;
		}

		// Clear cached file information to ensure fresh data on next request
		Assets::clear_file_cache();

		return true;

	}

}