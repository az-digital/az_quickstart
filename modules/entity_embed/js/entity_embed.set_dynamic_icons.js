/* This script is responsible for setting the correct image(s) on the admin
 * toolbar, as we cannot build the JS for it because we do not know how
 * many entity embed buttons are there in the system. The number of buttons
 * created are based on the number of embed buttons.
 */
(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.entityEmbedSetDynamicIcons = {
    attach: function (context) {
      // Get the available Embed Buttons from Drupal.
      Object.values(drupalSettings.embedButtons || {}).forEach(function (button) {
        // Iterate through the embed buttons and set the corresponding background image.
        const selector = '.ckeditor5-toolbar-button-' + button.id;
        const iconUrl = button.icon.endsWith('svg') ? button.icon : '/' + drupalSettings.modulePath + '/js/ckeditor5_plugins/drupalentity/entity.svg';
        once('entityEmbedSetDynamicIcons', selector, context).forEach((button) => {
          button.style['background-image'] = `url('${iconUrl}')`
        });
      });
    },
  }
})(Drupal, drupalSettings, once);
