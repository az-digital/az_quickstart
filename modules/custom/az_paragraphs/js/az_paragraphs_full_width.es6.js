(($, Drupal) => {
  Drupal.behaviors.azParagraphsFullWidth = {
    attach() {
      function _calculateScrollbarWidth() {
        document.documentElement.style.setProperty(
          "--scrollbar-width",
          `${window.innerWidth - document.documentElement.clientWidth}px`
        );
      }
      // recalculate on resize
      window.addEventListener("resize", _calculateScrollbarWidth, false);

      // recalculate on dom load
      window.addEventListener('DOMContentLoaded', (event) => {
        _calculateScrollbarWidth();
      });

      // recalculate on load (assets loaded as well)
      window.addEventListener("load", _calculateScrollbarWidth);
    },
  };
})(jQuery, Drupal);
