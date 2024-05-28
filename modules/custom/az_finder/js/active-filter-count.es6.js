/**
 * @file
 * active-filter-count.es6.js
 *
 * This file contains the JavaScript needed to count active filters in
 * exposed filter forms.
 */
((drupalSettings, Drupal) => {
  Drupal.behaviors.azFinderFilterCount = {
    attach(context, settings) {
      const filterContainers = context.querySelectorAll(
        '[data-az-better-exposed-filters]',
      );

      filterContainers.forEach((container) => {
        const filterCountDisplay = container.querySelector(
          '.js-active-filter-count',
        );
        const textInputFields = container.querySelectorAll(
          'input[type="text"], input[type="search"]',
        );
        const checkboxesAndRadios = container.querySelectorAll(
          'input[type="checkbox"], input[type="radio"]',
        );
        const alwaysDisplayResetButton =
          settings.azFinder.alwaysDisplayResetButton || false;

        const calculateActiveFilterCount = () => {
          let count = container.querySelectorAll(
            'input[type="checkbox"]:checked, input[type="radio"]:checked',
          ).length;
          textInputFields.forEach((inputField) => {
            if (inputField.value.trim().length >= 1) {
              count += 1;
            }
          });
          return count;
        };

        const updateActiveFilterDisplay = () => {
          const activeFilterCount = calculateActiveFilterCount();
          filterCountDisplay.textContent = `(${activeFilterCount})`;
          const resetButton = container.querySelector(
            '.js-active-filters-reset',
          );
          if (resetButton) {
            // Ensure resetButton exists before trying to modify it
            if (alwaysDisplayResetButton || activeFilterCount > 0) {
              resetButton.classList.remove('d-none');
            } else {
              resetButton.classList.add('d-none');
            }
          }
        };

        textInputFields.forEach((inputField) =>
          inputField.addEventListener('input', updateActiveFilterDisplay, {
            passive: true,
          }),
        );
        checkboxesAndRadios.forEach((input) =>
          input.addEventListener('change', updateActiveFilterDisplay, {
            passive: true,
          }),
        );
        container.addEventListener(
          'az-finder-filter-reset',
          updateActiveFilterDisplay,
          { passive: true },
        );

        // Initial update call
        updateActiveFilterDisplay();
      });
    },
  };
})(drupalSettings, Drupal);
