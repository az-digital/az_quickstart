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
        // Focal points are stored relative to original dimensions.
        const originalW = parseFloat($img.attr('data-original-width')) || $img[0].naturalWidth;
        const originalH = parseFloat($img.attr('data-original-height')) || $img[0].naturalHeight;

        // Skip if dimensions not available yet
        if (!originalW || !originalH || !containerW || !containerH) {
          return;
        }

        // Calculate aspect ratios to determine crop direction
        const imageRatio = originalW / originalH;
        const containerRatio = containerW / containerH;

        // Calculate the SCALED dimensions after object-fit: cover.
        // object-fit: cover scales the image to fill the container while maintaining aspect ratio.
        let scaledW, scaledH;
        
        if (imageRatio > containerRatio) {
          // Image is WIDER than container (will be cropped horizontally)
          // Scale to match container HEIGHT
          scaledH = containerH;
          scaledW = containerH * imageRatio;
        } else {
          // Image is TALLER than container (will be cropped vertically)
          // Scale to match container WIDTH
          scaledW = containerW;
          scaledH = containerW / imageRatio;
        }

        let objectPosX, objectPosY;

        if (imageRatio > containerRatio) {
          // Image is WIDER than container (cropped horizontally - left/right sides cut off)
          // Apply formula to X using SCALED dimensions, use focal point directly for Y
          objectPosX = (focalX * scaledW - 0.5 * containerW) / (scaledW - containerW);
          objectPosY = focalY;
        } else {
          // Image is TALLER than container (cropped vertically - top/bottom cut off)
          // Use focal point directly for X, apply formula to Y using SCALED dimensions
          objectPosX = focalX;
          objectPosY = (focalY * scaledH - 0.5 * containerH) / (scaledH - containerH);
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
