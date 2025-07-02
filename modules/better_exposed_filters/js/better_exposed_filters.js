/**
 * @file
 * better_exposed_filters.js
 *
 * Provides some client-side functionality for the Better Exposed Filters module.
 */

(function ($, Drupal, once) {
  Drupal.behaviors.betterExposedFilters = {
    attach: function (context) {
      // Add highlight class to checked checkboxes for better theming.
      $('.bef-tree input[type=checkbox], .bef-checkboxes input[type=checkbox]')
        // Highlight newly selected checkboxes.
        .change(function () {
          _bef_highlight(this, context);
        })
        .filter(':checked').closest('.form-item', context).addClass('highlight');
    }
  };

  /*
   * Helper functions
   */

  /**
   * Adds/Removes the highlight class from the form-item div as appropriate.
   */
  function _bef_highlight(elem, context) {
    $elem = $(elem, context);
    $elem.attr('checked')
      ? $elem.closest('.form-item', context).addClass('highlight')
      : $elem.closest('.form-item', context).removeClass('highlight');
  }

  /**
   * Adds the data-bef-auto-submit-exclude to elements with type="text".
   */
  Drupal.behaviors.autosubmitExcludeTextfield = {
    attach: function (context, settings) {
      if (!settings.better_exposed_filters?.autosubmit_exclude_textfield) {
        return;
      }
      $(once('autosubmit-exclude-textfield', '.bef-exposed-form', context)).each(function () {
        $(this).find('*[type="text"]').attr('data-bef-auto-submit-exclude', '');
      });
    }
  };

})(jQuery, Drupal, once);
