(($, Drupal) => {
  Drupal.behaviors.azBarrioOffCanvasNav = {
    attach: () => {
      $('.navbar-offcanvas').on('opened.az.offcanvasmenu', (e) => {
        if (
          $(e.target.ownerDocument.activeElement).attr('id') === 'jsAzSearch'
        ) {
          $('#block-az-barrio-offcanvas-searchform input').trigger('focus');
        }
      });
    },
  };
})(jQuery, Drupal);
