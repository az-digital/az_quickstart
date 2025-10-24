/**
 * @file
 * Focal Point Picker for az_image media type.
 *
 * Adds a clickable overlay to the focal point picker section in the media edit form.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.azRankingFocalPointPicker = {
    attach: function (context, settings) {      
      // Find the focal point picker wrapper in the media form
      const $pickerWrappers = $('.focal-point-picker-wrapper', context);
      
      $pickerWrappers.each(function(index) {
        const $wrapper = $(this);
        
        // Check if already processed
        if ($wrapper.data('focal-point-processed')) {
          return;
        }
        $wrapper.data('focal-point-processed', true);
        
        // Find the image
        const $image = $wrapper.find('.focal-point-picker-image');
        
        if ($image.length === 0) {
          console.warn('No image found in focal point picker wrapper');
          return;
        }
        
        // Get current focal point values from data attributes
        let focalX = parseFloat($wrapper.attr('data-focal-x')) || 0.5;
        let focalY = parseFloat($wrapper.attr('data-focal-y')) || 0.5;
        
        console.log('=== Focal Point Picker Initialized ===');
        console.log('Wrapper data-focal-x:', $wrapper.attr('data-focal-x'));
        console.log('Wrapper data-focal-y:', $wrapper.attr('data-focal-y'));
        console.log('Parsed focalX:', focalX);
        console.log('Parsed focalY:', focalY);
        
        // Wrap the image in a positioned container to constrain the overlay
        const $imageContainer = $('<div class="focal-point-image-container"></div>');
        $imageContainer.css({
          'position': 'relative',
          'display': 'inline-block',
          'max-width': '100%'
        });
        
        // Wrap the image
        $image.wrap($imageContainer);
        const $container = $image.parent();
        
        // Create overlay and indicator
        const $overlay = $('<div class="focal-point-overlay"></div>');
        const $indicator = $('<div class="focal-point-indicator" title="Click to set focal point"></div>');
        
        $overlay.append($indicator);
        $container.append($overlay);
        
        // Get hidden field inputs for storing focal point values
        // Use class selectors for more reliable targeting
        const $focalXInput = $('.js-focal-point-x-value', context);
        const $focalYInput = $('.js-focal-point-y-value', context);
        
        console.log('Hidden field inputs found:', $focalXInput.length, $focalYInput.length);
        console.log('Hidden field X value:', $focalXInput.val());
        console.log('Hidden field Y value:', $focalYInput.val());
        
        if ($focalXInput.length === 0 || $focalYInput.length === 0) {
          console.warn('Could not find focal point input fields! Focal point changes will not be saved.');
        }
        
        function updateIndicatorPosition() {
          const width = $image.width();
          const height = $image.height();
          
          // If dimensions are 0 or suspiciously small, the image isn't ready yet
          // Skip the update and it will be retried by other strategies
          if (width < 10 || height < 10) {
            console.log('Image dimensions too small (not rendered yet). Width:', width, 'Height:', height, '- skipping update');
            return;
          }
          
          const indicatorLeft = (focalX * width);
          const indicatorTop = (focalY * height);
          
          console.log('=== Updating Indicator Position ===');
          console.log('Image width:', width, 'height:', height);
          console.log('Using focalX:', focalX, 'focalY:', focalY);
          console.log('Calculated position - left:', indicatorLeft, 'top:', indicatorTop);
          
          $indicator.css({
            left: indicatorLeft + 'px',
            top: indicatorTop + 'px'
          });
        }
        
        // Set initial position
        // Use multiple strategies to ensure we set the red focus point picker
        // only when the image is fully rendered
        
        // Strategy 1: Wait for image load (for first-time loads)
        $image.on('load', function() {
          console.log('Image load event fired');
          updateIndicatorPosition();
        });
        
        // Strategy 2: Use setTimeout to allow DOM to settle after modal opens
        setTimeout(function() {
          console.log('Delayed update after modal open (100ms)');
          updateIndicatorPosition();
        }, 100);
        
        // Strategy 3: If image is cached (complete=true), use longer delays
        // because the image reports as complete but dimensions aren't ready yet
        if ($image[0].complete) {
          console.log('Image is cached - using multiple delayed updates');
          
          setTimeout(function() {
            console.log('Cached image update attempt (150ms)');
            updateIndicatorPosition();
          }, 150);
          
          setTimeout(function() {
            console.log('Cached image update attempt (300ms)');
            updateIndicatorPosition();
          }, 300);
          
          // Final attempt for stubborn cases
          setTimeout(function() {
            console.log('Cached image final update attempt (500ms)');
            updateIndicatorPosition();
          }, 500);
        }
        
        // Strategy 4: Listen for dialog/modal open events
        $(document).on('dialogopen', function() {
          setTimeout(function() {
            console.log('Dialog opened, updating position');
            updateIndicatorPosition();
          }, 150);
        });
        
        // Handle clicks on overlay
        $overlay.on('click', function(e) {
          const offset = $image.offset();
          const width = $image.width();
          const height = $image.height();
          
          // Calculate relative position
          focalX = (e.pageX - offset.left) / width;
          focalY = (e.pageY - offset.top) / height;
          
          // Clamp values
          focalX = Math.max(0, Math.min(1, focalX));
          focalY = Math.max(0, Math.min(1, focalY));
          
          // Update hidden fields
          $focalXInput.val(focalX.toFixed(2)).trigger('change');
          $focalYInput.val(focalY.toFixed(2)).trigger('change');
          
          // Update indicator position
          updateIndicatorPosition();
          
          // Show feedback
          $indicator.addClass('focal-point-indicator--active');
          setTimeout(function() {
            $indicator.removeClass('focal-point-indicator--active');
          }, 300);
        });
        
        // Update on resize
        $(window).on('resize', updateIndicatorPosition);
      });
    }
  };

})(jQuery, Drupal, once);
