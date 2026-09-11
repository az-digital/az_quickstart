/**
 * @file
 * active-filter-count.es6.js
 *
 * This file contains the JavaScript needed to count active filters in
 * exposed filter forms.
 */
((drupalSettings, Drupal, once) => {
  Drupal.behaviors.azFinderFilterCount = {
    attach(context, settings) {
      const filterContainers = once(
        'az-filter-count',
        '[data-az-better-exposed-filters]',
        context,
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

          // Get calendar filter inputs within the calendar widget
          const calendarInputs = container.querySelectorAll(
            '.views-widget-az-calendar-filter input[type="text"]',
          );
          let hasCalendarFilter = false;

          calendarInputs.forEach((inputField) => {
            const val = inputField.value.trim();
            // Only count if not empty and not a default relative value
            if (val.length >= 1 && val !== 'today' && val !== '+3 years') {
              hasCalendarFilter = true;
            }
          });

          // If calendar has active filters, count it as 1 (not 2)
          if (hasCalendarFilter) {
            count += 1;
          }

          // Count other text inputs (excluding calendar ones)
          textInputFields.forEach((inputField) => {
            if (
              !inputField.closest('.views-widget-az-calendar-filter') &&
              inputField.value.trim().length >= 1
            ) {
              count += 1;
            }
          });

          return count;
        };

        const updateActiveFilterDisplay = () => {
          const activeFilterCount = calculateActiveFilterCount();
          // See if the badge is already present.
          let badge = filterCountDisplay.querySelector('.badge');
          if (!badge) {
            badge = document.createElement('span');
            badge.classList.add('badge', 'text-bg-light');
            badge.textContent = '0';
          }
          if (activeFilterCount > 0) {
            badge.classList.remove('visually-hidden');
            badge.classList.remove('position-absolute');
          } else {
            badge.classList.add('visually-hidden');
            badge.classList.add('position-absolute');
          }
          let srText = badge.querySelector('.visually-hidden');
          if (!srText) {
            // Create the screen reader-only text.
            srText = document.createElement('span');
            srText.classList.add('visually-hidden');
            srText.textContent = `Active filters: `;
            badge.appendChild(srText);
          }
          // Set the text value.
          badge.firstChild.textContent = `${activeFilterCount}`;
          // Replace the children of the filter count display with the badge.
          filterCountDisplay.replaceChildren(badge);

          // Handle the reset button visibility.
          const resetButton = container.querySelector(
            '.js-active-filters-reset',
          );

          if (resetButton) {
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
        textInputFields.forEach((inputField) =>
          inputField.addEventListener('change', updateActiveFilterDisplay, {
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

        // Initial update call.
        updateActiveFilterDisplay();
      });
    },
  };
})(drupalSettings, Drupal, once);
