/**
 * @file Registers the entity embed button(s) to the CKEditor instance(s) and binds functionality to it/them.
 */

import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';
import defaultIcon from '../entity.svg';

export default class EntityEmbedUI extends Plugin {

  /**
   * @inheritdoc
   */
  static get requires() {
    return ['Widget'];
  }

  /**
   * @inheritdoc
   */
  init() {
    const editor = this.editor;
    const command = editor.commands.get('insertEntityEmbed');
    const options = editor.config.get('entityEmbed');
    if (!options) {
      return;
    }
    const { dialogSettings = {} } = options;
    const embed_buttons = options.buttons;

    // Register each embed button to the toolbar based on configuration.
    Object.keys(embed_buttons).forEach(id => {
      const libraryURL = Drupal.url('entity-embed/dialog/' + options.format + '/' + id);
      // Add each button to the toolbar.
      editor.ui.componentFactory.add(id, (locale) => {
        const button = embed_buttons[id];
        const buttonView = new ButtonView(locale);
        // Set the icon to the SVG from config, or set it to the default icon.
        // If the uploaded icon is an SVG, load it. If the icon was provided for
        // the button in a pre-CKEditor 5 Drupal instance, it may use a format
        // other than SVG. In that case it remains null and the default Entity
        // embed icon will be used.
        let icon = null;
        if (button.icon.endsWith('svg')) {
          let XMLrequest = new XMLHttpRequest();
          XMLrequest.open('GET', button.icon, false);
          XMLrequest.send(null);
          icon = XMLrequest.response;
        } else {
          console.warn(`CKEditor 5 only supports enity embed icons in SVG format. The icon provided is ${button.icon}`);
        }


        buttonView.set({
          label: button.label,
          icon: icon ?? defaultIcon,
          tooltip: true,
        });
        buttonView.bind('isOn', 'isEnabled').to(command, 'value', 'isEnabled');

        this.listenTo(buttonView, 'execute', () =>
          // Open a dialog to select entity to embed.
          Drupal.ckeditor5.openDialog(
            libraryURL,
            ({ attributes }) => {
              editor.execute('insertEntityEmbed', attributes);
            },
            dialogSettings,
          ),
        );

        return buttonView;
      })
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EntityEmbedUI';
  }

}
