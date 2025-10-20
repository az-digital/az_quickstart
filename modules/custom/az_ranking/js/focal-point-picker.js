/**
 * @file
 * Focal Point Picker for az_image media type.
 *
 * Adds a clickable overlay to the image preview that allows users to set
 * the focal point by clicking on the image.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.azRankingFocalPointPicker = {
    attach: function (context, settings) {
      // Find the main image field.
      once('focal-point-picker', '.field--name-field-media-az-image .image-widget', context).forEach(function (imageWidget) {
        const $widget = $(imageWidget);
        const $preview = $widget.find('.image-preview img');
        
        if ($preview.length === 0) {
          return;
        }

        // Create focal point indicator and overlay.
        const $overlay = $('<div class="focal-point-overlay"></div>');
        const $indicator = $('<div class="focal-point-indicator" title="Click to set focal point"></div>');
        
        $overlay.append($indicator);
        $preview.parent().css('position', 'relative').append($overlay);

        // Get focal point field values.
        const $focalX = $('input[name="field_focal_point_x[0][value]"]');
        const $focalY = $('input[name="field_focal_point_y[0][value]"]');

        // Set initial position from field values or default to center.
        let focalX = parseFloat($focalX.val()) || 0.5;
        let focalY = parseFloat($focalY.val()) || 0.5;

        function updateIndicatorPosition() {
          const width = $preview.width();
          const height = $preview.height();
          
          $indicator.css({
            left: (focalX * width) + 'px',
            top: (focalY * height) + 'px'
          });
        }

        // Wait for image to load before positioning.
        if ($preview[0].complete) {
          updateIndicatorPosition();
        } else {
          $preview.on('load', updateIndicatorPosition);
        }

        // Handle clicks on the overlay.
        $overlay.on('click', function (e) {
          const offset = $preview.offset();
          const width = $preview.width();
          const height = $preview.height();
          
          // Calculate relative position (0.0 to 1.0).
          focalX = (e.pageX - offset.left) / width;
          focalY = (e.pageY - offset.top) / height;

          // Clamp values between 0 and 1.
          focalX = Math.max(0, Math.min(1, focalX));
          focalY = Math.max(0, Math.min(1, focalY));

          // Update hidden fields.
          $focalX.val(focalX.toFixed(2)).trigger('change');
          $focalY.val(focalY.toFixed(2)).trigger('change');

          // Update indicator position.
          updateIndicatorPosition();

          // Show feedback.
          $indicator.addClass('focal-point-indicator--active');
          setTimeout(function () {
            $indicator.removeClass('focal-point-indicator--active');
          }, 300);
        });

        // Update position on window resize.
        $(window).on('resize', updateIndicatorPosition);
      });
    }
  };

})(jQuery, Drupal, once);
