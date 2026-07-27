/**
 * @file
 * Dynamically calculates object-position for az_quickstart:ranking-image
 * based on focal point.
 *
 * Uses the formula:
 * objectPosX = (focalX * imageW - 0.5 * containerW) / (imageW - containerW)
 * objectPosY = (focalY * imageH - 0.5 * containerH) / (imageH - containerH)
 *
 * This ensures the focal point stays centered in the visible area when
 * object-fit: cover crops the image. imageW/imageH come from the loaded
 * <img>'s own naturalWidth/naturalHeight.
 *
 * Targets .az-ranking-image__img and lives on the component itself, not
 * in az_ranking, because focal_x/focal_y are plain az_quickstart:
 * ranking-image props with no dependency on az_ranking, and the component
 * must keep working (focal point included) wherever it's placed, Canvas
 * or paragraph-authored, az_ranking installed or not.
 */

((Drupal, once) => {
  Drupal.behaviors.azRankingImageFocalPoint = {
    attach: (context) => {
      const images = once(
        'az-ranking-image-focal-point',
        '.az-ranking-image__img',
        context,
      );

      if (images.length === 0) return;

      /**
       * Calculate object-position for an image based on focal point and dimensions.
       *
       * @param {Element} img - Image element.
       */
      const calculateObjectPosition = (img) => {
        const focalX = parseFloat(img.getAttribute('data-focal-x'));
        const focalY = parseFloat(img.getAttribute('data-focal-y'));

        // Skip if no focal point data
        if (Number.isNaN(focalX) || Number.isNaN(focalY)) {
          return;
        }

        // Get container dimensions (the visible area)
        const containerW = img.offsetWidth;
        const containerH = img.offsetHeight;

        // Use the loaded (styled-derivative) image's own natural dimensions.
        // The formula below only ever uses these as a RATIO
        // (imageRatio = originalW / originalH), never as absolute values,
        // and az_ranking_responsive's image_scale effect always preserves
        // aspect ratio (that's what distinguishes it from
        // image_scale_and_crop), even when upscaling - so naturalWidth/
        // naturalHeight of the styled derivative the browser actually
        // loaded gives the exact same ratio as the true original, with no
        // need to pass original dimensions down as a separate prop at all.
        const originalW = img.naturalWidth;
        const originalH = img.naturalHeight;

        // Skip if dimensions not available yet
        if (!originalW || !originalH || !containerW || !containerH) return;

        // Calculate aspect ratios to determine crop direction
        const imageRatio = originalW / originalH;
        const containerRatio = containerW / containerH;

        // Calculate the SCALED dimensions after object-fit: cover.
        // object-fit: cover scales the image to fill the container while maintaining aspect ratio.
        let scaledW;
        let scaledH;

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

        let objectPosX;
        let objectPosY;

        if (imageRatio > containerRatio) {
          // Image is WIDER than container (cropped horizontally - left/right sides cut off)
          // Apply formula to X using SCALED dimensions, use focal point directly for Y
          objectPosX =
            (focalX * scaledW - 0.5 * containerW) / (scaledW - containerW);
          objectPosY = focalY;
        } else {
          // Image is TALLER than container (cropped vertically - top/bottom cut off)
          // Use focal point directly for X, apply formula to Y using SCALED dimensions
          objectPosX = focalX;
          objectPosY =
            (focalY * scaledH - 0.5 * containerH) / (scaledH - containerH);
        }

        // Convert to percentage and clamp between 0-100%
        objectPosX = Math.max(0, Math.min(100, objectPosX * 100));
        objectPosY = Math.max(0, Math.min(100, objectPosY * 100));

        // Apply to image
        img.style.objectPosition = `${objectPosX}% ${objectPosY}%`;
      };

      /**
       * Process all images.
       */
      const processImages = () => {
        images.forEach((img) => {
          // If image is already loaded, calculate immediately
          if (img.complete && img.naturalWidth > 0) {
            calculateObjectPosition(img);
          } else {
            // Wait for image to load
            img.addEventListener('load', () => calculateObjectPosition(img), {
              once: true,
            });
          }
        });
      };

      // Initial calculation
      processImages();

      // Recalculate on window resize (debounced)
      let resizeTimer;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
          images.forEach((img) => calculateObjectPosition(img));
        }, 250);
      });
    },
  };
})(Drupal, once);
