/**
 * @file
 * Focal Point Picker for az_image media type.
 *
 * Adds a clickable overlay to the focal point picker section in the media edit form.
 */

(function ($, Drupal, once) {
  'use strict';

  console.log('*** AZ Ranking Focal Point Picker JavaScript LOADED ***');

  Drupal.behaviors.azRankingFocalPointPicker = {
    attach: function (context, settings) {
      console.log('=== AZ Ranking Focal Point Picker: attach() called ===');
      
      // First, check if we're in a modal opened from an az_ranking widget.
      // Look for the focal point section in the current dialog.
      const $focalPointSection = $('.az-ranking-focal-point-section', context);
      
      if ($focalPointSection.length > 0) {
        // We're in a modal with the focal point section.
        // Check if the parent window has az-ranking-widget elements.
        let isFromAzRanking = false;
        
        try {
          // Try to access the parent/opener document (may fail due to security).
          const $parentDoc = $(window.parent.document);
          
          // Look for az-ranking-widget class in the parent document.
          if ($parentDoc.find('.az-ranking-widget').length > 0) {
            isFromAzRanking = true;
            console.log('Modal opened from az_ranking paragraph context');
          } else {
            console.log('Modal NOT opened from az_ranking paragraph context');
          }
        } catch (e) {
          // If we can't access parent (cross-origin), assume it's valid.
          console.log('Cannot access parent document, assuming valid context');
          isFromAzRanking = true;
        }
        
        // Hide the section if not from az_ranking.
        if (!isFromAzRanking) {
          $focalPointSection.hide();
          console.log('Hiding focal point section - not from az_ranking context');
          return;
        }
      }
      
      // Find the focal point picker wrapper in the media form
      const $pickerWrappers = $('.focal-point-picker-wrapper', context);
      console.log('Found', $pickerWrappers.length, 'focal point picker wrapper(s)');
      
      $pickerWrappers.each(function(index) {
        const $wrapper = $(this);
        
        // Check if already processed
        if ($wrapper.data('focal-point-processed')) {
          console.log('Picker', index, 'already processed, skipping');
          return;
        }
        $wrapper.data('focal-point-processed', true);
        
        console.log('Processing focal point picker', index);
        
        // Find the image
        const $image = $wrapper.find('.focal-point-picker-image');
        console.log('Found image:', $image.length);
        
        if ($image.length === 0) {
          console.log('No image found in picker wrapper');
          return;
        }
        
        // Get current focal point values from data attributes
        let focalX = parseFloat($wrapper.attr('data-focal-x')) || 0.5;
        let focalY = parseFloat($wrapper.attr('data-focal-y')) || 0.5;
        
        console.log('Initial focal point:', focalX, focalY);
        
        // Create overlay and indicator
        $wrapper.css('position', 'relative');
        
        const $overlay = $('<div class="focal-point-overlay"></div>');
        const $indicator = $('<div class="focal-point-indicator" title="Click to set focal point"></div>');
        
        $overlay.append($indicator);
        $wrapper.append($overlay);
        console.log('Overlay and indicator created');
        
        // Get hidden field inputs for storing focal point values
        const $focalXInput = $('input[name="field_focal_point_x[0][value]"]');
        const $focalYInput = $('input[name="field_focal_point_y[0][value]"]');
        
        console.log('Found focal X input:', $focalXInput.length);
        console.log('Found focal Y input:', $focalYInput.length);
        
        function updateIndicatorPosition() {
          const width = $image.width();
          const height = $image.height();
          
          const indicatorLeft = (focalX * width);
          const indicatorTop = (focalY * height);
          
          $indicator.css({
            left: indicatorLeft + 'px',
            top: indicatorTop + 'px'
          });
          
          console.log('Updated indicator position:', indicatorLeft, indicatorTop, 'for image size:', width, 'x', height);
        }
        
        // Set initial position
        if ($image[0].complete) {
          console.log('Image already loaded');
          updateIndicatorPosition();
        } else {
          console.log('Waiting for image to load');
          $image.on('load', function() {
            console.log('Image loaded');
            updateIndicatorPosition();
          });
        }
        
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
          
          console.log('New focal point:', focalX.toFixed(2), focalY.toFixed(2));
          
          // Update hidden fields
          $focalXInput.val(focalX.toFixed(2)).trigger('change');
          $focalYInput.val(focalY.toFixed(2)).trigger('change');
          
          console.log('Updated hidden fields');
          
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
