import KntntErrorBoundary from './KntntErrorBoundary'
import { createHigherOrderComponent } from '@wordpress/compose'
import { PanelBody, Button } from '@wordpress/components'
import { useState, useEffect } from '@wordpress/element'
import { InspectorControls } from '@wordpress/block-editor'
import { hasBlockSupport } from '@wordpress/blocks'
import { __ } from '@wordpress/i18n'
import CreatableSelect from 'react-select/creatable'

/**
 * Component that hides the original WordPress "Additional CSS class(es)" control.
 *
 * Searches for and hides the default WordPress CSS class input field to prevent
 * confusion and duplicate functionality with our enhanced class selector.
 */
const HideWordPressCSSControl = () => {
  useEffect(() => {
    const coreLabel = __('Additional CSS class(es)')

    // Find the original control by its label text
    const labelElements = Array.from(
      document.querySelectorAll('.components-base-control__label')
    ).filter((label) => label.textContent === coreLabel)

    // Hide the control's parent container if found
    if (labelElements.length) {
      labelElements[0]?.parentElement?.parentElement?.classList.add('kntnt-global-styles-hidden')
    }
  }, [])

  return null
}

/**
 * Higher-order component that hides the original WordPress CSS control.
 *
 * Wraps the block editor to inject the HideWordPressCSSControl component
 * when a block with custom class name support is selected.
 *
 * @param {Function} BlockEdit - The original block edit component.
 * @returns {Function} Enhanced block edit component.
 */
export const withHiddenOriginalControl = createHigherOrderComponent((BlockEdit) => {
  return (props) => {
    const { isSelected, name } = props
    const hasCustomClassNameSupport = hasBlockSupport(name, 'customClassName', true)

    return (
      <>
        <BlockEdit {...props} />
        {isSelected && hasCustomClassNameSupport && (
          <InspectorControls group="advanced">
            <HideWordPressCSSControl/>
          </InspectorControls>
        )}
      </>
    )
  }
}, 'withHiddenOriginalControl')

/**
 * Higher-order component that adds the Global Styles panel to the block editor.
 *
 * Provides an enhanced CSS class selector with dropdown suggestions and
 * direct access to the CSS editor modal. Replaces the default WordPress
 * "Additional CSS class(es)" field with more user-friendly functionality.
 *
 * @param {Function} BlockEdit - The original block edit component.
 * @returns {Function} Enhanced block edit component with Global Styles panel.
 */
export const withGlobalStylesPanel = createHigherOrderComponent((BlockEdit) => {
  return (props) => {
    const { attributes, setAttributes, isSelected, name } = props
    const hasCustomClassNameSupport = hasBlockSupport(name, 'customClassName', true)
    const [availableHints, setAvailableHints] = useState({})

    /**
     * Dispatches custom event to open the CSS editor modal.
     */
    const openEditor = () => {
      document.dispatchEvent(new CustomEvent('kntnt-open-gse-modal'))
    }

    // Load initial CSS class hints from global data
    useEffect(() => {
      const hints = window.kntnt_global_styles_data?.available_hints || {}
      setAvailableHints(hints)
    }, [])

    /**
     * Custom formatting for dropdown option labels.
     *
     * Displays class name prominently with optional description below.
     *
     * @param {Object} option - The option object with label and description.
     * @returns {JSX.Element} Formatted option display.
     */
    const formatOptionLabel = ({ label, description }) => (
      <div className="kntnt-global-styles-select__option">
        <span className="kntnt-global-styles-select__option-name">{label}</span>
        {description && <span className="kntnt-global-styles-select__option-desc">{description}</span>}
      </div>
    )

    // Transform hints object into format expected by react-select
    const classSuggestions = Object.entries(availableHints).map(([className, description]) => ({
      value: className,
      label: className,
      description: description,
    }))

    // Parse current className attribute into react-select format
    const selectedClasses = attributes.className
      ? attributes.className.split(' ').filter(Boolean).map((className) => ({ value: className, label: className }))
      : []

    /**
     * Handles changes to selected CSS classes.
     *
     * Updates the block's className attribute with space-separated class names.
     *
     * @param {Array} selectedOptions - Array of selected option objects.
     */
    const handleClassChange = (selectedOptions) => {
      const classString = selectedOptions ? selectedOptions.map((option) => option.value).join(' ') : ''
      setAttributes({ className: classString })
    }

    /**
     * Prevents spaces in CSS class names during typing.
     *
     * CSS class names cannot contain spaces, so this prevents invalid input.
     *
     * @param {KeyboardEvent} event - The keyboard event.
     */
    const preventSpacesInClassNames = (event) => {
      if (event.key === ' ') {
        event.preventDefault()
      }
    }

    /**
     * Custom label for the "create new class" option.
     *
     * @param {string} inputValue - The value being typed by the user.
     * @returns {string} Formatted create label.
     */
    const formatCreateLabel = (inputValue) => {
      return __('Add', 'kntnt-global-styles') + ` "${inputValue}"`
    }

    // Listen for hint updates from the CSS editor
    useEffect(() => {
      /**
       * Handles refresh events from CSS editor modal.
       *
       * Updates available hints when CSS is modified in the editor.
       *
       * @param {CustomEvent} event - Custom event with hint data.
       */
      const handleRefresh = (event) => {
        const hints = event.detail?.hints || window.kntnt_global_styles_data?.available_hints || {}
        setAvailableHints(hints)
      }

      document.addEventListener('kntnt-global-styles-refresh', handleRefresh)
      return () => {
        document.removeEventListener('kntnt-global-styles-refresh', handleRefresh)
      }
    }, [])

    return (
      <>
        <BlockEdit {...props} />
        {isSelected && hasCustomClassNameSupport && (
          <InspectorControls>
            <KntntErrorBoundary>
              <PanelBody title={__('Global Styles', 'kntnt-global-styles')} initialOpen={true}>
                <div className="kntnt-global-styles-class-selector-wrapper">
                  <label className="components-base-control__label">
                    {__('CSS Classes', 'kntnt-global-styles')}
                  </label>
                  <CreatableSelect
                    isMulti
                    isClearable
                    onChange={handleClassChange}
                    options={classSuggestions}
                    value={selectedClasses}
                    classNamePrefix="kntnt-global-styles-select"
                    placeholder={__('Select or add classes…', 'kntnt-global-styles')}
                    onKeyDown={preventSpacesInClassNames}
                    formatCreateLabel={formatCreateLabel}
                    formatOptionLabel={formatOptionLabel}
                    noOptionsMessage={() => __('No classes available. Add some in the CSS editor.', 'kntnt-global-styles')}
                  />
                  <p className="components-base-control__help">
                    {__('Click the arrow or start typing to select a class from the drop-down list. Alternatively, type the full name of the class and finish by pressing the Enter or Tab key.', 'kntnt-global-styles')}
                  </p>
                </div>

                <Button variant="link" onClick={openEditor}>
                  {__('Edit Global Styles', 'kntnt-global-styles')}
                </Button>

              </PanelBody>
            </KntntErrorBoundary>
          </InspectorControls>
        )}
      </>
    )
  }
}, 'withGlobalStylesPanel')