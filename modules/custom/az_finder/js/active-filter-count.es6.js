((drupalSettings) => {
  document.addEventListener('DOMContentLoaded', () => {
    const filterContainers = document.querySelectorAll(
      '[data-az-better-exposed-filters]',
    );
    filterContainers.forEach((container) => {
      const filterCountDisplay = container.querySelector(
        '.js-active-filter-count',
      );
      const resetButton = container.querySelector('.js-active-filters-reset');
      const textInputFields = container.querySelectorAll(
        'input[type="text"], input[type="search"]',
      );
      const minSearchLength = drupalSettings.azFinder.minSearchLength || 1;
      const alwaysDisplayResetButton =
        drupalSettings.azFinder.alwaysDisplayResetButton || false;

      const updateActiveFilterDisplay = () => {
        let activeFilterCount = container.querySelectorAll(
          'input[type="checkbox"]:checked, input[type="radio"]:checked',
        ).length;

        textInputFields.forEach((inputField) => {
          if (inputField.value.trim().length >= minSearchLength) {
            activeFilterCount += 1;
          }
        });

        filterCountDisplay.textContent = `(${activeFilterCount})`;

        if (alwaysDisplayResetButton || activeFilterCount > 0) {
          resetButton.classList.remove('d-none');
        } else {
          resetButton.classList.add('d-none');
        }
      };

      textInputFields.forEach((inputField) => {
        inputField.addEventListener('input', updateActiveFilterDisplay);
      });

      container.addEventListener('change', (event) => {
        if (['checkbox', 'radio'].includes(event.target.type)) {
          updateActiveFilterDisplay();
        }
      });

      updateActiveFilterDisplay();
    });
  });
})(drupalSettings);
