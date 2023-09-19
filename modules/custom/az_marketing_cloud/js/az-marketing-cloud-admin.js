/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
function addClass() {
  let elem = document.getElementsByClassName("dropbutton");
  elem.classList.add("dropbutton--small");
}

(function (Drupal, window, document) {
  function handleClick(event) {
    var baseUrl = window.location.origin;
    if (event.type === 'click') {
      event.preventDefault();
      var href = event.srcElement.getAttribute('href');
      navigator.clipboard.writeText(baseUrl + href);
      event.srcElement.classList.add('js-click-copy--copied', 'action-link--icon-checkmark');
    } else {
      return false;
    }
  }
  var copyLinks = document.querySelectorAll('.view-id-az_marketing_cloud.view-display-id-admin td.dropbutton--small a');
  copyLinks.forEach(function (element) {
    return element.addEventListener('click', handleClick, false);
  });
})(Drupal, this, this.document);
