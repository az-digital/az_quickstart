/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function (drupalSettings, Drupal) {
  Drupal.behaviors.azFinderFilterCount = {
    attach: function attach(context, settings) {
      var filterContainers = context.querySelectorAll('[data-az-better-exposed-filters]');
      filterContainers.forEach(function (container) {
        var filterCountDisplay = container.querySelector('.js-active-filter-count');
        var srMessage = container.querySelector('.js-active-filter-count-sr');
        var textInputFields = container.querySelectorAll('input[type="text"], input[type="search"]');
        var checkboxesAndRadios = container.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        var alwaysDisplayResetButton = settings.azFinder.alwaysDisplayResetButton || false;
        var calculateActiveFilterCount = function calculateActiveFilterCount() {
          var count = container.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked').length;
          textInputFields.forEach(function (inputField) {
            if (inputField.value.trim().length >= 1) {
              count += 1;
            }
          });
          return count;
        };
        var updateActiveFilterDisplay = function updateActiveFilterDisplay() {
          var activeFilterCount = calculateActiveFilterCount();
          var badge = document.createElement('span');
          badge.classList.add('badge', 'badge-light');
          if (activeFilterCount > 0) {
            badge.classList.remove('d-none');
          } else {
            badge.classList.add('d-none');
          }
          badge.textContent = "".concat(activeFilterCount);
          filterCountDisplay.replaceChildren(badge);
          var resetButton = container.querySelector('.js-active-filters-reset');
          if (resetButton) {
            if (alwaysDisplayResetButton || activeFilterCount > 0) {
              resetButton.classList.remove('d-none');
            } else {
              resetButton.classList.add('d-none');
            }
          }
          if (srMessage) {
            srMessage.textContent = activeFilterCount === 0 ? 'No active filters' : "".concat(activeFilterCount, " active filters applied");
          }
        };
        textInputFields.forEach(function (inputField) {
          return inputField.addEventListener('input', updateActiveFilterDisplay, {
            passive: true
          });
        });
        checkboxesAndRadios.forEach(function (input) {
          return input.addEventListener('change', updateActiveFilterDisplay, {
            passive: true
          });
        });
        container.addEventListener('az-finder-filter-reset', updateActiveFilterDisplay, {
          passive: true
        });
        updateActiveFilterDisplay();
      });
    }
  };
})(drupalSettings, Drupal);
