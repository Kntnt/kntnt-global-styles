<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

// Prevent direct file access for security.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages CSS asset enqueueing for frontend and block editor.
 *
 * Handles loading the custom global stylesheet on the frontend and injecting
 * CSS into the block editor for live preview functionality during editing.
 */
final class Assets {

	/**
	 * Cached CSS file information to avoid repeated filesystem checks.
	 *
	 * @var array{exists: bool, path: string, url: string, version: int}|null
	 */
	private static ?array $css_file_info = null;

	/**
	 * Enqueues the global stylesheet on the website frontend.
	 *
	 * Only enqueues if the CSS file exists and has content to avoid
	 * unnecessary HTTP requests for empty stylesheets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_style(): void {
		$file_info = $this->get_css_file_info();

		// Only load stylesheet if file exists and has content
		if ( $file_info['exists'] ) {
			wp_enqueue_style( Plugin::get_slug() . '-global-styles', $file_info['url'], [], (string) $file_info['version'] );
		}
	}

	/**
	 * Adds custom CSS to the block editor for live preview functionality.
	 *
	 * Injects the current CSS content from the database into the editor
	 * using a JavaScript function that handles both the main editor
	 * frame and any nested iframes.
	 *
	 * @return void
	 */
	public function add_custom_css_to_block_editor(): void {
		// Get the raw CSS content from database for editor injection
		$css_content = Plugin::get_css();
		$safe_css = wp_strip_all_tags( $css_content );

		// Defer script loading until block editor assets are being enqueued
		add_action( 'enqueue_block_editor_assets', function () use ( $safe_css ) {
			$live_preview_handle = Plugin::get_slug() . '-live-preview';

			// Load the JavaScript function that handles CSS injection
			wp_enqueue_script( $live_preview_handle, Plugin::get_plugin_url() . 'js/live-preview.js', [], Plugin::get_plugin_data()['Version'] ?? '1.1.0', true );

			// Initialize editor styles with current CSS on page load
			wp_add_inline_script( $live_preview_handle, sprintf( 'document.addEventListener("DOMContentLoaded", function() { 
						if (window.kntntUpdateEditorStyles) { 
							window.kntntUpdateEditorStyles(%s); 
						} 
					});', wp_json_encode( $safe_css ) ) );
		} );
	}

	/**
	 * Retrieves cached CSS file information.
	 *
	 * Caches file existence, path, URL, and modification time to avoid
	 * repeated filesystem operations during a single request.
	 *
	 * @return array{exists: bool, path: string, url: string, version: int} File information.
	 */
	private function get_css_file_info(): array {
		// Return cached data if available
		if ( self::$css_file_info === null ) {
			$css_file_path = Plugin::get_css_path();
			$css_file_url = Plugin::get_css_url();

			// Check if file exists and contains content
			$exists = ! empty( $css_file_path ) && file_exists( $css_file_path ) && filesize( $css_file_path ) > 0;

			// Use modification time as cache-busting version parameter
			$version = $exists ? filemtime( $css_file_path ) : 0;

			// Cache for subsequent calls within the same request
			self::$css_file_info = [
				'exists' => $exists,
				'path' => $css_file_path,
				'url' => $css_file_url,
				'version' => $version,
			];
		}

		return self::$css_file_info;
	}

	/**
	 * Clears the cached file information.
	 *
	 * Called after file operations to ensure fresh data on next access.
	 * Necessary when CSS file is modified during the same request.
	 *
	 * @return void
	 */
	public static function clear_file_cache(): void {
		self::$css_file_info = null;
	}

}