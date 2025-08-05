import { addFilter } from '@wordpress/hooks'
import { withHiddenOriginalControl, withGlobalStylesPanel } from './css-class-selector'
import './css-class-selector.css'
import './css-editor.css'
import './editor-integration'

/**
 * Main entry point for the Kntnt Global Styles plugin.
 *
 * Sets up WordPress block editor filters to enhance blocks with custom
 * CSS class functionality and integrates the Global Styles panel.
 * Also handles automatic persistence of draft CSS when documents are saved.
 */

// Hide the original WordPress "Additional CSS class(es)" control
addFilter(
  'editor.BlockEdit',
  'kntnt-global-styles/with-hidden-original-control',
  withHiddenOriginalControl
)

// Add the enhanced Global Styles panel to all supported blocks
addFilter(
  'editor.BlockEdit',
  'kntnt-global-styles/with-global-styles-panel',
  withGlobalStylesPanel
)

/**
 * Persists draft CSS content to the backend when documents are saved.
 *
 * Monitors the WordPress editor save state and automatically sends
 * any draft CSS changes to the server for permanent storage when
 * the user saves a post or page.
 */
const initializeDraftPersistence = () => {
  // Track save state to detect when save operations complete
  let wasRecentlySaving = false

  // Subscribe to WordPress data store changes
  if (window.wp?.data) {
    window.wp.data.subscribe(() => {
      const isSaving = window.wp.data.select('core/editor')?.isSavingPost()
      const isAutoSaving = window.wp.data.select('core/editor')?.isAutosavingPost()

      // Detect when a save operation completes (but not autosave)
      if (wasRecentlySaving && !isSaving && !isAutoSaving) {
        // Check if we have draft CSS that differs from saved CSS
        if (window.kntnt_global_styles_draft !== undefined && window.kntnt_global_styles_draft !== window.kntnt_global_styles_data?.css_content) {
          persistDraftCSS()
        }
      }

      wasRecentlySaving = isSaving
    })
  }
}

/**
 * Sends draft CSS content to the backend for permanent storage.
 *
 * Makes an AJAX request to save the draft CSS to the database and
 * generate the static CSS file for frontend use. Updates global
 * data structures with the newly persisted content.
 */
const persistDraftCSS = async () => {
  try {
    console.log('Kntnt Global Styles: Persisting draft CSS to backend...')

    // Prepare form data for the AJAX request
    const formData = new FormData()
    formData.append('action', 'kntnt_global_styles_save_css')
    formData.append('nonce', window.kntnt_global_styles_data?.nonce || '')
    formData.append('css_content', window.kntnt_global_styles_draft || '')
    formData.append('persist', 'true') // Flag for permanent storage

    // Send the save request to WordPress
    const response = await fetch(
      window.kntnt_global_styles_data?.ajax_url || '',
      {
        method: 'POST',
        body: formData,
      }
    )

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`)
    }

    const result = await response.json()

    if (result.success) {
      // Update global data with the persisted content
      if (window.kntnt_global_styles_data) {
        window.kntnt_global_styles_data.css_content = window.kntnt_global_styles_draft
        window.kntnt_global_styles_data.available_hints = result.data.available_hints || {}
      }

      // Clear draft since it's now permanently stored
      delete window.kntnt_global_styles_draft

      console.log('Kntnt Global Styles: Draft CSS successfully persisted')
    } else {
      console.error('Kntnt Global Styles: Failed to persist draft CSS:', result.data?.message)
    }
  } catch (error) {
    console.error('Kntnt Global Styles: Error persisting draft CSS:', error)
  }
}

// Initialize draft persistence when WordPress is ready
if (window.wp?.domReady) {
  window.wp.domReady(() => {
    initializeDraftPersistence()
  })
}