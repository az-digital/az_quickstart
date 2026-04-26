// Accessibility runtime patch for Slick carousel (short-term fix for #5514)
// - Adjust roles on dot navigation
// - Add aria-label to track
// - Prevent focusable elements inside aria-hidden slides
(function ($, Drupal) {
  'use strict';
  $(document).once('slick-a11y-patch').each(function () {
    // When slick initializes, apply corrections
    $(document).on('init reInit', '.slick-slider', function (event, slick) {
      var $slider = $(this);
      // ensure track has accessible name
      var $track = $slider.find('.slick-track');
      if ($track.length && !$track.attr('aria-label')) {
        $track.attr('aria-label', $slider.data('a11y-label') || 'Carousel slides');
      }
      // fix dots role
      var $dots = $slider.find('.slick-dots');
      if ($dots.length) {
        $dots.attr('role', 'list');
        $dots.find('li').each(function (i) {
          $(this).removeAttr('role aria-selected aria-controls');
          // ensure internal button has accessible label
          var $btn = $(this).find('button');
          if ($btn.length && !$btn.attr('aria-label')) {
            $btn.attr('aria-label', 'Go to slide ' + (i+1));
          }
        });
      }
      // hide focusable children of aria-hidden slides
      $slider.find('.slick-slide[aria-hidden="true"]').each(function () {
        $(this).find('a, button, input, select, textarea').attr('tabindex', '-1');
      });
      // remove problematic role on slide wrapper
      $slider.find('.slick-slide').removeAttr('role tabindex');
    });
  });
})(jQuery, Drupal);
