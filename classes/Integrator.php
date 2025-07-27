<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

/**
 * Integration with external plugins and themes via CSS annotations.
 *
 * Parses CSS comments for @class annotations and triggers a WordPress action
 * with the found class names, allowing other plugins to use these classes.
 *
 * @package Kntnt\Global_Styles
 * @since   2.0.0
 */
final class Integrator {

	/**
	 * Parses CSS for annotated classes and triggers action.
	 *
	 * Searches the custom CSS for @class annotations and triggers the
	 * 'kntnt-global-styles-annotated-classes' action with the found classes.
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function parse_and_trigger_annotated_classes(): void {

		// Get the stored CSS content
		$custom_css = Plugin::get_option()['css'] ?? '';
		if ( empty( $custom_css ) ) {
			return;
		}

		// Parse CSS for @class annotations
		$found_classes = $this->parse_css_for_classes( $custom_css );
		if ( empty( $found_classes ) ) {
			return;
		}

		// Trigger action with found classes
		do_action( 'kntnt-global-styles-annotated-classes', $found_classes );

	}

	/**
	 * Parses CSS content for @class annotations.
	 *
	 * Searches through all CSS comment blocks for @class tags
	 * and extracts class names and descriptions.
	 *
	 * @param string $css The CSS content to parse.
	 *
	 * @return array{name: string, description: string}[] Array of parsed class definitions.
	 * @since 2.0.0
	 *
	 */
	private function parse_css_for_classes( string $css ): array {

		$classes = [];

		// Regex that matches /* ... */ comment blocks
		// Handles nested asterisks and special characters properly
		$comment_pattern = '/\/\*(?:[^*]++|\*(?!\/))*+\*\//';

		// Find all comment blocks in the CSS
		if ( ! preg_match_all( $comment_pattern, $css, $comment_matches ) ) {
			return [];
		}

		// Process each comment block for @class annotations
		foreach ( $comment_matches[0] as $comment_block ) {

			// Remove comment delimiters /* and */
			$comment_content = trim( substr( $comment_block, 2, - 2 ) );

			// Look for @class lines within the comment
			// Handles various whitespace and asterisk patterns
			$class_pattern = '/^[ \t]*\*?[ \t]*@class\s+(.+)$/m';

			if ( preg_match_all( $class_pattern, $comment_content, $matches ) ) {
				// Process each @class line found
				foreach ( $matches[1] as $class_definition ) {
					$parsed_class = $this->parse_class_definition( $class_definition );
					if ( $parsed_class ) {
						$classes[] = $parsed_class;
					}
				}
			}
		}

		return $classes;

	}

	/**
	 * Parses a single @class definition line.
	 *
	 * Extracts the class name and optional description from a line like:
	 * "class-name | Optional description"
	 *
	 * @param string $class_definition The class definition line to parse.
	 *
	 * @return array{name: string, description: string}|null Parsed class data or null if invalid.
	 * @since 2.0.0
	 *
	 */
	private function parse_class_definition( string $class_definition ): ?array {

		// Split on pipe character to separate class name from description
		$parts = explode( '|', $class_definition, 2 );
		$class_name = trim( $parts[0] );

		// Validate class name using basic CSS class name rules
		// Must start with letter, can contain letters, numbers, hyphens, underscores
		if ( empty( $class_name ) || ! preg_match( '/^[a-zA-Z][\w-]*$/', $class_name ) ) {
			return null;
		}

		// Extract description if provided
		$description = isset( $parts[1] ) ? trim( $parts[1] ) : '';

		return [
			'name' => $class_name,
			'description' => $description,
		];
	}

}