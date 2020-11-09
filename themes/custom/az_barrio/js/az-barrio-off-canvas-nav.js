(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      $('#navbarOffcanvasDemo').on('opened.az.offcanvasmenu', function (e) {
        console.log(e);
        //$('#myInput').trigger('focus')
      });
    }
  };
})(jQuery, Drupal);
