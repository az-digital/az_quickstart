import { Plugin } from 'ckeditor5/src/core';
import LinkitEditing from './linkitediting';
import initializeAutocomplete from './autocomplete';

class Linkit extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [LinkitEditing];
  }

  init() {
    this._state = {};
    const editor = this.editor;
    const options = editor.config.get('linkit');
    // TRICKY: Work-around until the CKEditor team offers a better solution: force the ContextualBalloon to get instantiated early thanks to DrupalImage not yet being optimized like https://github.com/ckeditor/ckeditor5/commit/c276c45a934e4ad7c2a8ccd0bd9a01f6442d4cd3#diff-1753317a1a0b947ca8b66581b533616a5309f6d4236a527b9d21ba03e13a78d8.
    editor.plugins.get('LinkUI')._createViews();
    this._enableLinkAutocomplete();
    this._handleExtraFormFieldSubmit();
    this._handleDataLoadingIntoExtraFormField();
  }

  _enableLinkAutocomplete() {
    const editor = this.editor;
    const options = editor.config.get('linkit');
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkitInput = linkFormView.urlInputView.fieldView.element;
    let wasAutocompleteAdded = false;

    linkFormView.extendTemplate({
      attributes: {
        class: ['ck-vertical-form', 'ck-link-form_layout-vertical'],
      },
    });

    editor.plugins
      .get('ContextualBalloon')
      .on('set:visibleView', (evt, propertyName, newValue, oldValue) => {
        if (newValue !== linkFormView || wasAutocompleteAdded) {
          return;
        }

      /**
       * Used to know if a selection was made from the autocomplete results.
       *
       * @type {boolean}
       */
      let selected;

      initializeAutocomplete(
        linkitInput,
        {
          ...options,
          selectHandler: (event, { item }) => {
            if (!item.path) {
              throw 'Missing path param.' + JSON.stringify(item);
            }

            if (item.entity_type_id || item.entity_uuid || item.substitution_id) {
              if (!item.entity_type_id || !item.entity_uuid || !item.substitution_id) {
                throw 'Missing path param.' + JSON.stringify(item);
              }

              this.set('entityType', item.entity_type_id);
              this.set('entityUuid', item.entity_uuid);
              this.set('entitySubstitution', item.substitution_id);
            }
            else {
              this.set('entityType', null);
              this.set('entityUuid', null);
              this.set('entitySubstitution', null);
            }

            linkFormView.urlInputView.fieldView.set('value', item.path);
            selected = true;
            return false;
          },
          openHandler: (event) => {
            selected = false;
          },
          closeHandler: (event) => {
            // Upon close, ensure there is no selection (#3447669).
            selected = false;
          },
        },
      );

      wasAutocompleteAdded = true;
      linkFormView.urlInputView.fieldView.template.attributes.class.push('form-linkit-autocomplete');
    });
  }

  _handleExtraFormFieldSubmit() {
    const editor = this.editor;
    const linkFormView = editor.plugins.get('LinkUI').formView;
    const linkCommand = editor.commands.get('link');

    // Only selections from autocomplete set converter attributes.
    const linkit = editor.plugins.get('Linkit');
    linkFormView.urlInputView.fieldView.element.addEventListener('input', function (evt) {
      linkit.set('entityType', null);
      linkit.set('entityUuid', null);
      linkit.set('entitySubstitution', null);
    });

    this.listenTo(linkFormView, 'submit', () => {
      const values = {
        'linkDataEntityType': this.entityType,
        'linkDataEntityUuid': this.entityUuid,
        'linkDataEntitySubstitution': this.entitySubstitution,
      }
      // Stop the execution of the link command caused by closing the form.
      // Inject the extra attribute value. The highest priority listener here
      // injects the argument (here below 👇).
      // - The high priority listener in
      //   _addExtraAttributeOnLinkCommandExecute() gets that argument and sets
      //   the extra attribute.
      // - The normal (default) priority listener in ckeditor5-link sets
      //   (creates) the actual link.
      linkCommand.once('execute', (evt, args) => {
        if (args.length < 3) {
          args.push(values);
        } else if (args.length === 3) {
          Object.assign(args[2], values);
        } else {
          throw Error('The link command has more than 3 arguments.')
        }
      }, { priority: 'highest' });
    }, { priority: 'high' });
  }

  _handleDataLoadingIntoExtraFormField() {
    const editor = this.editor;
    const linkCommand = editor.commands.get('link');
    this.bind('entityType').to(linkCommand, 'linkDataEntityType');
    this.bind('entityUuid').to(linkCommand, 'linkDataEntityUuid');
    this.bind('entitySubstitution').to(linkCommand, 'linkDataEntitySubstitution');
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'Linkit';
  }
}

export default {
  Linkit,
};
