/**
 * @file
 * Provides click to copy functionality.
 */

((Drupal, window, document) => {
  /**
   * Handles click events on the click to copy links.
   *
   * @param {Event} event
   *  The event object.
   * @return {boolean}
   * Returns false if the event type is not click.  Otherwise, adds the link to
   * the user's clipboard and adds a class to the link to indicate that it has been
   * copied.
   */
  function handleClick(event) {
    const baseUrl = window.location.origin;
    if (event.type === 'click') {
      event.preventDefault();
      const href = event.srcElement.getAttribute('href');
      navigator.clipboard.writeText(baseUrl + href);
      event.srcElement.classList.add('js-click-copy--copied', 'action-link--icon-checkmark');
    } else {
      return false;
    }
  }

  const copyLinks = document.querySelectorAll(
    '.view-id-az_marketing_cloud.view-display-id-admin .views-field.views-field-view-node-1 a',
  );

  copyLinks.forEach((element) =>
    element.addEventListener('click', handleClick, false),
  );
})(Drupal, this, this.document);
