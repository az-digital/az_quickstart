(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      $('#navbarOffcanvasDemo').on('opened.az.offcanvasmenu', function (e) {
	if ($(e.target.ownerDocument.activeElement).attr('id') === 'jsAzSearch') {
          $('#search-block-form--2 input').trigger('focus');
	}
      });
    }
  };
})(jQuery, Drupal);
