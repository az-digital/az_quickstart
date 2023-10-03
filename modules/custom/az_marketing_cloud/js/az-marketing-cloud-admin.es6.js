/**
 * @file
 * Provides click to copy functionality.
 */

((window, document) => {
  function init() {
    const copyLinks = document.querySelectorAll('.js-click2copy a');

    function removeClass(element) {
      setTimeout(() => {
        element.classList.remove('js-click-copy--copied');
      }, 3000);
    }

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
    function _handleClick(event) {
      const baseUrl = window.location.origin;
      if (event.type === 'click') {
        event.preventDefault();
        const href = event.target.getAttribute('href');
        navigator.clipboard.writeText(baseUrl + href);
        event.target.classList.add('js-click-copy--copied');
        removeClass(event.target);
      } else {
        return false;
      }
    }

    copyLinks.forEach((element) => {
      element.addEventListener('click', _handleClick, false);
    });
  }

  if (document.readyState === 'complete') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})(this, this.document);
