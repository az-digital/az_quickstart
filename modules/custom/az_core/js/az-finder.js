/**
 * @file
 * Custom JavaScript for the AZ Finder module.
 */

(function (drupalSettings) {
  document.addEventListener('DOMContentLoaded', function () {
    const clearAllButton = document.querySelector('.js-finder-clear-all');
    const filterContainer = document.querySelector('.az-bef-vertical');
    const filterCountDisplay = clearAllButton.querySelector(
      '.js-finder-filter-count',
    );
    const searchInputField = filterContainer.querySelector(
      'input[name="search"]',
    );

    // Access Drupal setting for minimum search input length
    const minSearchLength = drupalSettings.azFinder.minSearchLength || 3;

    // Update display of total active filters
    function updateActiveFilterDisplay() {
      const activeCheckboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]:checked',
      );
      let activeFilterCount = activeCheckboxes.length;

      // Include search input as an active filter if it meets minimum length
      if (searchInputField.value.trim().length >= minSearchLength) {
        activeFilterCount += 1;
      }

      filterCountDisplay.textContent = `(${activeFilterCount})`;
      if (
        activeFilterCount > 0 &&
        clearAllButton.classList.contains('d-none')
      ) {
        clearAllButton.classList.remove('d-none');
      } else if (
        activeFilterCount === 0 &&
        !clearAllButton.classList.contains('d-none')
      ) {
        clearAllButton.classList.add('d-none');
      }
    }

    // Deselect all checkboxes
    function deselectAllCheckboxes(event) {
      event.preventDefault();
      const checkboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]',
      );
      checkboxes.forEach((checkbox) => (checkbox.checked = false));
      updateActiveFilterDisplay();
    }

    // Clear search input field
    function clearSearchInput(event) {
      searchInputField.value = '';
    }

    // Reset all filters to their default state
    function resetAllFilters(event) {
      clearSearchInput(event);
      deselectAllCheckboxes(event);
      filterContainer.querySelector('.js-form-submit').click();
      event.preventDefault();
    }

    clearAllButton.addEventListener('click', resetAllFilters);

    // Handle changes in checkboxes and search input
    filterContainer.addEventListener('change', function (event) {
      if (
        event.target &&
        (event.target.type === 'checkbox' || event.target === searchInputField)
      ) {
        updateActiveFilterDisplay();
      }
    });

    // Update filter count based on search input changes
    searchInputField.addEventListener('input', updateActiveFilterDisplay);

    // Prevent form submission with Enter key on search input field
    searchInputField.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        // Optionally trigger search or other actions here
      }
    });

    // Initialize the display of active filters
    updateActiveFilterDisplay();
  });
})(drupalSettings);
