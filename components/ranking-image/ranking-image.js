/**
 * @file
 * Keeps a ranking image's focal point in view when CSS crops the image.
 *
 * ranking-image.css sets object-fit: cover, which fills the box and cuts off
 * whatever does not fit. This sets object-position so the point an editor
 * picked survives that cut. Runs on .az-ranking-image__img, once on load and
 * again after a resize.
 *
 * It lives with the component rather than in az_ranking because focal_x and
 * focal_y are ordinary ranking-image props. The component has to keep
 * working wherever it is placed - in Canvas or authored as a paragraph, with
 * az_ranking installed or not.
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

        // No focal point set on this image, so leave object-position alone.
        if (Number.isNaN(focalX) || Number.isNaN(focalY)) {
          return;
        }

        const containerW = img.offsetWidth;
        const containerH = img.offsetHeight;

        /*
         * These are the dimensions of the styled derivative the browser
         * actually downloaded, not of the original file - and that is fine,
         * because everything below uses them only as a ratio. The
         * az_ranking_responsive style scales, and scaling keeps the aspect
         * ratio (that is what separates image_scale from
         * image_scale_and_crop), so the derivative's ratio equals the
         * original's. It saves passing the original dimensions down as a
         * separate prop.
         */
        const originalW = img.naturalWidth;
        const originalH = img.naturalHeight;

        // Nothing to measure against yet - the image or its box has no size.
        if (!originalW || !originalH || !containerW || !containerH) return;

        const imageRatio = originalW / originalH;
        const containerRatio = containerW / containerH;

        /*
         * Work out how big cover made the image. It scales the image - up or
         * down - until both sides reach or pass the container, so whichever
         * side the image is proportionally longer on is the side that
         * overflows and gets cropped.
         */
        let scaledW;
        let scaledH;

        if (imageRatio > containerRatio) {
          scaledH = containerH;
          scaledW = containerH * imageRatio;
        } else {
          scaledW = containerW;
          scaledH = containerW / imageRatio;
        }

        let objectPosX;
        let objectPosY;

        /*
         * Work out where to sit the image inside its box. Meet these
         * requirements:
         * 1. Only an axis with slack can slide. cover leaves at most one
         *    axis longer than the box; the other already matches it exactly,
         *    so its focal value passes straight through.
         * 2. On a sliding axis, object-position is a share of the slack
         *    rather than of the image, so the focal point converts into that
         *    scale:
         *      pos = (focal * scaled - 0.5 * container) / (scaled - container)
         *    which lands the focal point in the middle of what stays visible.
         * 3. Under half a pixel of slack counts as none. An image whose
         *    ratio matches its box has nowhere to slide, and dividing by
         *    that leftover would throw it to an edge instead.
         */
        const overflowX = scaledW - containerW;
        const overflowY = scaledH - containerH;

        objectPosX =
          overflowX > 0.5
            ? (focalX * scaledW - 0.5 * containerW) / overflowX
            : focalX;
        objectPosY =
          overflowY > 0.5
            ? (focalY * scaledH - 0.5 * containerH) / overflowY
            : focalY;

        /*
         * Clamp because the formula can overshoot when the focal point sits
         * near an edge, and object-position past 0-100% would pull the image
         * away from the box and leave a gap.
         */
        objectPosX = Math.max(0, Math.min(100, objectPosX * 100));
        objectPosY = Math.max(0, Math.min(100, objectPosY * 100));

        img.style.objectPosition = `${objectPosX}% ${objectPosY}%`;
      };

      /**
       * Positions every image, waiting for any that have not loaded yet.
       *
       * The calculation needs naturalWidth, which is only there once the
       * file has arrived. Anything that has finished loading by the time we
       * run is handled on the spot; the rest get a one-shot load listener.
       */
      const processImages = () => {
        images.forEach((img) => {
          if (img.complete && img.naturalWidth > 0) {
            calculateObjectPosition(img);
          } else {
            img.addEventListener('load', () => calculateObjectPosition(img), {
              once: true,
            });
          }
        });
      };

      processImages();

      /*
       * A resize changes the container's shape, which changes how much gets
       * cropped, so the position has to be worked out again. Debounced
       * because a drag fires this continuously.
       */
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
