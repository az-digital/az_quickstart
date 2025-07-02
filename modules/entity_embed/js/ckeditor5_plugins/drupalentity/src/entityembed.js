import EntityEmbedEditing from './editing';
import EntityEmbedToolbar from './toolbar';
import EntityEmbedUI from './ui';
import EntityEmbedGeneralHtmlSupport from './generalhtmlsupport';
import EntityEmbedLink from './link';
import { Plugin } from 'ckeditor5/src/core';

export default class EntityEmbed extends Plugin {

  static get requires() {
    return [
      EntityEmbedEditing,
      EntityEmbedUI,
      EntityEmbedToolbar,
      EntityEmbedGeneralHtmlSupport,
      EntityEmbedLink,
    ];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EntityEmbed';
  }

}
