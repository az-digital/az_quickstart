/**
 * @file
 * Ajax theme overrides for AZ Barrio.
 *
 * This script provides a custom theme for the AJAX progress indicator,
 * utilizing Arizona Bootstrap classes. The indicator is positioned
 * in the center of the screen.
 */

((Drupal) => {
  // Constants for repeated values
  const Z_INDEX = 1261;
  const TOP_POSITION = '48.5%';
  const LEFT_POSITION = '49%';
  const SPINNER_SIZE = '3rem';

  // Define a dedicated CSS class for the spinner position
  const spinnerPositionClass = 'js-az-spinner-position';

  // Add CSS class to the document head
  const style = document.createElement('style');
  style.textContent = `
    .${spinnerPositionClass} {
      position: fixed;
      z-index: ${Z_INDEX};
      top: ${TOP_POSITION};
      left: ${LEFT_POSITION};
    }
  `;
  document.head.appendChild(style);

  /**
   * Theme override of the ajax progress indicator.
   * Utilizes Arizona Bootstrap spinner classes.
   *
   * @return {string}
   *   The HTML markup for the throbber.
   */
  Drupal.theme.ajaxProgressIndicatorFullscreen = () => `
    <div class="${spinnerPositionClass}">
      <div class="spinner-border text-info" style="width: ${SPINNER_SIZE}; height: ${SPINNER_SIZE};" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>`;
})(Drupal);
