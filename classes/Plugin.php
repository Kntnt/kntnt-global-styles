<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

use LogicException;

/**
 * Main plugin class implementing singleton pattern.
 *
 * Coordinates plugin initialization, manages configuration, and provides
 * centralized access to plugin metadata, options, and component instances.
 * Serves as the primary entry point for all plugin functionality.
 */
final class Plugin {

	/**
	 * Singleton instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Path to the main plugin file.
	 *
	 * @var string|null
	 */
	private static ?string $plugin_file = null;

	/**
	 * Plugin slug derived from filename.
	 *
	 * @var string|null
	 */
	private static ?string $plugin_slug = null;

	/**
	 * Cached plugin metadata from WordPress plugin header.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $plugin_data = null;

	/**
	 * Handles plugin updates from GitHub.
	 *
	 * @var Updater
	 */
	public readonly Updater $updater;

	/**
	 * Manages CSS editing functionality in block editor.
	 *
	 * @var Editor
	 */
	private readonly Editor $editor;

	/**
	 * Handles frontend and editor asset enqueueing.
	 *
	 * @var Assets
	 */
	private readonly Assets $assets;

	/**
	 * Private constructor for singleton pattern.
	 *
	 * Initializes all plugin components and registers WordPress hooks.
	 * Called only once when the singleton instance is first created.
	 */
	private function __construct() {
		$this->editor = new Editor;
		$this->updater = new Updater;
		$this->assets = new Assets;

		// Register all WordPress hooks for plugin functionality
		$this->register_hooks();
	}

	/**
	 * Gets the singleton instance of the plugin.
	 *
	 * Implements lazy initialization - creates instance only when needed.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function get_instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Gets the editor component instance.
	 *
	 * Provides access to CSS editing functionality for external use.
	 *
	 * @return Editor The editor component instance.
	 */
	public function get_editor(): Editor {
		return $this->editor;
	}

	/**
	 * Sets the plugin file path for internal use.
	 *
	 * Must be called from the main plugin file before any other plugin
	 * methods that depend on file paths or plugin metadata.
	 *
	 * @param string $file Full path to the main plugin file.
	 *
	 * @return void
	 */
	public static function set_plugin_file( string $file ): void {
		self::$plugin_file = $file;
	}

	/**
	 * Gets the plugin file path.
	 *
	 * @return string Full path to the main plugin file.
	 * @throws LogicException If plugin file hasn't been set via set_plugin_file().
	 */
	public static function get_plugin_file(): string {
		if ( self::$plugin_file === null ) {
			throw new LogicException( 'Plugin file must be set using set_plugin_file() before accessing plugin metadata.' );
		}
		return self::$plugin_file;
	}

	/**
	 * Gets the plugin directory path.
	 *
	 * @return string Full filesystem path to the plugin directory.
	 */
	public static function get_plugin_dir(): string {
		return plugin_dir_path( self::get_plugin_file() );
	}

	/**
	 * Gets the plugin directory URL.
	 *
	 * @return string HTTP URL to the plugin directory.
	 */
	public static function get_plugin_url(): string {
		return plugin_dir_url( self::get_plugin_file() );
	}

	/**
	 * Gets plugin metadata from the WordPress plugin header.
	 *
	 * Reads and caches metadata like version, description, and text domain
	 * from the main plugin file header comments.
	 *
	 * @return array<string, mixed> {
	 *     Plugin data array from WordPress get_plugin_data() function.
	 *
	 * @type string $Name        Plugin name.
	 * @type string $PluginURI   Plugin URI.
	 * @type string $Version     Plugin version.
	 * @type string $Description Plugin description.
	 * @type string $Author      Plugin author's name.
	 * @type string $AuthorURI   Plugin author's website.
	 * @type string $TextDomain  Plugin textdomain for translations.
	 * @type string $DomainPath  Relative path to translation files.
	 * @type bool   $Network     Whether plugin can only be network activated.
	 * @type string $RequiresWP  Minimum WordPress version.
	 * @type string $RequiresPHP Minimum PHP version.
	 * @type string $UpdateURI   Update URI for custom updaters.
	 *                           }
	 */
	public static function get_plugin_data(): array {

		// Load and cache plugin metadata on first access
		if ( self::$plugin_data === null ) {
			self::$plugin_data = self::parse_plugin_header();
		}

		return self::$plugin_data;
	}

	/**
	 * Parses the plugin header for metadata.
	 *
	 * Uses WordPress get_plugin_data() function to extract metadata
	 * from plugin file header comments. Includes the function file
	 * since it's not available by default on frontend.
	 *
	 * @return array<string, mixed> Plugin header data.
	 */
	private static function parse_plugin_header(): array {
		// Ensure get_plugin_data() function is available
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Extract metadata from plugin file header
		return get_plugin_data( self::get_plugin_file() );
	}

	/**
	 * Gets the plugin text domain for translations.
	 *
	 * @return string Plugin text domain from header or empty string.
	 */
	public static function get_l10n_domain(): string {
		$plugin_data = self::get_plugin_data();
		return $plugin_data['TextDomain'] ?? '';
	}

	/**
	 * Gets the relative path to translation files.
	 *
	 * @return string Domain path from plugin header or empty string.
	 */
	public static function get_l10n_dir(): string {
		$plugin_data = self::get_plugin_data();
		return $plugin_data['DomainPath'] ?? '';
	}

	/**
	 * Gets the plugin slug based on main file name.
	 *
	 * Derives slug from the plugin filename without the .php extension.
	 * Used for generating option names, handles, and other identifiers.
	 *
	 * @return string Plugin slug.
	 */
	public static function get_slug(): string {
		if ( self::$plugin_slug === null ) {
			$file = self::get_plugin_file();
			self::$plugin_slug = basename( $file, '.php' );
		}
		return self::$plugin_slug;
	}

	/**
	 * Gets plugin option data from WordPress options table.
	 *
	 * Retrieves stored plugin configuration. Can get the entire option
	 * array or a specific key within it. Option name is automatically
	 * generated from the plugin slug.
	 *
	 * @param string|null $key Specific option key to retrieve, or null for entire option.
	 *
	 * @return mixed Option value or null if not found.
	 */
	public static function get_option( string $key = null ): mixed {
		// Convert plugin slug to option name format
		$option_name = str_replace( '-', '_', self::get_slug() );
		$option = get_option( $option_name, [] );

		// Return specific key or entire option array
		if ( $key !== null ) {
			return $option[ $key ] ?? null;
		}
		return $option;
	}

	/**
	 * Sets plugin option data in WordPress options table.
	 *
	 * Stores plugin configuration. Can set the entire option or update
	 * a specific key within the option array. Creates the option if
	 * it doesn't exist.
	 *
	 * @param mixed       $value The value to set.
	 * @param string|null $key   Specific option key to update, or null to replace entire option.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function set_option( mixed $value, string $key = null ): bool {
		// Generate standardized option name from plugin slug
		$option_name = str_replace( '-', '_', self::get_slug() );

		if ( $key !== null ) {
			// Update specific key within existing option array
			$option = get_option( $option_name, [] );
			$option[ $key ] = $value;
			return update_option( $option_name, $option );
		}

		// Replace entire option with new value
		return update_option( $option_name, $value );
	}

	/**
	 * Gets the CSS content from the database.
	 *
	 * @return string CSS content or empty string if not found.
	 */
	public static function get_css(): string {
		return self::get_option( 'css' ) ?? '';
	}

	/**
	 * Sets the CSS content in the database.
	 *
	 * @param string $css CSS content to save.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function set_css( string $css ): bool {
		return self::set_option( $css, 'css' );
	}

	/**
	 * Gets the directory path where CSS files are stored.
	 *
	 * Creates a plugin-specific subdirectory within WordPress uploads
	 * directory for storing generated CSS files.
	 *
	 * @return string Directory path or empty string if upload dir unavailable.
	 */
	public static function get_css_dir(): string {
		$basedir = self::wp_upload_dir( 'basedir' );
		return $basedir ? $basedir . '/' . self::get_slug() : '';
	}

	/**
	 * Gets the full file path for the CSS file.
	 *
	 * @return string File path or empty string if directory unavailable.
	 */
	public static function get_css_path(): string {
		$dir = self::get_css_dir();
		return $dir ? $dir . '/' . self::get_slug() . '.css' : '';
	}

	/**
	 * Gets the public URL for the CSS file.
	 *
	 * @return string File URL or empty string if upload dir unavailable.
	 */
	public static function get_css_url(): string {
		$baseurl = self::wp_upload_dir( 'baseurl' );
		return $baseurl ? $baseurl . '/' . self::get_slug() . '/' . self::get_slug() . '.css' : '';
	}

	/**
	 * Loads the plugin's translated strings.
	 *
	 * Initializes the WordPress translation system for the plugin using
	 * the text domain and domain path from the plugin header.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( self::get_l10n_domain(), false, self::get_slug() . self::get_l10n_dir() );
	}

	/**
	 * Registers WordPress hooks for plugin functionality.
	 *
	 * Sets up all necessary WordPress actions and filters to integrate
	 * the plugin with WordPress core, block editor, and frontend.
	 *
	 * @return void
	 */
	private function register_hooks(): void {

		// Check for plugin updates from GitHub repository
		add_filter( 'pre_set_site_transient_update_plugins', [ $this->updater, 'check_for_updates' ] );

		// Load plugin translations early in WordPress initialization
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Load block editor JavaScript and CSS assets
		add_action( 'enqueue_block_editor_assets', [ $this->editor, 'enqueue_block_editor_assets' ] );

		// Load frontend stylesheet with high priority to allow theme overrides
		add_action( 'wp_enqueue_scripts', [ $this->assets, 'enqueue_frontend_style' ], 9999 );

		// Inject CSS into block editor for live preview functionality
		add_action( 'init', [ $this->assets, 'add_custom_css_to_block_editor' ] );

		// Handle AJAX requests for saving CSS from the editor modal
		add_action( 'wp_ajax_kntnt_global_styles_save_css', [ $this->editor, 'handle_ajax_save' ] );
	}

	/**
	 * Helper method to get WordPress upload directory information.
	 *
	 * Safely retrieves upload directory paths and URLs with error handling
	 * for cases where uploads directory is not properly configured.
	 *
	 * @param string $key The specific upload directory key to retrieve (basedir, baseurl, etc.).
	 *
	 * @return string|false Directory path/URL or false on error.
	 */
	private static function wp_upload_dir( string $key ): string|false {
		// Get WordPress upload directory configuration
		$upload_dir = wp_upload_dir();

		// Check for upload directory configuration errors
		if ( $upload_dir['error'] ) {
			error_log( 'Kntnt Global Styles: Upload directory error: ' . $upload_dir['error'] );
			return false;
		}

		// Return the requested directory information
		return $upload_dir[ $key ];
	}

	/**
	 * Prevents cloning of singleton instance.
	 *
	 * @throws LogicException Always throws to prevent cloning.
	 */
	private function __clone(): void {
		throw new LogicException( 'Cannot clone a singleton.' );
	}

	/**
	 * Prevents unserialization of singleton instance.
	 *
	 * @throws LogicException Always throws to prevent unserialization.
	 */
	public function __wakeup(): void {
		throw new LogicException( 'Cannot unserialize a singleton.' );
	}

}