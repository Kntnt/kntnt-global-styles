<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

/**
 * Handles automatic plugin updates from GitHub releases.
 *
 * Integrates with WordPress update system to check for new plugin releases
 * on GitHub and present them in the WordPress admin area for installation.
 * Only processes releases that include a manually uploaded ZIP asset.
 */
final class Updater {

	/**
	 * Checks for new plugin releases on GitHub.
	 *
	 * Hooked into the WordPress update transient to compare the installed
	 * version with the latest GitHub release. Only triggers updates if a
	 * suitable ZIP asset is found in the release.
	 *
	 * @param object $transient The WordPress update transient object.
	 *
	 * @return object The potentially modified transient object.
	 */
	public function check_for_updates( object $transient ): object {

		// Skip if WordPress hasn't performed a recent update check
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Extract GitHub repository information from plugin metadata
		$plugin_data = Plugin::get_plugin_data();
		$github_uri = $plugin_data['PluginURI'] ?? '';

		$github_repo = $this->get_github_repo_from_uri( $github_uri );
		if ( ! $github_repo ) {
			return $transient;
		}

		// Fetch latest release information from GitHub API
		$latest_release = $this->get_latest_github_release( $github_repo );
		if ( ! $latest_release ) {
			return $transient;
		}

		// Compare installed version with latest GitHub release
		$current_version = $plugin_data['Version'];
		$latest_version = ltrim( $latest_release->tag_name, 'v' ); // Remove 'v' prefix if present

		if ( version_compare( $current_version, $latest_version, '<' ) ) {
			$plugin_slug_path = plugin_basename( Plugin::get_plugin_file() );

			$package_url = null;

			// Look for a ZIP asset in the release (manually uploaded distribution)
			if ( ! empty( $latest_release->assets ) ) {
				foreach ( $latest_release->assets as $asset ) {
					if ( $asset->content_type === 'application/zip' ) {
						$package_url = $asset->browser_download_url;
						break; // Use the first ZIP asset found
					}
				}
			}

			// Only proceed if we found a suitable package URL
			if ( ! $package_url ) {
				return $transient;
			}

			// Create update information object for WordPress
			$update_info = new \stdClass;
			$update_info->slug = dirname( $plugin_slug_path );
			$update_info->plugin = $plugin_slug_path;
			$update_info->new_version = $latest_version;
			$update_info->url = $latest_release->html_url;
			$update_info->package = $package_url;
			$update_info->tested = $plugin_data['Requires at least'] ?? get_bloginfo( 'version' );

			// Add update information to WordPress update system
			$transient->response[ $plugin_slug_path ] = $update_info;
		}

		return $transient;
	}

	/**
	 * Fetches the latest release data from the GitHub API.
	 *
	 * Makes a remote HTTP request to GitHub's API to get information
	 * about the most recent release for the specified repository.
	 *
	 * @param string $repo The repository name in 'user/repo' format.
	 *
	 * @return object|null The release data object on success, null on failure.
	 */
	private function get_latest_github_release( string $repo ): ?object {
		$request_uri = "https://api.github.com/repos/{$repo}/releases/latest";
		$response = wp_remote_get( $request_uri );

		// Check for HTTP errors or non-200 status codes
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$release_data = json_decode( wp_remote_retrieve_body( $response ) );

		// Validate that we received expected data structure
		if ( empty( $release_data ) || ! isset( $release_data->tag_name, $release_data->zipball_url ) ) {
			return null;
		}

		return $release_data;
	}

	/**
	 * Extracts the GitHub repository slug from a URI.
	 *
	 * Parses a full GitHub URL to extract the 'user/repo' portion
	 * needed for API calls. Handles various GitHub URL formats.
	 *
	 * @param string $uri The full GitHub Plugin URI from the plugin header.
	 *
	 * @return string|null The 'user/repo' slug on success, null if invalid.
	 */
	private function get_github_repo_from_uri( string $uri ): ?string {
		// Basic validation of GitHub URI
		if ( empty( $uri ) || ! str_contains( $uri, 'github.com' ) ) {
			return null;
		}

		$path = parse_url( $uri, PHP_URL_PATH );
		if ( ! $path ) {
			return null;
		}

		// Extract user and repo from path segments
		$parts = explode( '/', trim( $path, '/' ) );
		if ( count( $parts ) >= 2 ) {
			return "{$parts[0]}/{$parts[1]}";
		}

		return null;
	}

}