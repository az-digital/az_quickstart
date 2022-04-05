((Drupal) => {
  'use strict';

  Drupal.behaviors.calculateScrollbarWidth = {
    attach: () => {
        document.documentElement.style.setProperty(
          "--scrollbar-width",
          `${window.innerWidth - document.documentElement.clientWidth}px`
        );
    },
  };
  Drupal.behaviors.azParagraphsPushSidebarDown = {
    attach: () => {
      const contentRegion = document.getElementById("content");
      const sidebarPusher = document.querySelectorAll(
        '.paragraph.full-width-background'
      );
      const lastFullWidthElement = sidebarPusher[sidebarPusher.length - 1];
      const contentRegionPosition = contentRegion.getBoundingClientRect();
      const style =
        sidebarPusher[0].currentStyle ||
        window.getComputedStyle(lastFullWidthElement, "");
      const bottomMargin = style.marginBottom;
      const contentRegionTop = contentRegionPosition.top;
      const sidebarPusherPosition = lastFullWidthElement.getBoundingClientRect();
      const sidebarPusherBottom = sidebarPusherPosition.bottom;
      const sidebarTopMargin = (sidebarPusherBottom - contentRegionTop) + bottomMargin;
      document.documentElement.style.setProperty(
        "--sidebar-top-margin",
        `${sidebarTopMargin}`
      );
    },
  };
  Drupal.behaviors.calculateFullWidthNegativeMargins = {
    attach: () => {
      const contentRegion = document.getElementById("block-az-barrio-content");
      const contentRegionPosition = contentRegion.getBoundingClientRect();
      const distanceFromLeft = contentRegionPosition.left;
      const distanceFromRight = contentRegionPosition.right;
      const negativeLeftMargin = 0 - distanceFromLeft;
      const negativeRightMargin = 0 - distanceFromRight;
      document.documentElement.style.setProperty(
        "--full-width-left-distance",
        `${negativeLeftMargin}px`
      );
      document.documentElement.style.setProperty(
        "--full-width-right-distance",
        `${negativeRightMargin}px`
      );
    },
  };
})(Drupal);
