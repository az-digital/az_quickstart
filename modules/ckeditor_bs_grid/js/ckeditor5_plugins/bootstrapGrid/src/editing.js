import { Plugin } from "ckeditor5/src/core";
import { Widget, toWidget, toWidgetEditable } from "ckeditor5/src/widget";
import InsertBootstrapGridCommand from "./command";

/**
 * Defines the editing commands for BS Grid.
 */
export default class BootstrapGridEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return "BootstrapGridEditing";
  }

  constructor(editor) {
    super(editor);
    this.attrs = {
      class: "class",
      "data-row-none": "data-row-none",
      "data-row-sm": "data-row-sm",
      "data-row-md": "data-row-md",
      "data-row-lg": "data-row-lg",
      "data-row-xl": "data-row-xl",
      "data-row-xxl": "data-row-xxl",
    };
  }

  init() {
    const options = this.editor.config.get("bootstrapGrid");
    if (!options) {
      return;
    }

    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
  }

  /*
   * This registers the structure that will be seen by CKEditor 5 as
   * <bsGrid>
   *    <bsGridContainer>
   *      <bsGridRow>
   *        <bsGridCol .. />
   *      </bsGridRow>
   *    </gridContainer>
   * </bootstrapGrid>
   *
   * The logic in _defineConverters() will determine how this is converted to
   * markup.
   */
  _defineSchema() {
    const { schema } = this.editor.model;
    schema.register("bsGrid", {
      allowWhere: "$block",
      isLimit: true,
      isObject: true,
      allowAttributes: ["class"],
    });
    schema.register("bsGridContainer", {
      isLimit: true,
      allowIn: "bsGrid",
      isInline: true,
      allowAttributes: ["class"],
    });
    schema.register("bsGridRow", {
      isLimit: true,
      allowIn: ["bsGrid", "bsGridContainer"],
      isInline: true,
      allowAttributes: Object.keys(this.attrs),
    });
    schema.register("bsGridCol", {
      allowIn: "bsGridRow",
      isInline: true,
      allowContentOf: "$root",
      allowAttributes: ["class"],
    });
  }

  /**
   * Converters determine how CKEditor 5 models are converted into markup and
   * vice-versa.
   */
  _defineConverters() {
    const { conversion } = this.editor;

    // <bsGrid>
    conversion.for("upcast").elementToElement({
      model: "bsGrid",
      view: {
        name: "div",
        classes: "bs_grid",
      },
    });

    conversion.for("downcast").elementToElement({
      model: "bsGrid",
      view: (modelElement, { writer }) => {
        const container = writer.createContainerElement("div", {
          class: "bs_grid",
        });
        writer.setCustomProperty("bsGrid", true, container);
        return toWidget(container, writer, { label: "BS Grid" });
      },
    });

    // <bsGridContainer>
    conversion.for("upcast").elementToElement({
      model: "bsGridContainer",
      view: {
        name: "div",
      },
    });

    conversion.for("downcast").elementToElement({
      model: "bsGridContainer",
      view: (modelElement, { writer }) => {
        const container = writer.createContainerElement("div");
        writer.setCustomProperty("bsGridContainer", true, container);
        return toWidget(container, writer, { label: "BS Grid Container" });
      },
    });

    // <bsGridRow>
    conversion.for("upcast").elementToElement({
      view: {
        name: "div",
        classes: ["row"],
      },
      converterPriority: "high",
      model: (viewElement, {writer}) => {
        return writer.createElement("bsGridRow", {
          class: viewElement.getAttribute('class') || "row",
        });
      },
    });

    conversion.for("downcast").elementToElement({
      model: "bsGridRow",
      view: (modelElement, { writer }) => {
        const rowAttributes = {
          "class": modelElement.getAttribute('class') || "row",
          "data-row-none": modelElement.getAttribute("data-row-none"),
          "data-row-sm": modelElement.getAttribute("data-row-sm"),
          "data-row-md": modelElement.getAttribute("data-row-md"),
          "data-row-lg": modelElement.getAttribute("data-row-lg"),
          "data-row-xl": modelElement.getAttribute("data-row-xl"),
          "data-row-xxl": modelElement.getAttribute("data-row-xxl"),
        };
        const container = writer.createContainerElement("div", rowAttributes);
        writer.setCustomProperty("bsGridRow", true, container);
        return toWidget(container, writer, { label: "BS Grid Row" });
      },
    });

    // <bsGridCol>
    conversion.for("upcast").elementToElement({
      model: "bsGridCol",
      view: {
        name: "div",
      },
    });

    conversion.for("editingDowncast").elementToElement({
      model: "bsGridCol",
      view: (modelElement, { writer }) => {
        const element = writer.createEditableElement("div");
        writer.setCustomProperty("bsGridCol", true, element);
        return toWidgetEditable(element, writer);
      },
    });

    conversion.for("dataDowncast").elementToElement({
      model: "bsGridCol",
      view: {
        name: "div",
        classes: "",
      },
    });

    // Set attributeToAttribute conversion for all supported attributes.
    Object.keys(this.attrs).forEach((modelKey) => {
      const attributeMapping = {
        model: {
          key: modelKey,
          name: "bsGridRow",
        },
        view: {
          name: "div",
          key: this.attrs[modelKey],
        },
      };
      conversion.for("downcast").attributeToAttribute(attributeMapping);
      conversion.for("upcast").attributeToAttribute(attributeMapping);
    });

    conversion.attributeToAttribute({ model: "class", view: "class" });
  }

  /**
   * Defines the BS Grid commands.
   *
   * @private
   */
  _defineCommands() {
    this.editor.commands.add(
      "insertBootstrapGrid",
      new InsertBootstrapGridCommand(this.editor)
    );
  }
}
