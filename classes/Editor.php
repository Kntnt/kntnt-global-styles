<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

use LogicException;

// Prevent direct file access for security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles CSS editing functionality in the block editor.
 *
 * Manages CSS hint parsing, block editor asset loading, AJAX operations
 * for saving CSS, and file system operations for static CSS generation.
 */
final class Editor {

	/**
	 * Retrieves CSS class hints available for the class selector dropdown.
	 *
	 * Parses @hint annotations from the stored CSS and applies filters
	 * to allow other plugins to modify the available suggestions.
	 *
	 * @return array<string, string> Associative array of class names and descriptions.
	 */
	public function get_available_hints(): array {
		$css_content = Plugin::get_css();
		if ( empty( $css_content ) ) {
			return [];
		}

		$hints = $this->parse_hints_from_css( $css_content );

		// Allow other plugins to modify or add hints
		return apply_filters( 'kntnt-global-styles-hints', $hints );
	}

	/**
	 * Parses @hint annotations from CSS content using exact regex from documentation.
	 *
	 * Extracts class names and optional descriptions from special comment
	 * annotations that follow the pattern: @hint classname | description
	 *
	 * @param string $css The CSS content to parse for hints.
	 *
	 * @return array<string, string> Array mapping class names to descriptions.
	 */
	private function parse_hints_from_css( string $css ): array {

		$hints = [];

		// Use the exact regex pattern documented in README.md - do not modify
		$pattern = '/^\s*\/?\*+\s@hint\s+(?P<name>\S+)\s*(?:\|\s*(?P<description>.*?)\s*)?(?:\*\/.*)?$/m';

		if ( preg_match_all( $pattern, $css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$class_name = trim( $match['name'] );
				$description = isset( $match['description'] ) ? trim( $match['description'] ) : '';

				// Validate class name follows CSS naming conventions
				if ( ! empty( $class_name ) && preg_match( '/^[a-zA-Z][\w-]*$/', $class_name ) ) {
					$hints[ $class_name ] = $description;
				}
			}
		}

		return $hints;

	}

	/**
	 * Enqueues JavaScript and CSS assets for the block editor.
	 *
	 * Loads the React components, styles, and PHP data needed for the
	 * Global Styles panel and CSS editor modal functionality.
	 *
	 * @return void
	 * @throws LogicException If built assets are missing.
	 */
	public function enqueue_block_editor_assets(): void {

		$assets_file = Plugin::get_plugin_dir() . 'js/index.asset.php';

		// Ensure the plugin has been built before use
		if ( ! is_readable( $assets_file ) ) {
			throw new LogicException( 'Missing built assets. Execute `npm run build` to generate required files.' );
		}

		// Load WordPress-generated asset metadata
		$assets_meta = include $assets_file;

		// Enqueue main editor JavaScript with proper dependencies
		$js_handle = Plugin::get_slug() . '-script';
		wp_enqueue_script( $js_handle, Plugin::get_plugin_url() . 'js/index.js', $assets_meta['dependencies'], $assets_meta['version'], true );

		// Enqueue editor-specific styles
		wp_enqueue_style( Plugin::get_slug() . '-style', Plugin::get_plugin_url() . 'css/index.css', [], $assets_meta['version'] );

		// Enable WordPress translation system for the JavaScript
		wp_set_script_translations( $js_handle, Plugin::get_l10n_domain(), Plugin::get_plugin_dir() . Plugin::get_l10n_dir() );

		// Pass PHP data to JavaScript components via wp_localize_script
		wp_localize_script( $js_handle, 'kntnt_global_styles_data', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'kntnt-global-styles-nonce' ),
			'css_content' => Plugin::get_css(),
			'available_hints' => $this->get_available_hints(),
		] );
	}

	/**
	 * Handles AJAX requests to save CSS from the editor modal.
	 *
	 * Processes both preview updates (temporary) and full saves (persistent).
	 * Validates user permissions and nonce before processing the request.
	 *
	 * @return void
	 */
	public function handle_ajax_save(): void {
		// Verify request authenticity
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'kntnt-global-styles-nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'kntnt-global-styles' ),
				'code' => AjaxStatus::INVALID_NONCE->value,
			] );
		}

		// Verify user has permission to edit theme options
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Insufficient permissions.', 'kntnt-global-styles' ),
				'code' => AjaxStatus::INSUFFICIENT_PERMISSIONS->value,
			] );
		}

		// Extract request parameters
		$css_content = $_POST['css_content'] ?? '';
		$should_persist = isset( $_POST['persist'] ) && $_POST['persist'] === 'true';

		// Apply pre-save filter for CSS modification/sanitization
		$css_content = apply_filters( 'kntnt-global-styles-pre-save', $css_content );

		if ( $should_persist ) {
			// Full save - update database and generate static file
			if ( $this->save_css_content( $css_content ) ) {
				// Clear cached file information after successful save
				Assets::clear_file_cache();

				// Get updated hints from the newly saved CSS
				$updated_hints = $this->get_available_hints();

				wp_send_json_success( [
					'message' => __( 'CSS saved successfully.', 'kntnt-global-styles' ),
					'css_content' => $css_content,
					'available_hints' => $updated_hints,
					'persisted' => true,
				] );
			}
			else {
				wp_send_json_error( [
					'message' => __( 'Failed to save CSS.', 'kntnt-global-styles' ),
					'code' => AjaxStatus::SAVE_FAILED->value,
				] );
			}
		}
		else {
			// Preview save - only parse hints for live preview without persistence
			$updated_hints = $this->parse_hints_from_css( $css_content );
			$updated_hints = apply_filters( 'kntnt-global-styles-hints', $updated_hints );

			wp_send_json_success( [
				'message' => __( 'CSS updated in preview. Save the document to make changes permanent.', 'kntnt-global-styles' ),
				'css_content' => $css_content,
				'available_hints' => $updated_hints,
				'persisted' => false,
			] );
		}
	}

	/**
	 * Saves CSS content to both database and static file system.
	 *
	 * Handles the dual-storage approach: raw CSS in database for editing,
	 * minified CSS as static file for frontend performance.
	 *
	 * @param string $css The CSS content to save.
	 *
	 * @return bool True if both database and file operations succeeded.
	 */
	private function save_css_content( string $css ): bool {
		// Sanitize CSS for safe storage
		$sanitized_css = $this->sanitize_css( $css );

		// Save to database for editor access
		$db_saved = Plugin::set_css( $sanitized_css );

		// Generate static file for frontend performance
		$file_saved = $this->save_css_to_file( $sanitized_css );

		return $db_saved && $file_saved;
	}

	/**
	 * Sanitizes CSS content for safe storage and output.
	 *
	 * Removes potentially dangerous content while preserving valid CSS.
	 * Protects against injection attacks and malformed input.
	 *
	 * @param string $css The raw CSS string from user input.
	 *
	 * @return string The sanitized CSS string.
	 */
	private function sanitize_css( string $css ): string {
		// Remove leading and trailing whitespace
		$css = trim( $css );

		// Prevent null byte injection attacks
		$css = str_replace( "\0", '', $css );

		// Strip HTML and PHP tags while preserving CSS syntax
		$css = wp_strip_all_tags( $css );

		return $css;
	}

	/**
	 * Saves CSS content to a static file in the uploads directory.
	 *
	 * Creates a minified CSS file that can be served directly by the web
	 * server for optimal frontend performance with browser caching.
	 *
	 * @param string $css_content The CSS content to save.
	 *
	 * @return bool True if file was successfully created.
	 */
	private function save_css_to_file( string $css_content ): bool {
		global $wp_filesystem;

		// Initialize WordPress filesystem API
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			error_log( 'Kntnt Global Styles: Failed to initialize WP_Filesystem' );
			return false;
		}

		// Get target paths for CSS file
		$target_dir = Plugin::get_css_dir();
		$target_file = Plugin::get_css_path();

		// Ensure upload directory is available
		if ( empty( $target_dir ) || empty( $target_file ) ) {
			error_log( 'Kntnt Global Styles: Upload directory not available' );
			return false;
		}

		// Create plugin directory in uploads if it doesn't exist
		if ( ! $wp_filesystem->is_dir( $target_dir ) ) {
			if ( ! $wp_filesystem->mkdir( $target_dir, FS_CHMOD_DIR ) ) {
				error_log( 'Kntnt Global Styles: Failed to create directory: ' . $target_dir );
				return false;
			}
		}

		// Verify directory is writable before proceeding
		if ( ! $wp_filesystem->is_writable( $target_dir ) ) {
			error_log( 'Kntnt Global Styles: Directory not writable: ' . $target_dir );
			return false;
		}

		// Apply custom minification filter or use built-in minifier
		if ( has_filter( 'kntnt-global-styles-minimize' ) ) {
			$css_content = apply_filters( 'kntnt-global-styles-minimize', $css_content );
		}
		else {
			$css_content = $this->minify_css( $css_content );
		}

		// Write minified CSS to static file
		$result = $wp_filesystem->put_contents( $target_file, $css_content, FS_CHMOD_FILE );
		if ( ! $result ) {
			error_log( 'Kntnt Global Styles: Failed to write CSS file: ' . $target_file );
			return false;
		}

		return true;
	}

	/**
	 * Minifies CSS content for production use.
	 *
	 * Removes comments, whitespace, and unnecessary syntax to reduce
	 * file size while maintaining CSS functionality.
	 *
	 * @param string $css The CSS content to minify.
	 *
	 * @return string The minified CSS.
	 */
	private function minify_css( string $css ): string {
		// Remove CSS comments (/* ... */)
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove line breaks and tabs
		$css = str_replace( [ "\r\n", "\r", "\n", "\t" ], '', $css );

		// Remove spaces around CSS syntax characters
		$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );

		// Collapse multiple spaces into single spaces
		$css = preg_replace( '/\s+/', ' ', $css );

		// Remove unnecessary trailing semicolons before closing braces
		$css = str_replace( ';}', '}', $css );

		return trim( $css );
	}

}