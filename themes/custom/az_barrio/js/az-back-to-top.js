/**
 * @file
 * A JavaScript file for the back to top functionality.
 *
 */

(function ($, Drupal, window, document) {
  'use strict';
  Drupal.behaviors.uaZenBackToTop = {
    attach: function (context, settings) {
      // only run this script if the document height is 4 times the height
      // of the browser window the page is being viewed through.
      if (($(document).height() / $(window).height()) >= 4) {
        var $backToTop = $('#az-back-to-top');
        var $window = $(window);

        // Smoothly scroll to the top of the page if the arrow is clicked.
        $backToTop.on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: 0
            }, 20); // Changed from 500, which prevents bouncing
        });

        // Hide the arrow if we're at the top of the page.
        $window.scroll(function () {
          if ($window.scrollTop() > 750) {
            $backToTop.show();
          } else {
            $backToTop.hide();
          }
        })
      }
    }
  };
})(jQuery, Drupal, window, document);