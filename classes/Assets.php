<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

/**
 * Manages the enqueueing of CSS and JavaScript assets.
 *
 * Handles loading the custom CSS file on the frontend, adding styles to the block editor,
 * and enqueuing admin assets for the block editor sidebar panel.
 *
 * @package Kntnt\Global_Styles
 * @since   2.0.0
 */
final class Assets {

	/**
	 * Cached information about the CSS file.
	 *
	 * Contains existence status, file paths, URLs and version information
	 * to avoid repeated file system checks.
	 *
	 * @var array{exists: bool, path: string, url: string, version: int}|null
	 */
	private static ?array $css_file_info = null;

	/**
	 * Enqueues the custom stylesheet on the website's frontend.
	 *
	 * Only enqueues if the CSS file exists and has content. Uses file modification
	 * time as version for cache busting.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function enqueue_frontend_style(): void {

		$file_info = $this->get_css_file_info();

		// Only enqueue if file exists and has content
		if ( $file_info['exists'] ) {
			wp_enqueue_style( Plugin::get_slug() . '-custom', $file_info['url'], [], (string) $file_info['version'] );
		}

	}

	/**
	 * Adds the custom stylesheet to the block editor's preview iframe.
	 *
	 * This ensures that custom styles are visible when editing posts/pages
	 * in the block editor, providing a WYSIWYG experience.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function add_custom_css_to_block_editor(): void {

		// Get CSS content from database
		$css_content = Plugin::get_option( 'css' ) ?? '';

		if ( empty( $css_content ) ) {
			return;
		}

		// Add CSS to block editor via enqueue_block_editor_assets
		add_action( 'enqueue_block_editor_assets', function () use ( $css_content ) {
			// Create inline style for block editor
			wp_add_inline_style( 'wp-edit-blocks', wp_strip_all_tags( $css_content ) );

			// Also add as editor style
			wp_enqueue_style( Plugin::get_slug() . '-editor-style', 'data:text/css;base64,' . base64_encode( wp_strip_all_tags( $css_content ) ), [], Plugin::get_version() );
		} );

		// Backup method - add to admin head
		add_action( 'admin_head', function () use ( $css_content ) {
			if ( $this->is_block_editor_page() ) {
				echo '<style id="kntnt-global-styles-admin-styles">' . wp_strip_all_tags( $css_content ) . '</style>' . "\n";
			}
		} );

	}

	/**
	 * Enqueues admin assets for the block editor sidebar panel.
	 *
	 * Loads React components and styles for the block editor context only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {

		// Ensure screen functions are available
		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}
		$screen = \get_current_screen();

		// Enqueue block editor sidebar assets
		if ( $screen && $screen->is_block_editor() ) {
			$this->enqueue_block_editor_assets();
		}
	}

	/**
	 * Checks if current page is a block editor page.
	 *
	 * @return bool True if on block editor page, false otherwise.
	 * @since 2.0.0
	 *
	 */
	private function is_block_editor_page(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && $screen->is_block_editor();
	}

	/**
	 * Enqueues assets for the block editor sidebar panel.
	 *
	 * Loads React components and localizes script with necessary data
	 * for the sidebar panel functionality.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	private function enqueue_block_editor_assets(): void {
		$script_asset_path = Plugin::get_plugin_dir() . 'js/index.asset.php';
		$style_path = Plugin::get_plugin_dir() . 'js/index.css';

		if ( file_exists( $script_asset_path ) ) {
			$script_asset = require( $script_asset_path );

			wp_enqueue_script( Plugin::get_slug() . '-sidebar', Plugin::get_plugin_url() . 'js/index.js', $script_asset['dependencies'], $script_asset['version'], true );

			if ( file_exists( $style_path ) ) {
				wp_enqueue_style( Plugin::get_slug() . '-sidebar-style', Plugin::get_plugin_url() . 'js/index.css', [], $script_asset['version'] );
			}

			// Localize script with editor data
			$css_content = Plugin::get_option( 'css' ) ?? '';

			wp_localize_script( Plugin::get_slug() . '-sidebar', 'kntntEditorPanel', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'admin_url' => admin_url(),
				'nonce' => wp_create_nonce( 'kntnt-global-styles-save-nonce' ),
				'css_content' => $css_content,
				'l10n' => [
					// Basic actions
					'save' => __( 'Save', 'kntnt-global-styles' ),
					'saving' => __( 'Saving...', 'kntnt-global-styles' ),
					'cancel' => __( 'Cancel', 'kntnt-global-styles' ),

					// Messages
					'error' => __( 'Error saving styles.', 'kntnt-global-styles' ),
					'success' => __( 'Styles saved!', 'kntnt-global-styles' ),
					'unsaved_changes_message' => __( 'You have unsaved changes. Do you really want to close without saving?', 'kntnt-global-styles' ),

					// UI labels
					'open_editor' => __( 'Larger Editor', 'kntnt-global-styles' ),
					'css_editor_title' => __( 'CSS Editor', 'kntnt-global-styles' ),
					'css_label' => __( 'Custom CSS', 'kntnt-global-styles' ),
					'css_placeholder' => __( '/* Write your CSS here... */', 'kntnt-global-styles' ),
					'css_help' => __( 'The CSS will be visible live in the editor when you save.', 'kntnt-global-styles' ),

					// Modal
					'modal_title' => __( 'Global Styles Editor', 'kntnt-global-styles' ),
					'modal_description' => __( 'Large CSS editor for writing custom styles', 'kntnt-global-styles' ),

					// Accessibility labels
					'css_aria_label' => __( 'CSS Editor', 'kntnt-global-styles' ),
					'modal_css_aria_label' => __( 'Large CSS Editor', 'kntnt-global-styles' ),
					'open_editor_aria' => __( 'Open larger CSS editor', 'kntnt-global-styles' ),
					'save_aria' => __( 'Save CSS', 'kntnt-global-styles' ),
					'saving_aria' => __( 'Saving CSS...', 'kntnt-global-styles' ),
					'cancel_aria' => __( 'Cancel and close editor', 'kntnt-global-styles' ),
					'modal_actions_aria' => __( 'Modal actions', 'kntnt-global-styles' ),
				],
			] );
		}
	}

	/**
	 * Gets cached CSS file information including existence, URL, and version.
	 *
	 * Caches the file information to avoid repeated file system checks during
	 * the same request. Information includes file existence, paths, and modification time.
	 *
	 * @return array{exists: bool, path: string, url: string, version: int} File information array.
	 * @since 2.0.0
	 *
	 */
	private function get_css_file_info(): array {

		// Return cached information if available
		if ( self::$css_file_info === null ) {

			// Get file paths from plugin configuration
			$css_file_path = Plugin::get_css_path();
			$css_file_url = Plugin::get_css_url();

			// Check if file exists and has content (filesize > 0)
			$exists = ! empty( $css_file_path ) && file_exists( $css_file_path ) && filesize( $css_file_path ) > 0;

			// Use file modification time as version for cache busting
			$version = $exists ? filemtime( $css_file_path ) : 0;

			// Cache the information for subsequent calls
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
	 * Called after file operations (like saving CSS) to ensure fresh data
	 * is loaded on the next file info request.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public static function clear_file_cache(): void {
		self::$css_file_info = null;
	}

}