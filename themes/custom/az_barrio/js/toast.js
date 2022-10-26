/**
 * @file
 * Toast utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_toast = {
    attach: function (context, settings) {
      $('.toast').toast('show');
    }
  };

})(jQuery, Drupal);
