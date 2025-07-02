/* eslint-disable import/no-extraneous-dependencies */

import { Plugin } from 'ckeditor5/src/core';
import EntityEmbedLinkEditing from './linkediting';
import EntityEmbedLinkUI from './linkui';

/**
 * @private
 */
export default class EntityEmbedLink extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [EntityEmbedLinkEditing, EntityEmbedLinkUI];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EntityEmbedLink';
  }
}
