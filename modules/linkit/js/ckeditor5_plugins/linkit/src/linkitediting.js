import { Plugin } from 'ckeditor5/src/core';
import { findAttributeRange } from 'ckeditor5/src/typing';

export default class LinkitEditing extends Plugin {
  init() {
    this.attrs = ['linkDataEntityType', 'linkDataEntityUuid', 'linkDataEntitySubstitution'];
    this.attrsView = ['data-entity-type', 'data-entity-uuid', 'data-entity-substitution'];
    this._allowAndConvertExtraAttributes();
    this._removeExtraAttributesOnUnlinkCommandExecute();
    this._refreshExtraAttributeValues();
    this._addExtraAttributesOnLinkCommandExecute();
  }

  _allowAndConvertExtraAttributes() {
    const editor = this.editor;

    editor.model.schema.extend('$text', { allowAttributes: this.attrs });

    // Model -> View (DOM)
    this.attrs.forEach((attribute, i) => {
      editor.conversion.for('downcast').attributeToElement({
        model: attribute,
        view: (value, { writer }) => {
          const linkViewElement = writer.createAttributeElement('a', {
            [this.attrsView[i]]: value
          }, { priority: 5 });

          // Without it the isLinkElement() will not recognize the link and the UI will not show up
          // when the user clicks a link.
          writer.setCustomProperty('link', true, linkViewElement);

          return linkViewElement;
        }
      });

      // View (DOM/DATA) -> Model
      editor.conversion.for('upcast')
        .elementToAttribute({
          view: {
            name: 'a',
            attributes: {
              [this.attrsView[i]]: true,
            }
          },
          model: {
            key: attribute,
            value: viewElement => viewElement.getAttribute(this.attrsView[i]),
          }
        });
    });
  }

  _addExtraAttributesOnLinkCommandExecute() {
    const editor = this.editor;
    const linkCommand = editor.commands.get('link');
    let linkCommandExecuting = false;

    linkCommand.on('execute', (evt, args) => {
      // Custom handling is only required if an extra attribute was passed into
      // editor.execute( 'link', ... ).
      if (args.length < 3) {
        return;
      }
      if (linkCommandExecuting) {
        linkCommandExecuting = false;
        return;
      }

      // If the additional attribute was passed, we stop the default execution
      // of the LinkCommand. We're going to create Model#change() block for undo
      // and execute the LinkCommand together with setting the extra attribute.
      evt.stop();

      // Prevent infinite recursion by keeping records of when link command is
      // being executed by this function.
      linkCommandExecuting = true;
      const extraAttributeValues = args[args.length - 1];
      const model = this.editor.model;
      const selection = model.document.selection;

      // Wrapping the original command execution in a model.change() block to
      // make sure there's a single undo step when the extra attribute is added.
      model.change((writer) => {
        editor.execute('link', ...args);

        const firstPosition = selection.getFirstPosition();

        this.attrs.forEach((attribute) => {
          if (selection.isCollapsed) {
            const node = firstPosition.textNode || firstPosition.nodeBefore;

            if (extraAttributeValues[attribute]) {
              writer.setAttribute(attribute, extraAttributeValues[attribute], writer.createRangeOn(node));
            } else {
              writer.removeAttribute(attribute, writer.createRangeOn(node));
            }

            writer.removeSelectionAttribute(attribute);
          } else {
            const ranges = model.schema.getValidRanges(selection.getRanges(), attribute);

            for (const range of ranges) {
              if (extraAttributeValues[attribute]) {
                writer.setAttribute(attribute, extraAttributeValues[attribute], range);
              } else {
                writer.removeAttribute(attribute, range);
              }
            }
          }
        });
      });
    }, { priority: 'high' } );
  }

  _removeExtraAttributesOnUnlinkCommandExecute() {
    const editor = this.editor;
    const unlinkCommand = editor.commands.get('unlink');
    const model = this.editor.model;
    const selection = model.document.selection;

    let isUnlinkingInProgress = false;

    // Make sure all changes are in a single undo step so cancel the original unlink first in the high priority.
    unlinkCommand.on('execute', evt => {
      if (isUnlinkingInProgress) {
        return;
      }

      evt.stop();

      // This single block wraps all changes that should be in a single undo step.
      model.change(() => {
        // Now, in this single "undo block" let the unlink command flow naturally.
        isUnlinkingInProgress = true;

        // Do the unlinking within a single undo step.
        editor.execute('unlink');

        // Let's make sure the next unlinking will also be handled.
        isUnlinkingInProgress = false;

        // The actual integration that removes the extra attribute.
        model.change(writer => {
          // Get ranges to unlink.
          let ranges;

          this.attrs.forEach((attribute) => {
            if (selection.isCollapsed) {
              ranges = [findAttributeRange(
                selection.getFirstPosition(),
                attribute,
                selection.getAttribute(attribute),
                model
              )];
            } else {
              ranges = model.schema.getValidRanges(selection.getRanges(), attribute);
            }

            // Remove the extra attribute from specified ranges.
            for (const range of ranges) {
              writer.removeAttribute(attribute, range);
            }
          });
        });
      });
    }, { priority: 'high' });
  }

  _refreshExtraAttributeValues() {
    const editor = this.editor;
    const attributes = this.attrs
    const linkCommand = editor.commands.get('link');
    const model = this.editor.model;
    const selection = model.document.selection;

    attributes.forEach((attribute) => {
      linkCommand.set(attribute, null);
    });
    model.document.on('change', () => {
      attributes.forEach((attribute) => {
        linkCommand[attribute] = selection.getAttribute(attribute);
      });
    });
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'LinkitEditing';
  }
}
