/**
 * @file
 * az-zoom.drupal.js
 *
 * This file contains the JavaScript needed to create a zoom effect on images.
 */
import FocusImg from './az-zoom.js';

((drupalSettings, Drupal) => {
  Drupal.behaviors.zoomEffect = {
    attach(context, settings) {
      context.querySelectorAll('.image-zoom-container').forEach((element) => {
        // Find the <img> tag within the .image-zoom-container
        const imgElement = element.querySelector('.original-image');
        const fid = imgElement.getAttribute('fid');
        new FocusImg({
          imageSrc: settings.AZZoom.image_urls[fid] || imgElement.src,
          parentElement: element,
          zoomFactor: settings.AZZoom.image_zoom_factor || '250%',
          smoother: settings.AZZoom.image_smoother || true,
          width: settings.AZZoom.image_width || '100%',
          height: settings.AZZoom.image_height || '66.7%',
          displayLoc: settings.AZZoom.display_loc || false,
          displayZoom: settings.AZZoom.display_zoom || false,
          zoomOnScroll: settings.AZZoom.zoom_on_scroll || false,
        });
      });
    },
  };
})(drupalSettings, Drupal);
