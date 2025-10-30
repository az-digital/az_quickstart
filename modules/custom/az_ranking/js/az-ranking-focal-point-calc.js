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

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.azRankingFocalPoint = {
    attach: function (context, settings) {
      const images = once('az-ranking-focal-point', '.ranking-img', context);

      if (images.length === 0) {
        return;
      }

      /**
       * Calculate object-position for an image based on focal point and dimensions.
       */
      function calculateObjectPosition(img) {
        // Focal point values are stored as decimals (0-1)
        const focalX = parseFloat(img.getAttribute('data-focal-x'));
        const focalY = parseFloat(img.getAttribute('data-focal-y'));

        // Skip if no focal point data
        if (isNaN(focalX) || isNaN(focalY)) {
          console.warn('⚠️ No focal point data for image:', img.getAttribute('src'));
          return;
        }

        // Get container dimensions (the visible area)
        const containerW = img.offsetWidth;
        const containerH = img.offsetHeight;

        // Get ORIGINAL image dimensions (before any image style scaling).
        // Focal points are stored relative to original dimensions.
        const originalW = parseFloat(img.getAttribute('data-original-width')) || img.naturalWidth;
        const originalH = parseFloat(img.getAttribute('data-original-height')) || img.naturalHeight;

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
        img.style.objectPosition = objectPosX + '% ' + objectPosY + '%';
      }

      /**
       * Process all images.
       */
      function processImages() {
        images.forEach(function(img) {
          // If image is already loaded, calculate immediately
          if (img.complete && img.naturalWidth > 0) {
            calculateObjectPosition(img);
          } else {
            // Wait for image to load
            img.addEventListener('load', function() {
              calculateObjectPosition(img);
            });
          }
        });
      }

      // Initial calculation
      processImages();

      // Recalculate on window resize (debounced)
      let resizeTimer;
      window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          images.forEach(function(img) {
            calculateObjectPosition(img);
          });
        }, 250);
      });
    }
  };

})(Drupal, once);
