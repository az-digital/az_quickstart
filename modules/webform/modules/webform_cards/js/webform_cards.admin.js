/**
 * @file
 * JavaScript behaviors for webform cards admin.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Webform cards administration.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformCardsAdmin = {
    attach: function (context) {
      // Determine if the form is the context or it is within the context.
      var $forms = $(context).is('form.webform-edit-form')
        ? $(context)
        : $('form.webform-edit-form', context);

      $(once('webform-cards-admin', $forms)).each(function () {
        var $form = $(this);
        if ($form.find('[data-webform-type="webform_wizard_page"]').length) {
          $('#webform-ui-add-page').parent('li').show();
          $('#webform-ui-add-card').parent('li').hide();
        }
        else if ($form.find('[data-webform-type="webform_card"]').length) {
          $('#webform-ui-add-page').parent('li').hide();
          $('#webform-ui-add-card').parent('li').show();
        }
        else {
          $('#webform-ui-add-page').parent('li').show();
          $('#webform-ui-add-card').parent('li').show();
        }
      });
    }
  };

})(jQuery, Drupal, once);
