/**
 * Constants for the Kntnt Global Styles plugin.
 *
 * Centralizes all magic numbers, timing values, and UI constants
 * to improve code readability and maintainability.
 */

// Timing constants for style injection and retries
export const TIMINGS = {
  WAIT_FOR_EDITOR: 1500,  // Wait for block editor to fully load
  RETRY_QUICK: 1000,      // Quick retry for iframe injection
  RETRY_SLOW: 3000        // Slower retry for iframe injection
}

// UI constants for consistent styling and behavior
export const UI_CONSTANTS = {
  MODAL_MAX_WIDTH: '90vw',
  MODAL_MAX_HEIGHT: '90vh',
  TEXTAREA_MIN_HEIGHT: '300px',
  MODAL_TEXTAREA_MIN_HEIGHT: '50vh',
  TEXTAREA_ROWS: 20,
  MODAL_TEXTAREA_ROWS: 25,
  SPACER_MARGIN: 4
}

// CSS selectors for iframe injection
export const IFRAME_SELECTORS = [
  'iframe[name="editor-canvas"]',
  '.edit-post-visual-editor iframe',
  '.interface-interface-skeleton__content iframe',
  '.editor-styles-wrapper iframe'
]

// Element IDs used throughout the plugin
export const ELEMENT_IDS = {
  DYNAMIC_STYLES: 'kntnt-global-styles-dynamic-styles',
  IFRAME_STYLES: 'kntnt-iframe-styles',
  CSS_HELP: 'kntnt-css-editor-help'
}

// Plugin identification constants
export const PLUGIN_NAME = 'kntnt-global-styles-sidebar'
export const PLUGIN_TITLE = 'Global Styles'