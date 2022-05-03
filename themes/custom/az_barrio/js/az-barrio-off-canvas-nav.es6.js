(function ($, Drupal) {
  Drupal.behaviors.azBarrioOffCanvasNav = {
    attach: function () {
      $('.navbar-offcanvas').on('opened.az.offcanvasmenu', function (e) {
	      if ($(e.target.ownerDocument.activeElement).attr('id') === 'jsAzSearch') {
          $('#block-az-barrio-offcanvas-searchform input').trigger('focus');
	      }
      });
    }
  };
})(jQuery, Drupal);
