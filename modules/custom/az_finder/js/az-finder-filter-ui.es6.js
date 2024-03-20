/**
 * @file
 * Custom JavaScript for the AZ Finder module.
 */

((drupalSettings) => {
  document.addEventListener('DOMContentLoaded', () => {
    const filterContainer = document.querySelector('.az-bef-vertical');
    if (!filterContainer) return;

    const clearAllButton = filterContainer.querySelector(
      '.js-finder-clear-all',
    );
    const filterCountDisplay = clearAllButton.querySelector(
      '.js-finder-filter-count',
    );
    const searchInput = filterContainer.querySelector('input[name="search"]');
    const svgLevel0ReplaceButtons = filterContainer.querySelectorAll(
      '.js-svg-replace-level-0',
    );
    const svgLevel1ReplaceButtons = filterContainer.querySelectorAll(
      '.js-svg-replace-level-1',
    );
    const accordionButtons = filterContainer.querySelectorAll('.collapser');
    const { minSearchLength = 1, icons } = drupalSettings.azFinder;

    // Replace nested ternary with a function to avoid nesting
    function getNewSVGMarkup(isExpanded, level) {
      if (level === 0) {
        return isExpanded ? icons.level_0_expand : icons.level_0_collapse;
      }
      return isExpanded ? icons.level_1_expand : icons.level_1_collapse;
    }

    // Replace SVGs with expand/collapse icons
    function toggleSVG(button, level) {
      const isExpanded = button.getAttribute('aria-expanded') === 'true';
      const newSVGMarkup = getNewSVGMarkup(isExpanded, level);
      button.querySelector('svg').outerHTML = newSVGMarkup;
    }

    svgLevel0ReplaceButtons.forEach((button) => {
      button.addEventListener('click', () => toggleSVG(button, 0));
    });

    svgLevel1ReplaceButtons.forEach((button) => {
      button.addEventListener('click', () => toggleSVG(button, 1));
    });

    // Update display of total active filters
    function updateActiveFilterDisplay() {
      const activeCheckboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]:checked',
      );
      let activeFilterCount = activeCheckboxes.length;

      if (searchInput.value.trim().length >= minSearchLength) {
        activeFilterCount += 1;
      }

      filterCountDisplay.textContent = `(${activeFilterCount})`;
      clearAllButton.classList.toggle('d-none', activeFilterCount === 0);
    }

    // Adjust arrow function to not return an assignment directly
    function deselectAllCheckboxes() {
      const checkboxes = filterContainer.querySelectorAll(
        'input[type="checkbox"]',
      );
      checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
      });
      updateActiveFilterDisplay();
    }

    // Reset all filters to their default state
    function resetAllFilters() {
      searchInput.value = '';
      deselectAllCheckboxes();
      filterContainer.querySelector('.js-form-submit').click();
    }

    clearAllButton.addEventListener('click', (event) => {
      event.preventDefault();
      resetAllFilters();
    });

    filterContainer.addEventListener('change', (event) => {
      if (event.target.type === 'checkbox' || event.target === searchInput) {
        updateActiveFilterDisplay();
      }
    });

    searchInput.addEventListener('input', updateActiveFilterDisplay);

    // Define a function for handling keydown events on accordion buttons
    function handleAccordionButtonKeydown(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        event.currentTarget.click();
      }
    }

    accordionButtons.forEach((button) => {
      button.addEventListener('keydown', handleAccordionButtonKeydown);
    });

    updateActiveFilterDisplay();
  });
})(drupalSettings);
