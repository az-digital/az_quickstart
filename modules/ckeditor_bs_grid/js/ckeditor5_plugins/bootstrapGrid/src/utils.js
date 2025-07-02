import { isWidget } from "ckeditor5/src/widget";

/**
 * Checks if the provided model element is `bsGrid`.
 *
 * @param {module:engine/model/element~Element} modelElement
 *   The model element to be checked.
 * @return {boolean}
 *   A boolean indicating if the element is a bsGrid element.
 *
 * @private
 */
export function isBootstrapGrid(modelElement) {
  return !!modelElement && modelElement.is("element", "bsGrid");
}

/**
 * Checks if the provided model element is `bsGridContainer`.
 *
 * @param {module:engine/model/element~Element} modelElement
 *   The model element to be checked.
 * @return {boolean}
 *   A boolean indicating if the element is a bsGridContainer element.
 *
 * @private
 */
export function isBootstrapGridContainer(modelElement) {
  return !!modelElement && modelElement.is("element", "bsGridContainer");
}

/**
 * Checks if view element is <bsGrid> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <bsGrid> element.
 *
 * @private
 */
export function isBootstrapGridWidget(viewElement) {
  return isWidget(viewElement) && !!viewElement.getCustomProperty("bsGrid");
}

/**
 * Checks if view element is <bsGridContainer> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <bsGridContainer> element.
 *
 * @private
 */
export function isBootstrapGridContainerWidget(viewElement) {
  return (
    isWidget(viewElement) && !!viewElement.getCustomProperty("bsGridContainer")
  );
}

/**
 * Checks if view element is <bsGridRow> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <bsGridRow> element.
 *
 * @private
 */
export function isBootstrapGridRowWidget(viewElement) {
  return isWidget(viewElement) && !!viewElement.getCustomProperty("bsGridRow");
}

/**
 * Checks if view element is <bsGridCol> element.
 *
 * @param {module:engine/view/element~Element} viewElement
 *   The view element.
 * @return {boolean}
 *   A boolean indicating if the element is a <bsGridCol> element.
 *
 * @private
 */
export function isBootstrapGridColWidget(viewElement) {
  return !!viewElement.getCustomProperty("bsGridCol");
}

/**
 * Gets `bsGrid` element from selection.
 *
 * @param {module:engine/model/selection~Selection|module:engine/model/documentselection~DocumentSelection} selection
 *   The current selection.
 * @return {module:engine/model/element~Element|null}
 *   The `bsGrid` element which could be either the current selected an
 *   ancestor of the selection. Returns null if the selection has no Grid
 *   element.
 *
 * @private
 */
export function getClosestSelectedBootstrapGridElement(selection) {
  const selectedElement = selection.getSelectedElement();

  return isBootstrapGrid(selectedElement)
    ? selectedElement
    : selection.getFirstPosition().findAncestor("bsGrid");
}

/**
 * Gets selected BsGrid widget if only BsGrid is currently selected.
 *
 * @param {module:engine/model/selection~Selection} selection
 *   The current selection.
 * @return {module:engine/view/element~Element|null}
 *   The currently selected Grid widget or null.
 *
 * @private
 */
export function getClosestSelectedBootstrapGridWidget(selection) {
  const viewElement = selection.getSelectedElement();
  if (viewElement && isBootstrapGridWidget(viewElement)) {
    return viewElement;
  }

  // Perhaps nothing is selected.
  if (selection.getFirstPosition() === null) {
    return null;
  }

  let { parent } = selection.getFirstPosition();
  while (parent) {
    if (parent.is("element") && isBootstrapGridWidget(parent)) {
      return parent;
    }
    parent = parent.parent;
  }
  return null;
}

/**
 * Extracts classes for settings.
 *
 * @param {*} element
 *   The element being passed.
 * @param {string} base
 *   The base class to exclude.
 * @param {boolean} reverse
 *   Whether to reverse the affect.
 * @return {string|string|string}
 *   The class list.
 */
export function extractGridClasses(element, base, reverse) {
  reverse = reverse || false;
  let classes = "";

  if (typeof element.getAttribute === "function") {
    classes = element.getAttribute("class");
  } else if (typeof element.className === "string") {
    classes = element.className;
  }

  // Failsafe.
  if (!classes) {
    return "";
  }

  const classlist = classes.split(" ").filter((c) => {
    if (
      c.lastIndexOf("ck-widget", 0) === 0 ||
      c.lastIndexOf("ck-edit", 0) === 0 ||
      c.lastIndexOf("bsg-", 0) === 0
    ) {
      return false;
    }
    return reverse
      ? c.lastIndexOf(base, 0) === 0
      : c.lastIndexOf(base, 0) !== 0;
  });

  return classlist.length ? classlist.join(" ").trim() : "";
}

/**
 * Converts a grid into a settings object.
 *
 * @param {module:engine/view/element~Element|null} grid
 *   The current grid.
 * @return {{}}
 *   The settings.
 */
export function convertGridToSettings(grid) {
  const settings = {};
  let row = false;

  settings.container_wrapper_class = extractGridClasses(grid, "bs_grid");

  // First child might be container or row.
  const firstChild = grid.getChild(0);

  // Container.
  if (isBootstrapGridContainerWidget(firstChild)) {
    settings.add_container = 1;
    settings.container_class = extractGridClasses(firstChild, "container");

    // Container can have no classes, so need direct compare.
    const containerType = extractGridClasses(firstChild, "container", true);
    if (containerType.length) {
      if (containerType.indexOf("container-fluid") !== -1) {
        settings.container_type = "fluid";
      } else {
        settings.container_type = "default";
      }
    }
    row = firstChild.getChild(0);
  } else {
    row = firstChild;
  }

  // Row options.
  const rowClasses = extractGridClasses(row, "row");
  settings.no_gutter = rowClasses.indexOf("no-gutters") !== -1 ? 1 : 0;
  settings.row_class = rowClasses.replace("no-gutters", "").replace("g-0", "");

  // Layouts.
  settings.breakpoints = {
    none: { layout: row.getAttribute("data-row-none") },
    sm: { layout: row.getAttribute("data-row-sm") },
    md: { layout: row.getAttribute("data-row-md") },
    lg: { layout: row.getAttribute("data-row-lg") },
    xl: { layout: row.getAttribute("data-row-xl") },
    xxl: { layout: row.getAttribute("data-row-xxl") },
  };

  // Col options.
  settings.num_columns = 0;
  Array.from(row.getChildren()).forEach((col, idx) => {
    if (isBootstrapGridColWidget(col)) {
      settings.num_columns += 1;
      const colClass = extractGridClasses(col, "col");
      const key = `col_${idx + 1}_classes`;
      settings[key] = colClass;
    }
  });

  return settings;
}
