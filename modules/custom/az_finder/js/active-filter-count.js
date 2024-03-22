/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (drupalSettings) {
  document.addEventListener('DOMContentLoaded', function () {
    var filterContainers = document.querySelectorAll('[data-az-better-exposed-filters]');
    filterContainers.forEach(function (container) {
      var filterCountDisplay = container.querySelector('.js-active-filter-count');
      var resetButton = container.querySelector('.js-active-filters-reset');
      var textInputFields = container.querySelectorAll('input[type="text"], input[type="search"]');
      var minSearchLength = drupalSettings.azFinder.minSearchLength || 1;
      var alwaysDisplayResetButton = drupalSettings.azFinder.alwaysDisplayResetButton || false;
      var updateActiveFilterDisplay = function updateActiveFilterDisplay() {
        var activeFilterCount = container.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked').length;
        textInputFields.forEach(function (inputField) {
          if (inputField.value.trim().length >= minSearchLength) {
            activeFilterCount += 1;
          }
        });
        filterCountDisplay.textContent = "(".concat(activeFilterCount, ")");
        if (alwaysDisplayResetButton || activeFilterCount > 0) {
          resetButton.classList.remove('d-none');
        } else {
          resetButton.classList.add('d-none');
        }
      };
      textInputFields.forEach(function (inputField) {
        inputField.addEventListener('input', updateActiveFilterDisplay);
      });
      container.addEventListener('change', function (event) {
        if (['checkbox', 'radio'].includes(event.target.type)) {
          updateActiveFilterDisplay();
        }
      });
      updateActiveFilterDisplay();
    });
  });
})(drupalSettings);