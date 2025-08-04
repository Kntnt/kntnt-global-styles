<?php

declare( strict_types = 1 );

namespace Kntnt\Global_Styles;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enumeration of AJAX response status codes for the CSS editor.
 *
 * Provides standardized error codes that can be used by both server-side
 * PHP code and client-side JavaScript for consistent error handling.
 */
enum AjaxStatus: string {

	/**
	 * The security nonce verification failed.
	 */
	case INVALID_NONCE = 'invalid_nonce';

	/**
	 * The current user lacks required permissions for the operation.
	 */
	case INSUFFICIENT_PERMISSIONS = 'insufficient_permissions';

	/**
	 * Failed to save CSS content to database or file system.
	 */
	case SAVE_FAILED = 'save_failed';

}