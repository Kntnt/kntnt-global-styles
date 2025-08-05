import { Button, Modal, TextareaControl, Notice } from '@wordpress/components'
import { useState, useEffect } from '@wordpress/element'
import { __ } from '@wordpress/i18n'

/**
 * Modal component for editing global CSS styles.
 *
 * Provides a large textarea for CSS editing with live preview functionality.
 * Changes are previewed immediately in the editor but only persisted when
 * the document is saved, implementing a draft/publish workflow.
 *
 * @param {Object} props - Component props.
 * @param {boolean} props.isOpen - Whether the modal is currently open.
 * @param {Function} props.onClose - Callback function to close the modal.
 * @param {Function} props.onSave - Callback function when CSS is saved successfully.
 * @returns {JSX.Element|null} The modal component or null if closed.
 */
export const CSSEditorModal = ({ isOpen, onClose, onSave }) => {
  const [globalCss, setGlobalCss] = useState('')
  const [initialCss, setInitialCss] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false)
  const [notice, setNotice] = useState(null)

// Initialize CSS content when modal opens
  useEffect(() => {
    if (isOpen) {
      const persistedCss = window.kntnt_global_styles_data?.css_content || ''
      // Check if draft exists (not undefined), use it even if empty string
      const currentDraft = window.kntnt_global_styles_draft !== undefined ? window.kntnt_global_styles_draft : persistedCss

      setGlobalCss(currentDraft)
      setInitialCss(currentDraft)  // Store what we loaded
      setHasUnsavedChanges(false)  // No changes yet when just opened
    }
  }, [isOpen])

  /**
   * Handles changes to the CSS content in the textarea.
   *
   * Updates local state and draft storage, tracks unsaved changes
   * by comparing with the persisted version.
   *
   * @param {string} css - The new CSS content.
   */
  const handleCssChange = (css) => {
    setGlobalCss(css)
    // Compare with what was initially loaded, not with persisted
    setHasUnsavedChanges(css.trim() !== initialCss.trim())

    // Store draft for persistence when document is saved
    window.kntnt_global_styles_draft = css
  }

  /**
   * Handles the save/preview action.
   *
   * Updates the live preview in the editor and parses @hint annotations
   * for immediate availability in the class selector. Does not persist
   * to database - that happens when the document is saved.
   */
  const handleSave = async () => {
    setIsSaving(true)
    setNotice(null)

    try {
      // Store draft CSS for document save persistence
      window.kntnt_global_styles_draft = globalCss

      // Parse hints from current CSS for immediate class selector updates
      const hints = parseHintsFromCSS(globalCss)

      // Update global hints data for other components
      if (window.kntnt_global_styles_data) {
        window.kntnt_global_styles_data.available_hints = hints
      }

      // Apply live preview styles to editor
      if (window.kntntUpdateEditorStyles) {
        window.kntntUpdateEditorStyles(globalCss)
      }

      // Mark document as having CSS changes for save persistence
      if (wp.data?.dispatch('core/editor')) {
        wp.data.dispatch('core/editor').editPost({ meta: { _kntnt_css_changed: Date.now() } })
      }

      // Notify other components of hints update
      document.dispatchEvent(new CustomEvent('kntnt-global-styles-refresh'))

      // Notify parent component of successful save
      if (onSave) {
        onSave(globalCss, hints)
      }

      // Update initial state since user has confirmed these changes
      setInitialCss(globalCss)
      setHasUnsavedChanges(false)

      onClose()
    } catch (error) {
      console.error('Kntnt Global Styles: Live preview error:', error)
      setNotice({
        type: 'error',
        message: error.message || __('Error updating live preview', 'kntnt-global-styles'),
      })
    } finally {
      setIsSaving(false)
    }
  }

  /**
   * Parses @hint annotations from CSS content.
   *
   * Extracts class names and descriptions from special comment annotations
   * using the same regex pattern as the PHP backend for consistency.
   *
   * @param {string} css - The CSS content to parse.
   * @returns {Object} Object mapping class names to descriptions.
   */
  const parseHintsFromCSS = (css) => {
    const hints = {}

    // Use regex pattern matching the PHP implementation
    const pattern = /^\s*\/?\*+\s@hint\s+(?<name>\S+)\s*(?:\|\s*(?<description>.*?)\s*)?(?:\*\/.*)?$/gm

    let match
    while ((match = pattern.exec(css)) !== null) {
      const className = match.groups.name?.trim()
      const description = match.groups.description?.trim() || ''

      // Validate CSS class name format
      if (className && /^[a-zA-Z][\w-]*$/.test(className)) {
        hints[className] = description
      }
    }
    return hints
  }

  /**
   * Handles modal close requests.
   *
   * Prompts user for confirmation if there are unsaved changes,
   * otherwise closes immediately and cleans up draft storage.
   */
  const handleClose = () => {
    if (hasUnsavedChanges) {
      const confirmClose = window.confirm(
        __('You have unsaved changes. Do you really want to close without saving?', 'kntnt-global-styles')
      )
      if (!confirmClose) {
        return // User cancelled the close action
      }

      // Restore draft to what was initially loaded (discard uncommitted changes)
      window.kntnt_global_styles_draft = initialCss
    }

    // Don't delete draft - it should persist between modal opens
    setNotice(null)
    onClose()
  }

  // Don't render modal when closed
  if (!isOpen) {
    return null
  }

  return (
    <Modal
      title={__('Edit Global Styles', 'kntnt-global-styles')}
      onRequestClose={handleClose}
      className="kntnt-global-styles-editor"
    >
      {notice && (
        <Notice status={notice.type} isDismissible={true} onRemove={() => setNotice(null)}>
          {notice.message}
        </Notice>
      )}
      <TextareaControl
        label={__('Global CSS Editor', 'kntnt-global-styles')}
        help={__('Define your global CSS classes here. Use @hint to make them available in the CSS selector. Changes are previewed in the editor. Save the document to make the changes permanent.', 'kntnt-global-styles')}
        value={globalCss}
        onChange={handleCssChange}
        placeholder={__('/* Your global CSS here... */', 'kntnt-global-styles')}
        style={{ fontFamily: 'monospace', fontSize: '13px' }}
      />
      <div className="kntnt-global-styles-editor-buttons">
        <Button variant="secondary" onClick={handleClose}>
          {__('Cancel', 'kntnt-global-styles')}
        </Button>
        <Button variant="primary" onClick={handleSave} isBusy={isSaving}>
          {isSaving ? __('Updating preview...', 'kntnt-global-styles') : __('Update Preview', 'kntnt-global-styles')}
        </Button>
      </div>
    </Modal>
  )
}