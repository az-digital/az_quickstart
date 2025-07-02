/* eslint-disable import/no-extraneous-dependencies */
// cSpell:words linkui
import { Plugin, icons } from 'ckeditor5/src/core';
import { LINK_KEYSTROKE } from '@ckeditor/ckeditor5-link/src/utils';
import { ButtonView } from 'ckeditor5/src/ui';
import linkIcon from '@ckeditor/ckeditor5-link/theme/icons/link.svg';

/**
 * The link entity embed UI plugin.
 *
 * @private
 */
export default class EntityEmbedLinkUI extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return ['LinkEditing', 'LinkUI', 'EntityEmbedEditing', 'EntityEmbedUI'];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EntityEmbedLinkUi';
  }

  /**
   * @inheritdoc
   */
  init() {
    const { editor } = this;
    const viewDocument = editor.editing.view.document;

    this.listenTo(
      viewDocument,
      'click',
      (evt, data) => {
        if (
          this._isSelectedLinkedEntityEmbed(editor.model.document.selection)
        ) {
          // Prevent browser navigation when clicking a linked entity embed.
          data.preventDefault();

          // Block the `LinkUI` plugin when a entity embed was clicked. In such a case,
          // we'd like to display the entity embed toolbar.
          evt.stop();
        }
      },
      { priority: 'high' }
    );

    this._createToolbarLinkEntityEmbedButton();
  }

  /**
   * Creates a linking button view.
   *
   * Clicking this button shows a {@link module:link/linkui~LinkUI#_balloon}
   * attached to the selection. When an embedded entity is already linked, the
   * view shows {@link module:link/linkui~LinkUI#actionsView} or
   * {@link module:link/linkui~LinkUI#formView} if it is not.
   */
  _createToolbarLinkEntityEmbedButton() {
    const { editor } = this;

    editor.ui.componentFactory.add('entityEmbedLink', (locale) => {
      const button = new ButtonView(locale);
      const plugin = editor.plugins.get('LinkUI');
      const linkCommand = editor.commands.get('link');

      button.set({
        isEnabled: true,
        label: Drupal.t('Link entity embed'),
        icon: linkIcon,
        keystroke: LINK_KEYSTROKE,
        tooltip: true,
        isToggleable: true,
      });

      // Bind button to the command.
      button.bind('isEnabled').to(linkCommand, 'isEnabled');
      button.bind('isOn').to(linkCommand, 'value', (value) => !!value);

      // Show the actionsView or formView (both from LinkUI) on button click
      // depending on whether the entity embed is already linked.
      this.listenTo(button, 'execute', () => {
        if (
          this._isSelectedLinkedEntityEmbed(editor.model.document.selection)
        ) {
          plugin._addActionsView();
        } else {
          plugin._showUI(true);
        }
      });

      return button;
    });
  }

  /**
   * Returns true if a linked entity embed is the only selected element in the model.
   *
   * @param {module:engine/model/selection~Selection} selection
   * @return {Boolean}
   */
  // eslint-disable-next-line class-methods-use-this
  _isSelectedLinkedEntityEmbed(selection) {
    const selectedModelElement = selection.getSelectedElement();
    return (
      !!selectedModelElement &&
      selectedModelElement.is('element', 'drupalEntity') &&
      selectedModelElement.hasAttribute('linkHref')
    );
  }
}
