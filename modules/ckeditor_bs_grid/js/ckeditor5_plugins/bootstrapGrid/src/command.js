import { Command } from "ckeditor5/src/core";
import {
  getClosestSelectedBootstrapGridElement,
  isBootstrapGridContainer,
} from "./utils";

/**
 * Creates a new BS Grid
 *
 * @param {module:engine/model/writer~Writer} writer
 *   The model writer.
 * @param {{}} settings
 *   The settings
 * @return {*}
 *   The grid.
 */
function createBsGrid(writer, settings) {
  // Grid.
  const gridAttributes = { class: "bs_grid" };
  if (settings.container_wrapper_class !== undefined) {
    gridAttributes.class += ` ${settings.container_wrapper_class}`;
  }

  // Create the grid.
  const bsGrid = writer.createElement("bsGrid", gridAttributes);

  // Row.
  const rowAttributes = {
    class: settings.row_class.trim(),
    "data-row-none": settings.breakpoints.none
      ? settings.breakpoints.none.layout
      : "",
    "data-row-sm": settings.breakpoints.sm
      ? settings.breakpoints.sm.layout
      : "",
    "data-row-md": settings.breakpoints.md
      ? settings.breakpoints.md.layout
      : "",
    "data-row-lg": settings.breakpoints.lg
      ? settings.breakpoints.lg.layout
      : "",
    "data-row-xl": settings.breakpoints.xl
      ? settings.breakpoints.xl.layout
      : "",
    "data-row-xxl": settings.breakpoints.xxl
      ? settings.breakpoints.xxl.layout
      : "",
  };
  const bsGridRow = writer.createElement("bsGridRow", rowAttributes);

  // Cols.
  for (let i = 1; i <= settings.num_columns; i++) {
    const key = `col_${i}_classes`;
    const bsGridCol = writer.createElement("bsGridCol", {
      class: settings[key],
    });
    const colcontent = writer.createElement("paragraph");
    writer.insertText(`Column ${i} content`, colcontent);
    writer.append(colcontent, bsGridCol);
    writer.append(bsGridCol, bsGridRow);
  }

  // Container.
  if (settings.add_container) {
    const containerAttributes = { class: settings.container_class.trim() };
    const bsGridContainer = writer.createElement(
      "bsGridContainer",
      containerAttributes
    );
    writer.append(bsGridContainer, bsGrid);
    writer.append(bsGridRow, bsGridContainer);
  } else {
    writer.append(bsGridRow, bsGrid);
  }

  return bsGrid;
}

/**
 * Updates an existing BS Grid
 *
 * @param {module:engine/model/writer~Writer} writer
 *   The model writer.
 * @param {module:engine/view/element~Element|null} existingGrid
 * @param {{}} settings
 *   The settings
 */
function updateExisting(writer, existingGrid, settings) {
  let existingContainer = false;
  let row = false;

  // Grid.
  const gridAttributes = { class: "bs_grid" };
  if (settings.container_wrapper_class !== undefined) {
    gridAttributes.class += ` ${settings.container_wrapper_class}`;
  }
  gridAttributes.class = gridAttributes.class.trim();
  writer.setAttributes(gridAttributes, existingGrid);

  // First child might be container or row.
  const firstChild = existingGrid.getChild(0);

  // Container.
  if (isBootstrapGridContainer(firstChild)) {
    existingContainer = firstChild;
    row = firstChild.getChild(0);
  } else {
    row = firstChild;
  }

  // Row update.
  const rowAttributes = {
    class: settings.row_class.trim(),
    "data-row-none": settings.breakpoints.none
      ? settings.breakpoints.none.layout
      : "",
    "data-row-sm": settings.breakpoints.sm
      ? settings.breakpoints.sm.layout
      : "",
    "data-row-md": settings.breakpoints.md
      ? settings.breakpoints.md.layout
      : "",
    "data-row-lg": settings.breakpoints.lg
      ? settings.breakpoints.lg.layout
      : "",
    "data-row-xl": settings.breakpoints.xl
      ? settings.breakpoints.xl.layout
      : "",
    "data-row-xxl": settings.breakpoints.xxl
      ? settings.breakpoints.xxl.layout
      : "",
  };
  writer.setAttributes(rowAttributes, row);

  // Cols.
  for (let i = 1; i <= settings.num_columns; i++) {
    const key = `col_${i}_classes`;
    writer.setAttributes({ class: settings[key] }, row.getChild(i - 1));
  }

  // Container settings.
  if (settings.add_container) {
    const containerAttributes = { class: settings.container_class.trim() };

    // Needs container, none exists.
    if (!existingContainer) {
      existingContainer = writer.createElement(
        "bsGridContainer",
        containerAttributes
      );

      writer.append(existingContainer, existingGrid);
      writer.append(row, existingContainer);
    } else {
      // Already has one, just add the classes.
      writer.setAttributes(containerAttributes, existingContainer);
    }
  }
  // No container, but already has one.
  else if (existingContainer) {
    writer.unwrap(existingContainer);
  }
}

/**
 * Inserts a grid or updates a new one.
 */
export default class InsertBootstrapGridCommand extends Command {
  execute(settings) {
    const { model } = this.editor;
    const existingGrid = getClosestSelectedBootstrapGridElement(
      model.document.selection
    );

    model.change((writer) => {
      if (existingGrid) {
        updateExisting(writer, existingGrid, settings);
      } else {
        model.insertContent(createBsGrid(writer, settings));
      }
    });
  }

  refresh() {
    const { model } = this.editor;
    const { selection } = model.document;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      "bsGrid"
    );
    this.isEnabled = allowedIn !== null;
  }
}
