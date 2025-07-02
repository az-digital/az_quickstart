(function ($) {

  'use strict';

  /**
   * Attaches the behavior to bootstrap carousel view.
   */
  Drupal.behaviors.views_bootstrap_carousel = {
    attach: function (context, settings) {
      $('.carousel-inner').each(function() {
        if ($(this).children('div').length === 1) {
          $(this).siblings('.carousel-control, .carousel-indicators').hide();
        }
      });
    }
  }



}(jQuery));