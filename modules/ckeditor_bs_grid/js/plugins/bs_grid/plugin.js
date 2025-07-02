/**
 * @file
 * Bootstrap Grid plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  function findGridWrapper(element) {
    return element.getAscendant(function (el) {
      if (typeof el.hasClass === 'function') {
        return el.hasClass('bs_grid');
      }
      return false;
    }, true);
  }

  function getSelectedGrid(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getStartElement();

    if (selectedElement && selectedElement.hasClass('bs_grid')) {
      return selectedElement;
    }

    return findGridWrapper(selectedElement);
  }

  function extractClasses(element, base, reverse) {
    reverse = reverse || false;
    var classes = '';

    if (typeof element.getAttribute === 'function') {
      classes = element.getAttribute('class');
    }
    else if (typeof element.className === 'string') {
      classes = element.className;
    }

    // Failsafe.
    if (!classes) {
      return '';
    }

    var classlist = classes.split(" ").filter(function (c) {
      if (c.lastIndexOf('cke_', 0) === 0) { return false; }
      return reverse ? c.lastIndexOf(base, 0) === 0 : c.lastIndexOf(base, 0) !== 0;
    });

    return classlist.length ? classlist.join(" ").trim() : '';
  }

  CKEDITOR.plugins.add('bs_grid', {
    requires: 'widget',
    icons: 'bs_grid',
    init: function (editor) {

      // Allow widget editing.
      editor.widgets.add('bs_grid_widget', {
        template:
            '<div class="bs_grid"></div>',
        allowedContent: '',
        requiredContent: 'div(bs_grid)',
        upcast: function (element) {
          return element.name === 'div' && element.hasClass('bs_grid');
        },
        init: function () {
          var row = this.element.findOne('.row');
          if (row) {
            var cols = row.find('> div');
            for(var i = 1; i <= cols.count(); i++) {
              this.initEditable('col-' + i, {
                selector: '.row > div:nth-child(' + i + ')',
                allowedContent: '',
              })
            }
          }
        },

      });

      // Add the dialog command.
      editor.addCommand('bs_grid', {
        allowedContent: 'div[class, data-*]',
        requiredContent: 'div[class, data-*]',
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var existingValues = {};
          var existingElement = getSelectedGrid(editor);

          // Existing elements need to pull the settings.
          if (existingElement) {
            existingValues.saved = 1;
            var existing_row;

            // Parse out the data we need.
            existingValues.container_wrapper_class = extractClasses(existingElement, 'bs_grid');
            var first_element = existingElement.findOne('> div');

            // We have a container if no row (container can have no class).
            if (!first_element.hasClass('row')) {
              existingValues.add_container = 1;
              existingValues.container_class = extractClasses(first_element, 'container');

              // Container can have no classes, so need direct compare.
              var container_type = extractClasses(first_element, 'container', true);
              if (container_type.length) {
                if (container_type.indexOf('container-fluid') !== -1) {
                  existingValues.container_type = 'fluid';
                }
                else {
                  existingValues.container_type = 'default';
                }
              }

              // Get row info.
              existing_row = first_element.findOne('.row');
            }
            else {
              existing_row = first_element;
            }

            var row_classes = extractClasses(existing_row, 'row');
            existingValues.no_gutter = row_classes.indexOf('no-gutters') !== -1 ? 1 : 0;
            existingValues.row_class = row_classes.replace('no-gutters', '');

            // Cols.
            var existing_cols = existing_row.find('> div');
            existingValues.num_columns = existing_cols.count();

            // Layouts.
            existingValues.breakpoints = {
              none: {layout: existing_row.getAttribute('data-row-none')},
              sm: {layout: existing_row.getAttribute('data-row-sm')},
              md: {layout: existing_row.getAttribute('data-row-md')},
              lg: {layout: existing_row.getAttribute('data-row-lg')},
              xl: {layout: existing_row.getAttribute('data-row-xl')},
              xxl: {layout: existing_row.getAttribute('data-row-xxl')}
            };

            for (var i = 1; i <= existingValues.num_columns; i++) {
              var col = existing_cols.getItem(i - 1);
              var col_class = extractClasses(col, 'col');
              var key = 'col_' + i + '_classes';
              existingValues[key] = col_class;
            }

          }

          // Fired when saving the dialog.
          var saveCallback = function (returnVals) {
            var values = returnVals.settings;
            editor.fire('saveSnapshot');

            // Always output a wrapper.
            var wrapper_class = 'bs_grid';
            if (values.container_wrapper_class !== undefined) {
              wrapper_class += ' ' + values.container_wrapper_class;
            }
            if (existingElement) {
              existingElement.setAttribute('class', wrapper_class);
            }
            else {
              var bs_wrapper = editor.document.createElement('div', {attributes: {class: wrapper_class}});
            }

            // Add the row.
            var row_attributes = {
              class: values.row_class,
              'data-row-none': values.breakpoints.none ? values.breakpoints.none.layout : '',
              'data-row-sm': values.breakpoints.sm ? values.breakpoints.sm.layout : '',
              'data-row-md': values.breakpoints.md ? values.breakpoints.md.layout : '',
              'data-row-lg': values.breakpoints.lg? values.breakpoints.lg.layout : '',
              'data-row-xl': values.breakpoints.xl ? values.breakpoints.xl.layout : '',
              'data-row-xxl': values.breakpoints.xxl ? values.breakpoints.xxl.layout : ''
            };
            if (existingElement) {
              existing_row.setAttributes(row_attributes);
            }
            else {
              var row = editor.document.createElement('div', {attributes: row_attributes});
            }

            // Iterated through the cols.
            for (var i = 1; i <= values.num_columns; i++) {
              var key = 'col_' + i + '_classes';
              if (existingElement) {
                existing_cols.getItem(i -1).setAttribute('class', values[key]);
              }
              else {
                var col = editor.document.createElement('div', {attributes: {class: values[key]}});
                col.setHtml('Column ' + i + ' content');
                row.append(col);
              }
            }

            // Append to Wrapper. @TODO: Support for dropping existing container.
            if (!existingElement) {
              if (values.add_container) {
                var container = editor.document.createElement('div', {attributes: {class: values.container_class}});
                container.append(row);
                bs_wrapper.append(container);
              }
              else {
                bs_wrapper.append(row);
              }
              editor.insertHtml(bs_wrapper.getOuterHtml());
            }

            // Final save.
            editor.fire('saveSnapshot');
          };


          var dialogSettings = {
            dialogClass: 'bs_grid-dialog',
          };

          // Open the entity embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, Drupal.url('ckeditor_bs_grid/dialog/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      // UI Button
      editor.ui.addButton('bs_grid', {
        label: 'Insert Bootstrap Grid',
        command: 'bs_grid',
        icon: this.path + 'icons/bs_grid.png'
      });

      // Context menu to edit existing.
      if (editor.contextMenu) {
        editor.addMenuGroup('bsGridGroup');
        editor.addMenuItem('bsGridItem', {
          label: 'Edit Grid',
          icon: this.path + 'icons/bs_grid.png',
          command: 'bs_grid',
          group: 'bsGridGroup'
        });

        // Load nearest grid.
        editor.contextMenu.addListener(function (element) {
          if (findGridWrapper(element)) {
            return {bsGridItem: CKEDITOR.TRISTATE_OFF};
          }
        });
      }

    }
  });

})(jQuery, Drupal, CKEDITOR);
