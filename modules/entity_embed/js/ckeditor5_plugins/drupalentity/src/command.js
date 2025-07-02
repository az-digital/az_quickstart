import {Command} from 'ckeditor5/src/core';

export default class InsertEntityEmbedCommand extends Command {

  execute(attributes) {
    const { model } = this.editor;
    const entityEmbedEditing = this.editor.plugins.get('EntityEmbedEditing');

    // Create object that contains supported data-attributes in view data by
    // flipping `EntityEmbedEditing.attrs` object (i.e. keys from object become
    // values and values from object become keys).
    const dataAttributeMapping = Object.fromEntries(
      Object.entries(entityEmbedEditing.attrs).map(([key, value]) => [value, key])
    );

    // \Drupal\entity_embed\Form\EntityEmbedDialog returns data in keyed by
    // data-attributes used in view data. This converts data-attribute keys to
    // keys used in model.
    const modelAttributes = Object.fromEntries(
      Object.keys(dataAttributeMapping)
        .filter((attribute) => attributes[attribute])
        .map((attribute) => [dataAttributeMapping[attribute] ,attributes[attribute]])
    );


    model.change((writer) => {
      model.insertContent(createEntityEmbed(writer, modelAttributes));
    });
  }

  refresh() {
    const model = this.editor.model;
    const selection = model.document.selection;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'drupalEntity',
    );
    this.isEnabled = allowedIn !== null;
  };

}

function createEntityEmbed(writer, attributes) {
  return writer.createElement('drupalEntity', attributes);
}
