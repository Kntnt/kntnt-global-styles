<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

use LogicException;

/**
 * Main plugin class implementing singleton pattern.
 *
 * Manages plugin initialization, configuration, and provides central access
 * to plugin metadata and options. Coordinates between different plugin components.
 *
 * @package Kntnt\Global_Styles
 * @since   2.0.0
 */
final class Plugin {

	/**
	 * Singleton instance of the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Path to the main plugin file.
	 *
	 * @since 2.0.0
	 *
	 * @var string|null
	 */
	private static ?string $plugin_file = null;

	/**
	 * Plugin slug derived from filename.
	 *
	 * @since 2.0.0
	 *
	 * @var string|null
	 */
	private static ?string $plugin_slug = null;

	/**
	 * Cached plugin metadata from header.
	 *
	 * @since 2.0.0
	 *
	 * @var array|null
	 */
	private static ?array $plugin_data = null;

	/**
	 * Updater component instance.
	 *
	 * @since 2.1.0
	 *
	 * @var Updater
	 */
	public readonly Updater $updater;

	/**
	 * Editor component instance.
	 *
	 * @since 2.0.0
	 *
	 * @var Editor
	 */
	public readonly Editor $editor;

	/**
	 * Assets management component instance.
	 *
	 * @since 2.0.0
	 *
	 * @var Assets
	 */
	public readonly Assets $assets;

	/**
	 * CSS annotation parser component instance.
	 *
	 * @since 2.0.0
	 *
	 * @var Integrator
	 */
	public readonly Integrator $parser;

	/**
	 * Private constructor for singleton pattern.
	 *
	 * Initializes plugin components and registers WordPress hooks.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		// Initialize plugin components
		$this->updater = new Updater;
		$this->editor = new Editor;
		$this->assets = new Assets;
		$this->parser = new Integrator;

		// Register WordPress hooks
		$this->register_hooks();
	}

	/**
	 * Gets the singleton instance of the plugin.
	 *
	 * Creates the instance if it doesn't exist, otherwise returns existing instance.
	 *
	 * @return Plugin The plugin instance.
	 * @since 2.0.0
	 *
	 */
	public static function get_instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Sets the plugin file path. Called from the main plugin file.
	 *
	 * Must be called before any other plugin methods that depend on file paths.
	 *
	 * @param string $file Full path to the main plugin file.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public static function set_plugin_file( string $file ): void {
		self::$plugin_file = $file;
	}

	/**
	 * Gets the plugin file path.
	 *
	 * @return string Full path to the main plugin file.
	 *
	 * @throws LogicException If plugin file hasn't been set.
	 * @since 2.0.0
	 *
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
	 * @return string Full path to the plugin directory.
	 * @since 2.0.0
	 *
	 */
	public static function get_plugin_dir(): string {
		return plugin_dir_path( self::get_plugin_file() );
	}

	/**
	 * Gets url to the plugin directory.
	 *
	 * @return string URL to the plugin directory.
	 * @since 2.0.0
	 *
	 */
	public static function get_plugin_url(): string {
		return plugin_dir_url( self::get_plugin_file() );
	}

	/**
	 * Gets the plugin data from the plugin header.
	 *
	 * Reads version information from the main plugin file header. Caches
	 * the result to avoid repeated file parsing.
	 *
	 * @return array {
	 *     Plugin data. Values will be empty if not supplied by the plugin.
	 *
	 * @type string $Name            Name of the plugin. Should be unique.
	 * @type string $PluginURI       Plugin URI.
	 * @type string $Version         Plugin version.
	 * @type string $Description     Plugin description.
	 * @type string $Author          Plugin author's name.
	 * @type string $AuthorURI       Plugin author's website address (if set).
	 * @type string $TextDomain      Plugin textdomain.
	 * @type string $DomainPath      Plugin's relative directory path to .mo files.
	 * @type bool   $Network         Whether the plugin can only be activated network-wide.
	 * @type string $RequiresWP      Minimum required version of WordPress.
	 * @type string $RequiresPHP     Minimum required version of PHP.
	 * @type string $UpdateURI       ID of the plugin for update purposes, should be a URI.
	 * @type string $RequiresPlugins Comma separated list of dot org plugin slugs.
	 * @type string $Title           Title of the plugin and link to the plugin's site (if set).
	 * @type string $AuthorName      Plugin author's name.
	 *                               }
	 * @since 2.1.0
	 *
	 */
	public static function get_plugin_data(): array {

		// Load plugin data if not already cached
		if ( self::$plugin_data === null ) {

			// get_plugin_data() is only available in admin context by default.
			// Since this plugin can be instantiated on frontend (for enqueuing styles),
			// we need to ensure the function exists before calling it.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Parse plugin header for metadata
			self::$plugin_data = get_plugin_data( self::get_plugin_file() );

		}

		return self::$plugin_data;

	}

	/**
	 * Gets the plugin version from the plugin header.
	 *
	 * @return string Plugin version number.
	 * @since 2.0.0
	 *
	 */
	public static function get_version(): string {
		return self::get_plugin_data()['Version'] ?? '';
	}

	/**
	 * Gets the plugin slug based on filename (without .php).
	 *
	 * @return string Plugin slug.
	 * @since 2.0.0
	 *
	 */
	public static function get_slug(): string {
		if ( self::$plugin_slug === null ) {
			$file = self::get_plugin_file();
			self::$plugin_slug = basename( $file, '.php' );
		}
		return self::$plugin_slug;
	}

	/**
	 * Required capability for using the editor.
	 *
	 * Can be made configurable in the future if necessary.
	 *
	 * @return string WordPress capability required to use the plugin.
	 * @since 2.0.0
	 *
	 */
	public static function get_capability(): string {
		return 'edit_theme_options';
	}

	/**
	 * Gets plugin option data from WordPress options table.
	 *
	 * Can retrieve the entire option array or a specific key within it.
	 * Option name is automatically generated from plugin slug.
	 *
	 * @param string|null $key Specific option key to retrieve, or null for entire option.
	 *
	 * @return mixed Option value or null if not found.
	 * @since 2.0.0
	 *
	 */
	public static function get_option( string $key = null ): mixed {
		// Generate option name from plugin slug (replace hyphens with underscores)
		$option_name = str_replace( '-', '_', self::get_slug() );
		$option = get_option( $option_name, [] );

		// Return specific key or entire option
		if ( $key !== null ) {
			return $option[ $key ] ?? null;
		}
		return $option;
	}

	/**
	 * Sets plugin option data in WordPress options table.
	 *
	 * Can set the entire option or update a specific key within the option array.
	 * Creates the option if it doesn't exist.
	 *
	 * @param mixed       $value The value to set.
	 * @param string|null $key   Specific option key to update, or null to replace entire option.
	 *
	 * @return bool True on success, false on failure.
	 * @since 2.0.0
	 *
	 */
	public static function set_option( mixed $value, string $key = null ): bool {
		// Generate option name from plugin slug
		$option_name = str_replace( '-', '_', self::get_slug() );

		if ( $key !== null ) {
			// Update specific key within option array
			$option = get_option( $option_name, [] );
			$option[ $key ] = $value;
			return update_option( $option_name, $option );
		}

		// Replace entire option
		return update_option( $option_name, $value );
	}

	/**
	 * Gets the directory path where CSS files are stored.
	 *
	 * @return string Directory path or empty string if upload dir unavailable.
	 * @since 2.0.0
	 *
	 */
	public static function get_css_dir(): string {
		$basedir = self::wp_upload_dir( 'basedir' );
		return $basedir ? $basedir . '/' . self::get_slug() : '';
	}

	/**
	 * Gets the full file path for the CSS file.
	 *
	 * @return string File path or empty string if directory unavailable.
	 * @since 2.0.0
	 *
	 */
	public static function get_css_path(): string {
		$dir = self::get_css_dir();
		return $dir ? $dir . '/' . self::get_slug() . '.css' : '';
	}

	/**
	 * Gets the public URL for the CSS file.
	 *
	 * @return string File URL or empty string if upload dir unavailable.
	 * @since 2.0.0
	 *
	 */
	public static function get_css_url(): string {
		$baseurl = self::wp_upload_dir( 'baseurl' );
		return $baseurl ? $baseurl . '/' . self::get_slug() . '/' . self::get_slug() . '.css' : '';
	}

	/**
	 * Registers WordPress hooks for plugin functionality.
	 *
	 * Sets up all necessary WordPress actions and filters for the plugin
	 * to function properly.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	private function register_hooks(): void {

		// Check for updates from GitHub
		add_filter( 'pre_set_site_transient_update_plugins', [ $this->updater, 'check_for_updates' ] );

		// AJAX handler for saving CSS
		add_action( 'wp_ajax_kntnt_save_css', [ $this->editor, 'handle_ajax_save' ] );

		// Register admin assets
		add_action( 'admin_enqueue_scripts', [ $this->assets, 'enqueue_admin_assets' ] );

		// Register frontend assets (high priority to load last)
		add_action( 'wp_enqueue_scripts', [ $this->assets, 'enqueue_frontend_style' ], 9999 );

		// Add custom styles to block editor
		add_action( 'init', [ $this->assets, 'add_custom_css_to_block_editor' ] );

		// Parse CSS for annotated classes and trigger action
		add_action( 'init', [ $this->parser, 'parse_and_trigger_annotated_classes' ] );

	}

	/**
	 * Helper method to get WordPress upload directory information.
	 *
	 * @param string $key The specific upload directory key to retrieve.
	 *
	 * @return string|false Directory path/URL or false on error.
	 * @since 2.0.0
	 *
	 */
	private static function wp_upload_dir( string $key ): string|false {
		// Get upload directory information
		$upload_dir = wp_upload_dir();

		// Check for errors in upload directory configuration
		if ( $upload_dir['error'] ) {
			error_log( 'Kntnt Global Styles: Upload directory error: ' . $upload_dir['error'] );
			return false;
		}

		// Return requested key
		return $upload_dir[ $key ];
	}

	/**
	 * Prevents cloning of singleton instance.
	 *
	 * @throws LogicException Always throws to prevent cloning.
	 * @since 2.0.0
	 *
	 */
	private function __clone(): void {
		throw new LogicException( 'Cannot clone a singleton.' );
	}

	/**
	 * Prevents unserialization of singleton instance.
	 *
	 * @throws LogicException Always throws to prevent unserialization.
	 * @since 2.0.0
	 *
	 */
	public function __wakeup(): void {
		throw new LogicException( 'Cannot unserialize a singleton.' );
	}

}