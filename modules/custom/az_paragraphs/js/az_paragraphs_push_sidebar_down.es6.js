((Drupal) => {
  Drupal.behaviors.azParagraphsPushSidebarDown = {
    attach() {
      function _setSidebarTopMargin() {
        const contentRegion = document.getElementById("content");
        const sidebarPusher = document.querySelectorAll(
          '[push-sidebar-down="push-sidebar-down"]'
        );
        const contentRegionPosition = contentRegion.getBoundingClientRect();
        const style =
          sidebarPusher[0].currentStyle ||
          window.getComputedStyle(sidebarPusher[0], "");
        const bottomMargin = style.marginBottom;
        const contentRegionTop = contentRegionPosition.top;
        const sidebarPusherPosition = sidebarPusher[0].getBoundingClientRect();
        const sidebarPusherBottom = sidebarPusherPosition.bottom;
        const sidebarTopMargin = (sidebarPusherBottom - contentRegionTop) + bottomMargin;
        document.documentElement.style.setProperty(
          "--sidebar-top-margin",
          `${sidebarTopMargin}px`
        );
      }
      function _calculateLeftMargin() {
        const marginLeft = document.querySelectorAll(
          '[push-sidebar-down="push-sidebar-down"]'
        );
        const marginLeftPosition = marginLeft[0].getBoundingClientRect();
        const marginLeftLeft = marginLeftPosition.left;
        const marginLeftRight = marginLeftPosition.right;

        const negativeLeftMargin = 0 - marginLeftLeft;
        const rightMargin = 0 - marginLeftRight;

        document.documentElement.style.setProperty(
          "--full-width-thirty-three-percent",
          `${negativeLeftMargin}px`
        );
        document.documentElement.style.setProperty(
          "--full-width-sixty-six-percent",
          `${rightMargin}px`
        );
      }

      // recalculate on resize
      window.addEventListener("resize", _setSidebarTopMargin, false);
      // window.addEventListener("resize", _calculateLeftMargin, false);

      window.addEventListener("DOMContentLoaded", (event) => {
        _setSidebarTopMargin();
        _calculateLeftMargin();
      });
      // recalculate on load (assets loaded as well)
      window.addEventListener("load", _setSidebarTopMargin);
    },
  };
})(Drupal);
