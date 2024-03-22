/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (Drupal) {
  var clickHandler = function clickHandler(selector, action) {
    var element = selector.querySelector('.js-form-submit');
    if (element && action) action(element);
  };
  var resetFilters = function resetFilters(container) {
    var checkboxes = container.querySelectorAll('input[type="checkbox"]');
    var textFields = container.querySelectorAll('input[type="text"]');
    checkboxes.forEach(function (checkbox) {
      checkbox.checked = false;
    });
    textFields.forEach(function (textField) {
      textField.value = '';
    });
    clickHandler(container, function (element) {
      return element.click();
    });
    var event = new CustomEvent('az-finder-filter-reset', {
      bubbles: true,
      detail: {
        message: 'Filters have been reset.'
      }
    });
    container.dispatchEvent(event);
  };
  Drupal.behaviors.azFinderActiveFilterReset = {
    attach: function attach(context) {
      context.querySelectorAll('[data-az-better-exposed-filters]').forEach(function (container) {
        var resetButton = container.querySelector('.js-active-filters-reset');
        if (resetButton) {
          resetButton.addEventListener('click', function (event) {
            event.preventDefault();
            resetFilters(container);
          });
        }
      });
    }
  };
})(Drupal);