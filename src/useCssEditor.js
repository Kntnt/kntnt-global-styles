/**
 * Custom React hook for managing CSS editor functionality.
 *
 * Handles CSS state management, saving, and live preview updates
 * in the WordPress block editor environment.
 */

import { useState, useEffect, useCallback } from '@wordpress/element'
import { useDispatch } from '@wordpress/data'
import { TIMINGS, IFRAME_SELECTORS, ELEMENT_IDS } from './constants'

/**
 * Custom hook for CSS editor functionality
 *
 * @param {string} initialCss - Initial CSS content from server
 * @returns {Object} CSS editor state and methods
 */
export const useCssEditor = (initialCss) => {
  const [css, setCss] = useState('')
  const [modalCss, setModalCss] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [isModalOpen, setIsModalOpen] = useState(false)

  const { createSuccessNotice, createErrorNotice } = useDispatch('core/notices')
  const l10n = window.kntntEditorPanel?.l10n || {}

  // Initialize CSS content
  useEffect(() => {
    setCss(initialCss)

    if (initialCss && initialCss.trim().length > 0) {
      setTimeout(() => {
        updateEditorStyles(initialCss)
      }, TIMINGS.WAIT_FOR_EDITOR)
    }
  }, [initialCss])

  /**
   * Updates CSS styles in both main document and editor iframes
   *
   * @param {string} newCss - CSS content to inject
   */
  const updateEditorStyles = useCallback((newCss) => {
    if (!newCss || newCss.trim().length === 0) {
      return
    }

    // Update style element in main document
    let styleElement = document.querySelector(`#${ELEMENT_IDS.DYNAMIC_STYLES}`)
    if (!styleElement) {
      styleElement = document.createElement('style')
      styleElement.id = ELEMENT_IDS.DYNAMIC_STYLES
      styleElement.type = 'text/css'
      document.head.appendChild(styleElement)
    }
    styleElement.textContent = newCss

    // Inject CSS into editor iframes for block editor preview
    const injectIntoEditorIframes = () => {
      IFRAME_SELECTORS.forEach(selector => {
        const iframes = document.querySelectorAll(selector)
        iframes.forEach(iframe => {
          try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document
            if (!iframeDoc) return

            let styleEl = iframeDoc.querySelector(`#${ELEMENT_IDS.IFRAME_STYLES}`)
            if (!styleEl) {
              styleEl = iframeDoc.createElement('style')
              styleEl.id = ELEMENT_IDS.IFRAME_STYLES
              styleEl.type = 'text/css'
              if (iframeDoc.head) {
                iframeDoc.head.appendChild(styleEl)
              }
            }

            styleEl.textContent = newCss
          } catch (error) {
            // Cross-origin errors are expected and can be ignored
            if (!error.message.includes('cross-origin')) {
              console.warn('Kntnt Global Styles: Iframe injection failed:', error.message)
            }
          }
        })
      })
    }

    // Inject immediately and with delays to catch dynamically loaded iframes
    injectIntoEditorIframes()
    setTimeout(injectIntoEditorIframes, TIMINGS.RETRY_QUICK)
    setTimeout(injectIntoEditorIframes, TIMINGS.RETRY_SLOW)
  }, [])

  /**
   * Handles saving CSS content via AJAX
   *
   * @param {string} cssToSave - CSS content to save
   * @param {Function} onSuccess - Callback to execute on successful save
   */
  const handleSave = useCallback(async (cssToSave = css, onSuccess = null) => {
    setIsSaving(true)

    const formData = new FormData()
    formData.append('action', 'kntnt_save_css')
    formData.append('nonce', window.kntntEditorPanel?.nonce || '')
    formData.append('css_content', cssToSave)

    try {
      const response = await fetch(window.kntntEditorPanel?.ajax_url || '', {
        method: 'POST',
        body: formData,
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const result = await response.json()

      if (result.success) {
        createSuccessNotice(l10n.success || 'Saved!', { type: 'snackbar' })
        updateEditorStyles(cssToSave)
        setCss(cssToSave)
        onSuccess?.()
      } else {
        throw new Error(result.data?.message || l10n.error || 'Save failed')
      }
    } catch (error) {
      console.error('Kntnt Global Styles: Save error:', error)
      createErrorNotice(
        error.message || l10n.error || 'Error saving styles',
        { type: 'snackbar' }
      )
    } finally {
      setIsSaving(false)
    }
  }, [css, createSuccessNotice, createErrorNotice, l10n, updateEditorStyles])

  /**
   * Opens the modal editor with current CSS content
   */
  const openModal = useCallback(() => {
    setModalCss(css)
    setIsModalOpen(true)
  }, [css])

  /**
   * Handles saving from the modal editor
   */
  const handleModalSave = useCallback(() => {
    handleSave(modalCss, () => {
      setIsModalOpen(false)
    })
  }, [handleSave, modalCss])

  /**
   * Handles canceling the modal editor with unsaved changes confirmation
   */
  const handleModalCancel = useCallback(() => {
    const hasUnsavedChanges = modalCss !== css

    if (hasUnsavedChanges) {
      const confirmClose = window.confirm(
        l10n.unsaved_changes_message ||
        'You have unsaved changes. Do you really want to close without saving?'
      )

      if (!confirmClose) {
        return
      }
    }

    setIsModalOpen(false)
  }, [modalCss, css, l10n])

  return {
    // State
    css,
    setCss,
    modalCss,
    setModalCss,
    isSaving,
    isModalOpen,

    // Methods
    handleSave,
    updateEditorStyles,
    openModal,
    handleModalSave,
    handleModalCancel,

    // Computed values
    l10n
  }
}