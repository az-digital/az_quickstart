/**
 * @file
 * az_exposed_filters_datepickers.js
 *
 * Provides jQueryUI Datepicker integration with AZ Exposed Filters.
 */

(function ($, Drupal, drupalSettings) {
  /*
   * Helper functions
   */

  Drupal.behaviors.azExposedFiltersDatePickers = {
    attach: function (context, settings) {

      // Check for and initialize datepickers.
      var azExposedFiltersSettings = drupalSettings.az_exposed_filters;
      if (azExposedFiltersSettings && azExposedFiltersSettings.datepicker && azExposedFiltersSettings.datepicker_options && $.fn.datepicker) {
        var opt = [];
        $.each(azExposedFiltersSettings.datepicker_options, function (key, val) {
          if (key && val) {
            opt[key] = JSON.parse(val);
          }
        });
        $('.az-exposed-filters-datepicker').datepicker(opt);
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
