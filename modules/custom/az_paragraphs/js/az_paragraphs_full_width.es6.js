/**
 * @file
 * Provides helper functions to ensure proper display of full-width-paragraphs.
 */

((Drupal, window, document) => {
  /**
   * Calculates scroll bar width if any and assigns the value to the
   * `--scrollbar-width` CSS variable on the html element.
   */
  function calculateScrollbarWidth() {
    document.documentElement.style.setProperty(
      "--scrollbar-width",
      `${window.innerWidth - document.documentElement.clientWidth}px`
    );
  }

  /**
   * Calculates and sets margin required to push sidebars beneath the last
   * full-width paragraph on the page.
   *
   * This function assigns values to the `--sidebar-top-margin` CSS variable on
   * the `html` element.
   */
  function pushSidebarsDown() {
    const contentRegion = document.querySelectorAll("main.main-content");
    const allFullWidthElements = document.querySelectorAll(
      ".paragraph.full-width-background"
    );
    const lastFullWidthElement =
      allFullWidthElements[allFullWidthElements.length - 1];
    const contentRegionPosition = contentRegion[0].getBoundingClientRect();
    const style =
      allFullWidthElements[0].currentStyle ||
      window.getComputedStyle(lastFullWidthElement, "");
    const bottomMargin = style.marginBottom;
    const contentRegionTop = contentRegionPosition.top;
    const lastFullWidthElementPosition =
      lastFullWidthElement.getBoundingClientRect();
    const lastFullWidthElementBottom = lastFullWidthElementPosition.bottom;
    const sidebarTopMargin =
      lastFullWidthElementBottom - contentRegionTop + bottomMargin;
    document.documentElement.style.setProperty(
      "--sidebar-top-margin",
      `${sidebarTopMargin}`
    );
  }

  /**
   * Calculates and sets negative margins required for full with backgrounds.
   *
   * This function assigns values to the --full-width-left-distance` and
   * `--full-width-right-distance` CSS variables on the `html` element.
   */
  function calculateFullWidthNegativeMargins() {
    const contentRegion = document.querySelectorAll(".block-system-main-block");
    const contentRegionPosition = contentRegion[0].getBoundingClientRect();
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
  }

  /**
   * Attaches the the functions defined in this file to the document.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   After the document loads, execute functions.
   */
  Drupal.behaviors.azParagraphsFullWidthElements = {
    attach: () => {
      calculateScrollbarWidth();
      calculateFullWidthNegativeMargins();
      pushSidebarsDown();
    },
  };

  /**
   * Recalculates values for CSS variables on window resize.
   */
  window.addEventListener("resize", () => {
    calculateScrollbarWidth();
    calculateFullWidthNegativeMargins();
    pushSidebarsDown();
  });

  /**
   * Recalculates values for CSS variables on youtube iframe ready.
   */
  window.addEventListener("onYouTubeIframeAPIReady", () => {
    calculateScrollbarWidth();
    calculateFullWidthNegativeMargins();
    pushSidebarsDown();
  });
})(Drupal, this, this.document);
