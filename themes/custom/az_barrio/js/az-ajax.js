/**
 * @file
 * Ajax theme overrides for AZ Barrio.
 */

((Drupal) => {
  /**
   * Theme override of the ajax progress indicator to use Arizona Bootstrap
   * classes and place in center of screen with inline style.
   *
   * See: https://digital.arizona.edu/arizona-bootstrap/docs/2.0/components/spinners
   *
   * @return {string}
   *   The HTML markup for the throbber.
   */
  Drupal.theme.ajaxProgressIndicatorFullscreen = () =>
    '<div class="position-fixed" style="z-index:1261;top:48.5%;left:49%;"><div class="spinner-border text-info" style="width: 3rem; height: 3rem;" role="status"><span class="sr-only">Loading...</span></div></div>';
})(Drupal);
