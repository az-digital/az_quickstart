/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (window, document) {
  function init() {
    var copyLinks = document.querySelectorAll('.js-click2copy a');
    function _handleClick(event) {
      var baseUrl = window.location.origin;
      if (event.type === 'click') {
        event.preventDefault();
        var href = event.target.getAttribute('href');
        navigator.clipboard.writeText(baseUrl + href);
        event.target.classList.add('js-click-copy--copied', 'action-link--icon-checkmark');
        removeClass(event.srcElement);
      } else {
        return false;
      }
    }
    function removeClass(element) {
      setTimeout(function () {
        element.classList.remove('js-click-copy--copied', 'action-link--icon-checkmark');
      }, 3000);
    }
    copyLinks.forEach(function (element) {
      element.addEventListener('click', _handleClick, false);
    });
  }
  if (document.readyState === "complete") {
    init();
  } else {
    document.addEventListener("DOMContentLoaded", init);
  }
})(this, this.document);
