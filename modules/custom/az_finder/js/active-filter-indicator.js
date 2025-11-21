/**
 * @file
 * active-filter-indicator.js
 *
 * This file contains the JavaScript needed to display the number of active
 * filters in the accordion headings of the AZ Finder based on configured
 * levels.
 */
((Drupal, drupalSettings) => {
  Drupal.behaviors.azFinderAccordionIndicators = {
    attach(context) {
      // Get the configured levels from drupalSettings.
      const levels = drupalSettings?.azFinder?.activeFilterIndicatorLevels;

      // If levels is not set, exit early.
      if (levels === undefined || levels === null) {
        return;
      }

      // Determine which levels to target based on configuration.
      // levels = 0: all levels (.level-0, .level-1, .level-2, etc.)
      // levels = 1: level-0 and level-1 (first 2 levels)
      // levels = 2: level-0, level-1, and level-2 (first 3 levels)
      // etc.
      let selector;
      if (levels === 0) {
        // Target all levels.
        selector = 'a.collapser[class*="level-"]';
      } else {
        // Build selector for specific levels (0 through levels).
        const levelSelectors = [];
        for (let i = 0; i <= levels; i++) {
          levelSelectors.push(`a.collapser.level-${i}`);
        }
        selector = levelSelectors.join(', ');
      }

      const toggles = once('az-filter-indicator', selector, context);

      toggles.forEach((toggle) => {
        const h3 = toggle.querySelector('h3, h4, h5, h6');
        const { collapseId } = toggle.dataset;
        const checkboxList = context.querySelector(`#${collapseId}`);

        if (!h3 || !checkboxList) return;

        const updateIndicator = () => {
          // Count all checked checkboxes in this section and all nested sections
          const inputs = checkboxList.querySelectorAll(
            'input[type="checkbox"]',
          );
          const count = Array.from(inputs).filter(
            (input) => input.checked,
          ).length;

          let badge = h3.querySelector('.js-az-finder-indicator');
          if (count > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className =
                'js-az-finder-indicator badge bg-azurite mx-2 lh-az-normal';
              badge.textContent = count;
              // Find the text node
              const textNode = Array.from(h3.childNodes).find(
                (node) =>
                  node.nodeType === Node.TEXT_NODE && node.textContent.trim(),
              );
              if (textNode) {
                // For flex-row-reverse, insert BEFORE text so it appears after visually
                // For normal flex, insert AFTER text
                if (h3.classList.contains('flex-row-reverse')) {
                  textNode.before(badge);
                } else {
                  textNode.after(badge);
                }
              } else {
                h3.appendChild(badge);
              }
            } else {
              badge.textContent = count;
            }
          } else if (badge) {
            badge.remove();
          }
        };

        // Get all checkboxes in this section and nested sections
        const inputs = checkboxList.querySelectorAll('input[type="checkbox"]');
        inputs.forEach((input) =>
          input.addEventListener('change', updateIndicator, {
            passive: true,
          }),
        );
        updateIndicator();
        document
          .querySelectorAll('[data-az-better-exposed-filters]')
          .forEach((container) => {
            container.addEventListener('az-finder-filter-reset', () => {
              updateIndicator();
            });
          });
      });
    },
  };
})(Drupal, drupalSettings);
