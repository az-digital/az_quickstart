/**
 * @file
 * Select-All Button functionality.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.views_bulk_operations = {
    attach: function (context, settings) {
      once('vbo-init', '.vbo-view-form', context).forEach(Drupal.viewsBulkOperationsFrontUi);
    }
  };

  /**
   * VBO selection handling class.
   */
  let viewsBulkOperationsSelection = class {

    constructor(vbo_form) {
      this.vbo_form = vbo_form;
      this.$actionSelect = $('select[name="action"]', vbo_form);
      this.view_id = '';
      this.display_id = '';
      this.$summary = null;
      this.totalCount = 0;
      this.ajaxing = false;
    }

    /**
     * Bind event handlers to an element.
     *
     * @param {jQuery} $element
     * @param {string} element_type
     * @param {int} index
     */
    bindEventHandlers($element, element_type, index = 0) {
      if ($element.length) {
        var selectionObject = this;
        $element.on('keypress', function (event) {
          // Emulate click action for enter key.
          if (event.which === 13) {
            event.preventDefault();
            event.stopPropagation();
            selectionObject.update(this, element_type, index);
            $(this).trigger('click');
          }
          if (event.which === 32) {
            selectionObject.update(this, element_type, index);
          }
        });
        $element.on('click', function (event) {
          // Act only on left button click.
          if (event.which === 1) {
            selectionObject.update(this, element_type, index);
          }
        });
      }
    }

    bindActionSelect() {
      if (this.$actionSelect.length) {
        var selectionObject = this;
        this.$actionSelect.on('change', function (event) {
          selectionObject.toggleButtonsState();
        });
      }
    }

    bindCheckboxes() {
      var selectionObject = this;
      var checkboxes = $('.js-vbo-checkbox', this.vbo_form);
      checkboxes.on('change', function (event) {
        selectionObject.toggleButtonsState();
      });
    }

    toggleButtonsState() {
      // If no rows are checked, disable any form submit actions.
      var buttons = $('input[data-vbo="vbo-action"], button[data-vbo="vbo-action"]', this.vbo_form);
      var anyItemsSelected;

      if (this.view_id.length && this.display_id.length) {
        anyItemsSelected = this.totalCount;
      }
      else {
        anyItemsSelected = $('.js-vbo-checkbox:checked', this.vbo_form).length;
      }

      if (this.$actionSelect.length) {
        let has_selection = anyItemsSelected && this.$actionSelect.val() !== '';
        buttons.prop('disabled', !has_selection);
      }
      else {
        buttons.prop('disabled', !anyItemsSelected);
      }
    }

    /**
     * Perform an AJAX request to update selection.
     *
     * @param {object} element
     *   The checkbox element.
     * @param {string} element_type
     *   Which type of a checkbox is it?
     * @param {int} index
     *   Index of the checkbox, used for table select all.
     */
    update(element, element_type, index) {
      if (!this.view_id.length || !this.display_id.length) {
        this.toggleButtonsState();
        return;
      }
      if (this.ajaxing) {
        return;
      }

      var list = {};
      var selectionObject = this;
      var op = 'update';
      if (element_type === 'selection_method_change') {
        op = element.checked ? 'method_exclude' : 'method_include';
      }
      else {
        // Build standard list.
        $('.js-vbo-checkbox', this.vbo_form).each(function () {
          let dom_value = $(this).val();
          // All bulk form keys are quite long, it'd be safe to assume
          // anything above 10 characters to filter out other values.
          if (dom_value.length < 10) {
            return;
          }
          list[dom_value] = this.checked;
        });

        // If a table select all was used, update the list according to that.
        if (element_type === 'table_select_all') {
          this.list[index].forEach(function (bulk_form_key) {
            list[bulk_form_key] = element.checked;
          });
        }
      }

      var $summary = this.$summary;
      var $selectionInfo = this.$selectionInfo;
      var target_uri = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix + 'views-bulk-operations/ajax/' + this.view_id + '/' + this.display_id;

      var ajax_options = {
        url: target_uri,
        progress: false,
        submit: {
          list: list,
          op: op
        },
        success: function (data) {
          selectionObject.totalCount = data.count;
          $selectionInfo.html(data.selection_info);
          $summary.text(Drupal.formatPlural(data.count, 'Selected 1 item', 'Selected @count items'));
          selectionObject.toggleButtonsState();
          selectionObject.ajaxing = false;
        }
      };

      if (
        Object.prototype.hasOwnProperty.call(drupalSettings, 'vbo') &&
        Object.prototype.hasOwnProperty.call(drupalSettings.vbo, 'ajax_loader') &&
        drupalSettings.vbo.ajax_loader
      ) {
        ajax_options.progress = {type: 'fullscreen'};
      }

      var ajaxDrupal = Drupal.ajax(ajax_options);
      this.ajaxing = true;
      ajaxDrupal.execute();
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.views_bulk_operations}.
   *
   * @param {object} element
   */
  Drupal.viewsBulkOperationsFrontUi = function (element) {
    var $vboForm = $(element);
    var $viewsTables = $('.vbo-table', $vboForm);
    var $primarySelectAll = $('.vbo-select-all', $vboForm);
    var tableSelectAll = [];
    let vboSelection = new viewsBulkOperationsSelection($vboForm);

    // When grouping is enabled, there can be multiple tables.
    if ($viewsTables.length) {
      $viewsTables.each(function (index) {
        tableSelectAll[index] = $(this).find('.select-all input').first();
      });
    }

    // Add AJAX functionality to row selector checkboxes.
    var $multiSelectElement = $vboForm.find('.vbo-multipage-selector').first();
    if ($multiSelectElement.length) {

      vboSelection.$selectionInfo = $multiSelectElement.find('.vbo-info-list-wrapper').first();
      vboSelection.$summary = $multiSelectElement.find('summary').first();
      vboSelection.view_id = $multiSelectElement.attr('data-view-id');
      vboSelection.display_id = $multiSelectElement.attr('data-display-id');
      vboSelection.totalCount = drupalSettings.vbo_selected_count[vboSelection.view_id][vboSelection.display_id];

      // Get the list of all checkbox values and add AJAX callback.
      vboSelection.list = [];

      var $contentWrappers;
      if ($viewsTables.length) {
        $contentWrappers = $viewsTables;
      }
      else {
        $contentWrappers = $([$vboForm]);
      }

      $contentWrappers.each(function (index) {
        vboSelection.list[index] = [];

        $(this).find('input[type="checkbox"]').each(function () {
          let value = $(this).val();
          if (!$(this).hasClass('vbo-select-all') && value !== 'on') {
            vboSelection.list[index].push(value);
            vboSelection.bindEventHandlers($(this), 'vbo_checkbox');
          }
        });

        // Bind event handlers to select all checkbox.
        if ($viewsTables.length && tableSelectAll.length) {
          vboSelection.bindEventHandlers(tableSelectAll[index], 'table_select_all', index);
        }
      });
    }
    // If we don't have multiselect and AJAX calls, we need to toggle button
    // state on click instead of on AJAX success.
    else {
      vboSelection.bindCheckboxes();
    }

    // Initialize all selector if the primary select all and
    // view table elements exist.
    if ($primarySelectAll.length) {
      $primarySelectAll.on('change', function (event) {
        var value = this.checked;

        // Select / deselect all checkboxes in the view.
        // If there are table select all elements, use that.
        if (tableSelectAll.length) {
          tableSelectAll.forEach(function (element) {
            if (element.get(0).checked !== value) {
              element.click();
            }
          });
        }

        // Also handle checkboxes that may still have different values.
        $vboForm.find('.views-field-views-bulk-operations-bulk-form input[type="checkbox"]').each(function () {
          if (this.checked !== value) {
            $(this).click();
          }
        });

        // Clear the selection information if exists.
        $vboForm.find('.vbo-info-list-wrapper').each(function () {
          $(this).html('');
        });
      });

      if ($multiSelectElement.length) {
        vboSelection.bindEventHandlers($primarySelectAll, 'selection_method_change');
      }
    }
    vboSelection.bindActionSelect();
    vboSelection.toggleButtonsState();
  };

})(jQuery, Drupal, drupalSettings);
