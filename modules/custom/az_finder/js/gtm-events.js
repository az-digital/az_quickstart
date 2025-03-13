/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (Drupal, once) {
  Drupal.behaviors.azFinderGTMEvents = {
    attach: function attach(context) {
      var triggerGTagEvent = function triggerGTagEvent(event) {
        if (event.target.tagName === 'DIV') {
          var titleElement = event.target.querySelector('.field--name-title');
          gtag('event', 'Finder Event', {
            source_url: event.target.baseURI,
            event_type: 'View content item click',
            target_label: titleElement.innerText,
            target_url: ''
          });
        } else if (event.target.tagName === 'INPUT') {
          gtag('event', 'Finder Event', {
            source_url: event.target.baseURI,
            event_type: 'Checkbox/Radio button filter',
            target_label: event.target.nextSibling.innerText,
            target_url: ''
          });
        }
      };
      var finderExposedFormDivs = once('az-finder-gtm-events', '[data-az-better-exposed-filters]', context);
      var finderViewDisplays = [];
      finderExposedFormDivs.forEach(function (container) {
        finderViewDisplays.push(container.dataset.azViewDisplay);
        var textInputFields = container.querySelectorAll('input[type="text"], input[type="search"]');
        var checkboxesAndRadios = container.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        var addGTMEvents = function addGTMEvents() {
          checkboxesAndRadios.forEach(function (input) {
            return input.addEventListener('click', triggerGTagEvent, {
              passive: true
            });
          });
        };
        addGTMEvents();
      });
      finderViewDisplays.forEach(function (container) {
        var viewDisplay = container.split('-');
        var finderViewContent = once('az-finder-gtm-events', ".view.view-id-".concat(viewDisplay[0], ".view-display-id-").concat(viewDisplay[1], " > .view-content > div"), context);
        finderViewContent.forEach(function (viewItem) {
          viewItem.addEventListener('click', triggerGTagEvent, {
            passive: true
          });
        });
      });
    }
  };
})(Drupal, once);