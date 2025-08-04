import { Component } from '@wordpress/element'
import { __ } from '@wordpress/i18n'

/**
 * React Error Boundary component for the Global Styles panel.
 *
 * Catches JavaScript errors anywhere in the child component tree and
 * displays a fallback UI instead of crashing the entire editor interface.
 * Essential for maintaining editor stability when the plugin encounters errors.
 */
class KntntErrorBoundary extends Component {
  /**
   * Initialize component with error state.
   *
   * @param {Object} props - Component props passed from parent.
   */
  constructor (props) {
    super(props)
    this.state = { hasError: false }
  }

  /**
   * React lifecycle method called when an error is caught.
   *
   * Updates component state to trigger fallback UI rendering.
   * This is a static method that must return the new state.
   *
   * @param {Error} error - The error that was thrown.
   * @returns {Object} New state object to trigger fallback UI.
   */
  static getDerivedStateFromError (error) {
    // Update state to show fallback UI
    return { hasError: true }
  }

  /**
   * React lifecycle method for handling caught errors.
   *
   * Called after an error has been caught. Used for logging
   * error information for debugging purposes.
   *
   * @param {Error} error - The error that was thrown.
   * @param {Object} errorInfo - React-specific error information.
   */
  componentDidCatch (error, errorInfo) {
    // Log error details for debugging
    console.error('Uncaught error in Kntnt Global Styles panel:', error, errorInfo)
  }

  /**
   * Renders either the fallback UI or normal children.
   *
   * @returns {JSX.Element} Either error message or child components.
   */
  render () {
    if (this.state.hasError) {
      // Render fallback UI when error occurs
      return (
        <div style={{ padding: '12px', border: '1px solid #d63638', color: '#d63638' }}>
          {__('The Global Styles panel encountered an error and could not be displayed.', 'kntnt-global-styles')}
        </div>
      )
    }

    // Render children normally when no error
    return this.props.children
  }
}

export default KntntErrorBoundary