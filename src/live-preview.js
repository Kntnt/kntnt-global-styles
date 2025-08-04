/**
 * Live preview functionality for the Kntnt Global Styles plugin.
 *
 * Provides a global function to inject CSS into the block editor's main document
 * and any nested iframes for real-time style preview. Handles the complex iframe
 * structure of the WordPress block editor where content may be rendered in
 * isolated contexts.
 */

/**
 * Global function to update CSS styles in the block editor.
 *
 * Injects CSS into both the main editor document and any editor iframes
 * to provide live preview functionality. Handles timing issues with
 * dynamically loaded iframes by attempting injection multiple times.
 *
 * @param {string} newCss - The CSS content to inject into the editor.
 */
window.kntntUpdateEditorStyles = function (newCss) {

  // Configuration constants for timing and element identification
  const TIMINGS = {
    RETRY_QUICK: 1000,  // First retry after 1 second
    RETRY_SLOW: 3000,   // Second retry after 3 seconds
  }

  // Selectors for finding editor iframes in different WordPress configurations
  const IFRAME_SELECTORS = [
    'iframe[name="editor-canvas"]',           // Site editor iframe
    '.edit-post-visual-editor iframe',        // Post editor iframe
    '.interface-interface-skeleton__content iframe', // Interface skeleton iframe
    '.editor-styles-wrapper iframe',          // Editor styles wrapper iframe
  ]

  // IDs for the style elements we inject
  const ELEMENT_IDS = {
    DYNAMIC_STYLES: 'kntnt-global-styles-dynamic-styles', // Main document styles
    IFRAME_STYLES: 'kntnt-iframe-styles',                  // Iframe styles
  }

  /**
   * Main function to apply styles to editor documents.
   *
   * Updates or creates style elements in the main document and attempts
   * to inject styles into any discovered editor iframes.
   *
   * @param {string} cssToInject - The CSS content to apply.
   */
  const updateStyles = (cssToInject) => {

    // Ensure we have a string to work with (handle null/undefined)
    cssToInject = cssToInject || ''

    // Update or create style element in the main document head
    let styleElement = document.querySelector('#' + ELEMENT_IDS.DYNAMIC_STYLES)
    if (!styleElement) {
      styleElement = document.createElement('style')
      styleElement.id = ELEMENT_IDS.DYNAMIC_STYLES
      styleElement.type = 'text/css'
      document.head.appendChild(styleElement)
    }
    styleElement.textContent = cssToInject

    /**
     * Injects styles into discovered editor iframes.
     *
     * Searches for iframes using multiple selectors and attempts to inject
     * CSS into each one. Handles cross-origin restrictions gracefully.
     */
    const injectIntoEditorIframes = () => {
      IFRAME_SELECTORS.forEach((selector) => {
        const iframes = document.querySelectorAll(selector)
        iframes.forEach((iframe) => {
          try {
            // Attempt to access iframe document
            const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document
            if (!iframeDoc) return

            // Find or create style element in iframe
            let styleEl = iframeDoc.querySelector('#' + ELEMENT_IDS.IFRAME_STYLES)
            if (!styleEl) {
              styleEl = iframeDoc.createElement('style')
              styleEl.id = ELEMENT_IDS.IFRAME_STYLES
              styleEl.type = 'text/css'
              if (iframeDoc.head) {
                iframeDoc.head.appendChild(styleEl)
              }
            }
            styleEl.textContent = cssToInject
          } catch (error) {
            // Cross-origin errors are expected and can be safely ignored
            if (!error.message.includes('cross-origin')) {
              console.warn('Kntnt Global Styles: Iframe injection failed:', error.message)
            }
          }
        })
      })
    }

    // Inject styles immediately and with delays to catch dynamic iframes
    injectIntoEditorIframes()
    setTimeout(injectIntoEditorIframes, TIMINGS.RETRY_QUICK)
    setTimeout(injectIntoEditorIframes, TIMINGS.RETRY_SLOW)
  }

  // Execute the style update
  updateStyles(newCss)
}