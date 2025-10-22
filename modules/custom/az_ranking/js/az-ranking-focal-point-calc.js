/**
 * @file
 * Dynamically calculates object-position for ranking images based on focal point.
 *
 * Uses the formula:
 * objectPosX = (focalX * imageW - 0.5 * containerW) / (imageW - containerW)
 * objectPosY = (focalY * imageH - 0.5 * containerH) / (imageH - containerH)
 *
 * This ensures the focal point stays centered in the visible area when
 * object-fit: cover crops the image.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.azRankingFocalPoint = {
    attach: function (context, settings) {
      const $images = $(once('az-ranking-focal-point', '.ranking-img', context));

      if ($images.length === 0) {
        return;
      }

      /**
       * Calculate object-position for an image based on focal point and dimensions.
       */
      function calculateObjectPosition($img) {
        // Focal point values are stored as decimals (0-1)
        const focalX = parseFloat($img.attr('data-focal-x'));
        const focalY = parseFloat($img.attr('data-focal-y'));

        // Skip if no focal point data
        if (isNaN(focalX) || isNaN(focalY)) {
          console.warn('⚠️ No focal point data for image:', $img.attr('src'));
          return;
        }

        // Get container dimensions (the visible area)
        const containerW = $img.width();
        const containerH = $img.height();

        // Get ORIGINAL image dimensions (before any image style scaling).
        // When image styles are applied, naturalWidth/Height would be the
        // scaled dimensions, but focal points are relative to original.
        const imageW = parseFloat($img.attr('data-original-width')) || $img[0].naturalWidth;
        const imageH = parseFloat($img.attr('data-original-height')) || $img[0].naturalHeight;

        // Skip if dimensions not available yet
        if (!imageW || !imageH || !containerW || !containerH) {
          return;
        }

        // Calculate aspect ratios to determine crop direction
        const imageRatio = imageW / imageH;
        const containerRatio = containerW / containerH;

        let objectPosX, objectPosY;

        if (imageRatio > containerRatio) {
          // Image is WIDER than container (cropped horizontally - left/right sides cut off)
          // Apply formula to X, use focal point directly for Y
          objectPosX = (focalX * imageW - 0.5 * containerW) / (imageW - containerW);
          objectPosY = focalY;
        } else {
          // Image is TALLER than container (cropped vertically - top/bottom cut off)
          // Use focal point directly for X, apply formula to Y
          objectPosX = focalX;
          objectPosY = (focalY * imageH - 0.5 * containerH) / (imageH - containerH);
        }

        // Convert to percentage and clamp between 0-100%
        objectPosX = Math.max(0, Math.min(100, objectPosX * 100));
        objectPosY = Math.max(0, Math.min(100, objectPosY * 100));

        // Apply to image
        $img.css('object-position', objectPosX + '% ' + objectPosY + '%');
      }

      /**
       * Process all images.
       */
      function processImages() {
        $images.each(function() {
          const $img = $(this);

          // If image is already loaded, calculate immediately
          if ($img[0].complete && $img[0].naturalWidth > 0) {
            calculateObjectPosition($img);
          } else {
            // Wait for image to load
            $img.on('load', function() {
              calculateObjectPosition($img);
            });
          }
        });
      }

      // Initial calculation
      processImages();

      // Recalculate on window resize (debounced)
      let resizeTimer;
      $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          $images.each(function() {
            calculateObjectPosition($(this));
          });
        }, 250);
      });
    }
  };

})(jQuery, Drupal, once);
