/**
 * @file
 * Config inspector behaviors.
 */

 (function ($, Drupal, debounce, once) {

  'use strict';

  /**
   * Filters configuration entities with schema errors.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.configInspectorSchemaErrors = {
    attach: function (context, settings) {

      const $rows = $('.config-inspector-list').find('tbody tr');

      // Run filters on page load if state is saved by browser.
      $(once('listIsLoaded', '.config-inspector-list', context)).each(function () {
        filterByText();
      });

      // Toggle table rows with schema errors.
      $(once('schemaHasErrorsLoaded', '#schema-has-errors', context)).change(function () {
        filterByText();
      });

      // Filter table rows with text input.
      $(once('schemaFilterText', '#schema-filter-text', context)).keyup(function () {
        debounce(filterByText, 200)();
      });

      function filterByText() {
        const query = $('#schema-filter-text').val();
        // Case insensitive expression to find query.
        const re = new RegExp(`${query}`, 'i');
        const filter_schema_errors = $('#schema-has-errors').is(':checked');

        function showModuleRow(index, row) {
          const $row = $(row);
          const $sources = $row.find('.table-filter-text-source');
          // If text query exists in row (or is empty) we have a match.
          const textMatch = $sources.text().search(re) !== -1;
          // Don't match if 'show errors' is checked and the row is not an error. Otherwise match.
          const errorMatch = (!(filter_schema_errors && $row.find('[data-has-errors]').length === 0));
          // Show the row if it matches the text filter and the error filter.
          $row.closest('tr').toggle(textMatch && errorMatch);
        }

        $rows.each(showModuleRow);
      }
    }
  };

}(jQuery, Drupal, Drupal.debounce, once));
