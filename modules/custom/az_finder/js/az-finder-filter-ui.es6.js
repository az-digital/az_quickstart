/**
 * @file
 * Custom JavaScript for the AZ Finder module.
 */

(function (drupalSettings) {
  document.addEventListener('DOMContentLoaded', () => {
    const filterContainer = document.querySelector('.az-bef-vertical');
    if (!filterContainer) return;

    const clearAllButton = filterContainer.querySelector(
      '.js-finder-clear-all',
    );
    const filterCountDisplay = clearAllButton.querySelector(
      '.js-finder-filter-count',
    );
    const searchInputField = filterContainer.querySelector(
      'input[name="search"]',
    );
    const svgLevel0ReplaceButtons = filterContainer.querySelectorAll(
      '.js-svg-replace-level-0',
    );
    const svgLevel1ReplaceButtons = filterContainer.querySelectorAll(
      '.js-svg-replace-level-1',
    );
    const accordionButtons = filterContainer.querySelectorAll('.collapser');
    // Access Drupal setting for minimum search input length
    const minSearchLength = drupalSettings.azFinder.minSearchLength || 3;
    // Access Drupal setting for icons
    const icons = drupalSettings.azFinder.icons;

    // Replace SVGs with expand/collapse icons
    function toggleSVG(container, level) {
      const isExpanded = container.getAttribute('aria-expanded') === 'true';
      let newSVGMarkup;
      if (level === 0) {
        newSVGMarkup = isExpanded
          ? icons.level_0_expand
          : icons.level_0_collapse;
      } else {
        newSVGMarkup = isExpanded
          ? icons.level_1_expand
          : icons.level_1_collapse;
      }
      container.querySelector('svg').outerHTML = newSVGMarkup;
    }

    svgLevel0ReplaceButtons.forEach((button) => {
      button.addEventListener('click', () => toggleSVG(button, 0));
    });

    svgLevel1ReplaceButtons.forEach((button) => {
      button.addEventListener('click', () => toggleSVG(button, 1));
    });

    // Update display of total active filters
    const updateActiveFilterDisplay = () => {
      const activeCheckboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]:checked',
      );
      let activeFilterCount = activeCheckboxes.length;

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
    function deselectAllCheckboxes(event, filterContainer) {
      event.preventDefault();
      const checkboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]',
      );
      checkboxes.forEach((checkbox) => (checkbox.checked = false));
      updateActiveFilterDisplay();
    }

    // Clear search input field
    function clearSearchInput(event, searchInputField) {
      searchInputField.value = '';
    }

    // Reset all filters to their default state
    function resetAllFilters(event, searchInputField, filterContainer) {
      clearSearchInput(event, searchInputField);
      deselectAllCheckboxes(event, filterContainer);
      filterContainer.querySelector('.js-form-submit').click();
      event.preventDefault();
    }

    // Handle changes in checkboxes and search input
    filterContainer.addEventListener('change', function (event) {
      if (
        event.target &&
        (event.target.type === 'checkbox' || event.target === searchInputField)
      ) {
        updateActiveFilterDisplay();
      }
    });

    clearAllButton.addEventListener('click', function (event) {
      resetAllFilters(event, searchInputField, filterContainer);
    });
    // Update filter count based on search input changes
    searchInputField.addEventListener('input', updateActiveFilterDisplay);
    // Add event listeners for keyboard navigation.
    // This allows the user to navigate the accordion headers with the arrow
    // keys and toggle them with Enter or Space.
    accordionButtons.forEach((button, index) => {
      button.addEventListener('keydown', (event) => {
        // Handle Enter or Space press
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault(); // Prevent the default action to stop scrolling when space is pressed
          button.click(); // Trigger the Bootstrap collapse toggle
        }
      });
    });

    // Initialize the display of active filters
    updateActiveFilterDisplay();
  });
})(drupalSettings);
