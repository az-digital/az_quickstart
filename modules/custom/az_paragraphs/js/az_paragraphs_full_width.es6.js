((Drupal, window, document) => {
  Drupal.behaviors.calculateScrollbarWidth = {
    attach: () => {
      document.documentElement.style.setProperty(
        '--scrollbar-width',
        `${window.innerWidth - document.documentElement.clientWidth}px`,
      );
    },
  };
  Drupal.behaviors.azParagraphsPushSidebarDown = {
    attach: () => {
      const contentRegion = document.getElementById('content');
      const allFullWidthElements = document.querySelectorAll(
        '.paragraph.full-width-background',
      );
      const lastFullWidthElement =
        allFullWidthElements[allFullWidthElements.length - 1];
      const contentRegionPosition = contentRegion.getBoundingClientRect();
      const style =
        allFullWidthElements[0].currentStyle ||
        window.getComputedStyle(lastFullWidthElement, '');
      const bottomMargin = style.marginBottom;
      const contentRegionTop = contentRegionPosition.top;
      const lastFullWidthElementPosition =
        lastFullWidthElement.getBoundingClientRect();
      const lastFullWidthElementBottom = lastFullWidthElementPosition.bottom;
      const sidebarTopMargin =
        lastFullWidthElementBottom - contentRegionTop + bottomMargin;
      document.documentElement.style.setProperty(
        '--sidebar-top-margin',
        `${sidebarTopMargin}`,
      );
    },
  };
  Drupal.behaviors.calculateFullWidthNegativeMargins = {
    attach: () => {
      const contentRegion = document.getElementById('block-az-barrio-content');
      const contentRegionPosition = contentRegion.getBoundingClientRect();
      const distanceFromLeft = contentRegionPosition.left;
      const distanceFromRight = contentRegionPosition.right;
      const negativeLeftMargin = 0 - distanceFromLeft;
      const negativeRightMargin = 0 - distanceFromRight;
      document.documentElement.style.setProperty(
        '--full-width-left-distance',
        `${negativeLeftMargin}px`,
      );
      document.documentElement.style.setProperty(
        '--full-width-right-distance',
        `${negativeRightMargin}px`,
      );
    },
  };
  document.addEventListener('DOMContentLoaded', () => {
    Drupal.behaviors.azParagraphsPushSidebarDown.attach();
    Drupal.behaviors.calculateFullWidthNegativeMargins.attach();
  });
  window.addEventListener('resize', () => {
    Drupal.behaviors.calculateFullWidthNegativeMargins.attach();
    Drupal.behaviors.calculateScrollbarWidth.attach();
    Drupal.behaviors.azParagraphsPushSidebarDown.attach();
  });
})(Drupal, this, this.document);
