/**
 * Main entry point for the Kntnt Global Styles block editor plugin.
 *
 * Registers a sidebar panel in the WordPress block editor that allows
 * users to write and save custom CSS with live preview functionality.
 */

import { registerPlugin } from '@wordpress/plugins'
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post'
import {
  Button,
  TextareaControl,
  Panel,
  PanelBody,
  PanelRow,
  Flex,
  FlexItem,
  Modal,
  __experimentalSpacer as Spacer
} from '@wordpress/components'
import { brush, fullscreen } from '@wordpress/icons'

import { useCssEditor } from './useCssEditor'
import { PLUGIN_NAME, PLUGIN_TITLE, UI_CONSTANTS, ELEMENT_IDS } from './constants'

/**
 * Global Styles Panel Component for the Block Editor sidebar.
 *
 * Provides a CSS editor interface within the WordPress block editor,
 * allowing users to write and save custom CSS that applies site-wide.
 */
const StyleEditorPanel = () => {
  const initialCss = window.kntntEditorPanel?.css_content || ''

  const {
    css,
    setCss,
    modalCss,
    setModalCss,
    isSaving,
    isModalOpen,
    handleSave,
    openModal,
    handleModalSave,
    handleModalCancel,
    l10n
  } = useCssEditor(initialCss)

  return (
    <>
      <PluginSidebarMoreMenuItem target={PLUGIN_NAME}>
        {PLUGIN_TITLE}
      </PluginSidebarMoreMenuItem>

      <PluginSidebar name={PLUGIN_NAME} title={PLUGIN_TITLE}>
        <Panel>
          <PanelBody
            title={l10n.css_editor_title || 'CSS Editor'}
            initialOpen={true}
          >
            <PanelRow>
              <div style={{ width: '100%' }}>
                <TextareaControl
                  label={l10n.css_label || 'Custom CSS'}
                  value={css}
                  onChange={setCss}
                  placeholder={l10n.css_placeholder || '/* Write your CSS here... */'}
                  help={l10n.css_help || 'The CSS will be visible live in the editor when you save.'}
                  rows={UI_CONSTANTS.TEXTAREA_ROWS}
                  aria-describedby={ELEMENT_IDS.CSS_HELP}
                  aria-label={l10n.css_aria_label || 'CSS Editor'}
                  style={{
                    fontFamily: 'monospace',
                    minHeight: UI_CONSTANTS.TEXTAREA_MIN_HEIGHT,
                    resize: 'vertical'
                  }}
                />
                <div
                  id={ELEMENT_IDS.CSS_HELP}
                  className="screen-reader-text"
                  aria-live="polite"
                >
                  {l10n.css_help || 'The CSS will be visible live in the editor when you save.'}
                </div>
              </div>
            </PanelRow>

            <Spacer marginTop={UI_CONSTANTS.SPACER_MARGIN}/>

            <PanelRow>
              <Flex justify="space-between" gap={2}>
                <FlexItem>
                  <Button
                    variant="tertiary"
                    onClick={openModal}
                    icon={fullscreen}
                    iconSize={16}
                    aria-label={l10n.open_editor_aria || 'Open larger CSS editor'}
                  >
                    {l10n.open_editor || 'Larger Editor'}
                  </Button>
                </FlexItem>
                <FlexItem>
                  <Button
                    variant="primary"
                    onClick={() => handleSave()}
                    isBusy={isSaving}
                    disabled={isSaving}
                    aria-label={isSaving ? l10n.saving_aria || 'Saving CSS...' : l10n.save_aria || 'Save CSS'}
                  >
                    {isSaving ? (l10n.saving || 'Saving...') : (l10n.save || 'Save')}
                  </Button>
                </FlexItem>
              </Flex>
            </PanelRow>
          </PanelBody>
        </Panel>

        {/* Modal Editor */}
        {isModalOpen && (
          <Modal
            title={l10n.modal_title || 'Global Styles Editor'}
            onRequestClose={handleModalCancel}
            isDismissible={true}
            size="large"
            style={{
              maxWidth: UI_CONSTANTS.MODAL_MAX_WIDTH,
              maxHeight: UI_CONSTANTS.MODAL_MAX_HEIGHT
            }}
            aria-describedby="modal-css-editor-description"
          >
            <div
              style={{
                minHeight: '60vh',
                display: 'flex',
                flexDirection: 'column'
              }}
            >
              <div
                id="modal-css-editor-description"
                className="screen-reader-text"
              >
                {l10n.modal_description || 'Large CSS editor for writing custom styles'}
              </div>

              <div style={{ flex: 1, marginBottom: '20px' }}>
                <TextareaControl
                  label={l10n.css_label || 'Custom CSS'}
                  value={modalCss}
                  onChange={setModalCss}
                  placeholder={l10n.css_placeholder || '/* Write your CSS here... */'}
                  help={l10n.css_help || 'The CSS will be visible live in the editor when you save.'}
                  rows={UI_CONSTANTS.MODAL_TEXTAREA_ROWS}
                  aria-label={l10n.modal_css_aria_label || 'Large CSS Editor'}
                  style={{
                    fontFamily: 'monospace',
                    minHeight: UI_CONSTANTS.MODAL_TEXTAREA_MIN_HEIGHT,
                    resize: 'vertical',
                    width: '100%'
                  }}
                />
              </div>

              <Flex
                justify="flex-end"
                gap={2}
                style={{ marginTop: 'auto' }}
                role="group"
                aria-label={l10n.modal_actions_aria || 'Modal actions'}
              >
                <FlexItem>
                  <Button
                    variant="tertiary"
                    onClick={handleModalCancel}
                    disabled={isSaving}
                    aria-label={l10n.cancel_aria || 'Cancel and close editor'}
                  >
                    {l10n.cancel || 'Cancel'}
                  </Button>
                </FlexItem>
                <FlexItem>
                  <Button
                    variant="primary"
                    onClick={handleModalSave}
                    isBusy={isSaving}
                    disabled={isSaving}
                    aria-label={isSaving ? l10n.saving_aria || 'Saving CSS...' : l10n.save_aria || 'Save CSS'}
                  >
                    {isSaving ? (l10n.saving || 'Saving...') : (l10n.save || 'Save')}
                  </Button>
                </FlexItem>
              </Flex>
            </div>
          </Modal>
        )}
      </PluginSidebar>
    </>
  )
}

// Register the plugin with WordPress
registerPlugin('kntnt-global-styles-panel', {
  render: StyleEditorPanel,
  icon: brush,
})