((Drupal, once) => {
  Drupal.behaviors.azBarrioOffCanvasSearch = {
    attach: (context) => {
      function focusOffCanvasSearchOnShow() {
        const offCanvasNav = document.querySelector('#azMobileNav');
        offCanvasNav.addEventListener('shown.bs.offcanvas', (event) => {
          if (event.relatedTarget.id === 'jsAzSearch') {
            document
              .querySelector('#azMobileNav .search-block-form input')
              .focus();
          }
        });
      }
      once('azBarrioOffCanvasSearch', '#azMobileNav').forEach(
        focusOffCanvasSearchOnShow,
        context,
      );
    },
  };
})(Drupal, once);
