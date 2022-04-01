((Drupal) => {
  'use strict';

  Drupal.behaviors.calculateScrollbarWidth = {
    attach: (context) => {
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
        "--full-width-thirty-three-percent",
        `${negativeLeftMargin}px`
      );
      document.documentElement.style.setProperty(
        "--full-width-sixty-six-percent",
        `${negativeRightMargin}px`
      );
    },
  };
  // recalculate on resize
  window.addEventListener("resize", (event) => {
    Drupal.behaviors.azParagraphsPushSidebarDown,
    Drupal.behaviors.calculateFullWidthNegativeMargins,
    Drupal.behaviors.calculateScrollbarWidth;
  });
  window.addEventListener("DOMContentLoaded", (event) => {
    Drupal.behaviors.azParagraphsPushSidebarDown,
    Drupal.behaviors.calculateFullWidthNegativeMargins,
    Drupal.behaviors.calculateScrollbarWidth;
  });
  // recalculate on load (assets loaded as well)
  window.addEventListener("load", (event) => {
    Drupal.behaviors.azParagraphsPushSidebarDown,
    Drupal.behaviors.calculateFullWidthNegativeMargins,
    Drupal.behaviors.calculateScrollbarWidth;
  });

})(Drupal);
