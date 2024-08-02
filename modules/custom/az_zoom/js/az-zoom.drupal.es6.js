// @file az-zoom.drupal.js

import FocusImg from './az-zoom.js';

((Drupal, drupalSettings) => {
  Drupal.behaviors.zoomEffect = {
    attach: function (context, drupalSettings) {
      context.querySelectorAll('.image-zoom-container').forEach((element) => {
        // Find the <img> tag within the .image-zoom-container
        let imgElement = element.querySelector('.original-image');
        let fid = imgElement.getAttribute('fid');
        new FocusImg({
          imageSrc: drupalSettings.AZZoom.image_urls[fid] || imgElement.src,
          parentElement: element,
          zoomFactor: drupalSettings.AZZoom.image_zoom_factor || '250%',
          smoother: drupalSettings.AZZoom.image_smoother || true,
          width: drupalSettings.AZZoom.image_width || '100%',
          height: drupalSettings.AZZoom.image_height || '66.7%',
          displayLoc: drupalSettings.AZZoom.display_loc || false,
          displayZoom: drupalSettings.AZZoom.display_zoom || false,
          zoomOnScroll: drupalSettings.AZZoom.zoom_on_scroll || false,
        });
      });
    },
  };
})(Drupal, drupalSettings);
