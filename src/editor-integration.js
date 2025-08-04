import KntntErrorBoundary from './KntntErrorBoundary'
import { useState, useEffect } from '@wordpress/element'
import { registerPlugin } from '@wordpress/plugins'
import { CSSEditorModal } from './css-editor'
import { useDispatch, useSelect } from '@wordpress/data'
import { __ } from '@wordpress/i18n'
import { PluginMoreMenuItem } from '@wordpress/editor'
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts'
import { useCommand } from '@wordpress/commands'

/**
 * Custom event name for opening the Global Styles Editor modal.
 * Used for communication between different parts of the plugin.
 */
const OPEN_MODAL_EVENT = 'kntnt-open-gse-modal'

/**
 * Modal manager component that handles the CSS editor modal state.
 *
 * Listens for open events and manages the modal lifecycle, including
 * success notifications and data refresh after CSS changes.
 */
const ModalManager = () => {
  const [isModalOpen, setModalOpen] = useState(false)
  const { createNotice } = useDispatch('core/notices')

  // Listen for modal open events from various triggers
  useEffect(() => {
    const handleOpenEvent = () => setModalOpen(true)
    document.addEventListener(OPEN_MODAL_EVENT, handleOpenEvent)
    return () => {
      document.removeEventListener(OPEN_MODAL_EVENT, handleOpenEvent)
    }
  }, [])

  const closeModal = () => setModalOpen(false)

  /**
   * Handles successful CSS save operations.
   *
   * Shows success notification and dispatches refresh events to update
   * other plugin components with new CSS hints and content.
   *
   * @param {string} cssContent - The saved CSS content.
   * @param {Object} newHints - Updated CSS class hints from the saved CSS.
   */
  const onSaveSuccess = (cssContent, newHints) => {
    createNotice(
      'success',
      __('CSS updated in editor. Save the document to make changes permanent.', 'kntnt-global-styles'),
      { type: 'snackbar', isDismissible: true }
    )

    // Notify other components about hints update
    document.dispatchEvent(new CustomEvent('kntnt-global-styles-refresh', { detail: { hints: newHints } }))
  }

  return (
    <KntntErrorBoundary>
      <CSSEditorModal
        isOpen={isModalOpen}
        onClose={closeModal}
        onSave={onSaveSuccess}
      />
    </KntntErrorBoundary>
  )
}

// Register the modal manager with WordPress plugin system
registerPlugin('kntnt-global-styles-modal-manager', {
  render: ModalManager,
})

/**
 * More menu item component for the WordPress editor toolbar.
 *
 * Adds "Edit Global Styles" option to the three-dot menu in the editor
 * with keyboard shortcut display integration.
 */
const GlobalStylesMoreMenu = () => {
  // Get keyboard shortcut representation from WordPress
  const shortcutDisplay = useSelect((select) => {
    try {
      const keyboardShortcutsStore = select('core/keyboard-shortcuts')
      if (!keyboardShortcutsStore || typeof keyboardShortcutsStore.getShortcutRepresentation !== 'function') {
        return null
      }
      return keyboardShortcutsStore.getShortcutRepresentation('kntnt-global-styles/open-gse')
    } catch (error) {
      return null
    }
  }, [])

  return (
    <PluginMoreMenuItem
      icon="admin-customizer"
      onClick={() => {
        document.dispatchEvent(new CustomEvent(OPEN_MODAL_EVENT))
      }}
      shortcut={shortcutDisplay}
    >
      {__('Edit Global Styles', 'kntnt-global-styles')}
    </PluginMoreMenuItem>
  )
}

// Register the more menu item
registerPlugin('kntnt-global-styles-more-menu-item', {
  render: GlobalStylesMoreMenu,
})

/**
 * Keyboard shortcut registration component.
 *
 * Registers Cmd/Ctrl+Shift+G shortcut to open the CSS editor modal.
 * Provides both WordPress API integration and fallback manual event handling.
 */
const ShortcutRegistration = () => {
  const { registerShortcut } = useDispatch(keyboardShortcutsStore) || {}

  useEffect(() => {
    // Try WordPress shortcut API first
    if (registerShortcut && typeof registerShortcut === 'function') {
      try {
        registerShortcut({
          name: 'kntnt-global-styles/open-gse',
          category: 'global',
          description: __('Open Global Style Editor (Cmd+Shift+G)', 'kntnt-global-styles'),
          keyCombination: {
            modifier: 'primaryShift', // Cmd+Shift on Mac, Ctrl+Shift on PC
            character: 'g',
          },
          callback: () => {
            document.dispatchEvent(new CustomEvent(OPEN_MODAL_EVENT))
          },
        })
      } catch (error) {
        console.warn('Kntnt Global Styles: WordPress shortcut registration failed:', error)
      }
    }

    // Fallback manual keyboard listener for reliability
    const handleKeyDown = (event) => {
      const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0
      const isCorrectModifier = isMac
        ? (event.metaKey && event.shiftKey && !event.ctrlKey && !event.altKey) // Cmd+Shift on Mac
        : (event.ctrlKey && event.shiftKey && !event.metaKey && !event.altKey) // Ctrl+Shift on PC

      if (isCorrectModifier && event.key.toLowerCase() === 'g') {
        // Ensure we're in block editor context
        if (document.querySelector('.block-editor') || document.querySelector('.edit-post-layout')) {
          event.preventDefault()
          event.stopPropagation()
          document.dispatchEvent(new CustomEvent(OPEN_MODAL_EVENT))
        }
      }
    }

    // Use capture phase to ensure we get the event before other handlers
    document.addEventListener('keydown', handleKeyDown, true)

    return () => {
      document.removeEventListener('keydown', handleKeyDown, true)
    }
  }, [registerShortcut])

  return null
}

// Register the shortcut handler
registerPlugin('kntnt-global-styles-shortcut-registration', {
  render: ShortcutRegistration,
})

/**
 * Command palette registration component.
 *
 * Adds "Open Global Style Editor" command to the WordPress command palette
 * with searchable terms for easy discovery.
 */
const CommandPaletteRegistration = () => {
  // Get shortcut representation for command palette display
  const shortcutDisplay = useSelect((select) => {
    try {
      const keyboardShortcutsStore = select('core/keyboard-shortcuts')
      if (!keyboardShortcutsStore || typeof keyboardShortcutsStore.getShortcutRepresentation !== 'function') {
        return null
      }
      return keyboardShortcutsStore.getShortcutRepresentation('kntnt-global-styles/open-gse')
    } catch (error) {
      return null
    }
  }, [])

  // Register command with WordPress Command Palette
  useCommand({
    name: 'kntnt-global-styles/open-editor',
    label: __('Open Global Style Editor', 'kntnt-global-styles'),
    callback: () => {
      document.dispatchEvent(new CustomEvent(OPEN_MODAL_EVENT))
    },
    context: 'block-editor',
    // Search terms for improved discoverability
    searchTerms: [
      __('global', 'kntnt-global-styles'),
      __('styles', 'kntnt-global-styles'),
      __('editor', 'kntnt-global-styles'),
      shortcutDisplay || ''
    ].filter(Boolean)
  })

  return null
}

// Register the command palette command
registerPlugin('kntnt-global-styles-command-palette', {
  render: CommandPaletteRegistration,
})