/**
 * @file
 * Add support for Better Exposed Filters integration.
 */
(function ($, Drupal) {
  'use strict';

  function applyChosenBef($select) {
    $select.next('.chosen-container').find('.chosen-search-input').attr('data-bef-auto-submit-exclude', true);
  }

  Drupal.behaviors.chosenBef = {
    attach: function (context, settings) {
      $(once('chosenBef', 'select')).each(function () {
        const $select = $(this);
        if ($select.next('.chosen-container').length) {
          applyChosenBef($select);
        } else {
          $select.on('chosen:ready', function () {
            applyChosenBef($select);
          });
        }
      });
    }
  };
})(jQuery, Drupal);
