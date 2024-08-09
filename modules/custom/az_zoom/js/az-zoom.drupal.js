/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
import FocusImg from './az-zoom.js';
(function (drupalSettings, Drupal) {
  Drupal.behaviors.zoomEffect = {
    attach: function attach(context, settings) {
      context.querySelectorAll('.image-zoom-container').forEach(function (element) {
        var imgElement = element.querySelector('.original-image');
        var fid = imgElement.getAttribute('fid');
        new FocusImg({
          imageSrc: settings.AZZoom.image_urls[fid] || imgElement.src,
          parentElement: element,
          zoomFactor: settings.AZZoom.image_zoom_factor || '250%',
          smoother: settings.AZZoom.image_smoother || true,
          width: settings.AZZoom.image_width || '100%',
          height: settings.AZZoom.image_height || '66.7%',
          displayLoc: settings.AZZoom.display_loc || false,
          displayZoom: settings.AZZoom.display_zoom || false,
          zoomOnScroll: settings.AZZoom.zoom_on_scroll || false
        });
      });
    }
  };
})(drupalSettings, Drupal);