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
      // Early return if we're not in a dialog/modal context.
      // The focal point picker is only added to modal forms.
      if (!$(context).closest('.ui-dialog').length && !$(context).hasClass('ui-dialog-content')) {
        return;
      }
      
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
          }
        } catch (e) {
          // If we can't access parent (cross-origin), assume it's valid.
          isFromAzRanking = false;
        }
        
        // Hide the section if not from az_ranking.
        if (!isFromAzRanking) {
          $focalPointSection.hide();
          return;
        }
      }
      
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
        
        if ($focalXInput.length === 0 || $focalYInput.length === 0) {
          console.warn('Could not find focal point input fields! Focal point changes will not be saved.');
        }
        
        function updateIndicatorPosition() {
          const width = $image.width();
          const height = $image.height();
          
          const indicatorLeft = (focalX * width);
          const indicatorTop = (focalY * height);
          
          $indicator.css({
            left: indicatorLeft + 'px',
            top: indicatorTop + 'px'
          });
        }
        
        // Set initial position
        if ($image[0].complete) {
          updateIndicatorPosition();
        } else {
          $image.on('load', function() {
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
