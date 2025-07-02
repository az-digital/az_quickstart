(function ($) {

  'use strict';

  Drupal.behaviors.workbenchAccess = {
    attach: function (context, settings) {
      $(once('field-switch', '#edit-add', context)).each(function () {
        // We hide mass assign.
        $('.js-form-item-editors-add-mass').hide();
        $(this).find('.switch').click(function (e) {
          e.preventDefault();
          $('.js-form-item-editors-add').toggle();
          $('.js-form-item-editors-add-mass').toggle();
        })
      });
    }
  };

})(jQuery, drupalSettings);
