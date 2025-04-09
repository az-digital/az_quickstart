/**
 * @file
 * Provides helper functions to ensure proper display of full-width-paragraphs.
 */
(() => {
  /**
   * Calculates scroll bar width if any and assigns the value to the
   * `--scrollbar-width` CSS variable on the html element.
   */
  function calculateScrollbarWidth() {
    document.documentElement.style.setProperty(
      '--scrollbar-width',
      `${window.innerWidth - document.documentElement.clientWidth}px`,
    );
  }

  /**
   * Calculates and sets margin required to push sidebars beneath the last
   * full-width paragraph in the Content region of the page.
   *
   * This function assigns values to the `--sidebar-top-margin` CSS variable on
   * the `html` element.
   */
  function pushSidebarsDown() {
    const contentRegion = document.querySelector('main.main-content');
    if (contentRegion !== null) {
      const allFullWidthElements = contentRegion.querySelectorAll(
        '.paragraph.full-width-background',
      );
      if (allFullWidthElements.length === 0) {
        return;
      }
      const lastFullWidthElement =
        allFullWidthElements[allFullWidthElements.length - 1];
      const contentRegionPosition = contentRegion.getBoundingClientRect();
      const style = window.getComputedStyle(lastFullWidthElement, '');
      const bottomMargin = parseFloat(style.marginBottom);
      const contentRegionTop = contentRegionPosition.top;
      const lastFullWidthElementPosition =
        lastFullWidthElement.getBoundingClientRect();
      const lastFullWidthElementBottom = lastFullWidthElementPosition.bottom;
      const sidebarTopMargin =
        lastFullWidthElementBottom - contentRegionTop + bottomMargin;
      if (sidebarTopMargin) {
        document.documentElement.style.setProperty(
          '--sidebar-top-margin',
          `${sidebarTopMargin}px`,
        );
      }
    }
  }

  /**
   * Calculates and sets negative margins required for full width backgrounds.
   *
   * This function assigns values to the `--full-width-left-distance` and
   * `--full-width-right-distance` CSS variables on the `html` element.
   */
  function calculateFullWidthNegativeMargins() {
    const contentRegion = document.querySelectorAll('.block-system-main-block');
    if (contentRegion.length > 0) {
      const contentRegionPosition = contentRegion[0].getBoundingClientRect();
      const distanceFromLeft = contentRegionPosition.left;
      const distanceFromRight = contentRegionPosition.right;
      const negativeLeftMargin = 0 - distanceFromLeft;
      const negativeRightMargin =
        distanceFromRight - document.documentElement.clientWidth;
      document.documentElement.style.setProperty(
        '--full-width-left-distance',
        `${negativeLeftMargin}px`,
      );
      document.documentElement.style.setProperty(
        '--full-width-right-distance',
        `${negativeRightMargin}px`,
      );
    }
    const contentTopAndBottomBlocks = document.querySelectorAll(
      '.region-content-top > .block, .region-content-bottom > .block',
    );
    if (contentTopAndBottomBlocks.length > 0) {
      const negativeAutoMargin =
        -(
          document.documentElement.clientWidth -
          contentTopAndBottomBlocks[0].getBoundingClientRect().width
        ) / 2;
      document.documentElement.style.setProperty(
        '--full-width-auto-distance',
        `${negativeAutoMargin}px`,
      );
    }
  }

  /**
   * Executes functions to set up the page layout.
   */
  function setFullWidthLayout() {
    calculateScrollbarWidth();
    calculateFullWidthNegativeMargins();
    pushSidebarsDown();
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', setFullWidthLayout);

  // Recalculate values on window resize
  window.addEventListener('resize', setFullWidthLayout);

  // Recalculate values when azVideoPlay custom event fires
  window.addEventListener('azVideoPlay', setFullWidthLayout);
})();
