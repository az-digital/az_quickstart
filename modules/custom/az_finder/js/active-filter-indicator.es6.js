/**
 * @file
 * active-filter-indicator.es6.js
 *
 * This file contains the JavaScript needed to display the number of active
 * filters in the accordion headings of the AZ Finder.
 */
((Drupal) => {
  Drupal.behaviors.azFinderAccordionIndicators = {
    attach(context) {
      const toggles = once('az-filter-indicator', '.collapser.level-0', context);

      toggles.forEach((toggle) => {
        const h3 = toggle.querySelector('h3');
        const collapseId = toggle.dataset.collapseId;
        const checkboxList = context.querySelector(`#${collapseId}`);

        if (!h3 || !checkboxList) return;

        const inputs = checkboxList.querySelectorAll('input[type="checkbox"]');

        const updateIndicator = () => {
          const count = Array.from(inputs).filter((input) => input.checked).length;
          let badge = h3.querySelector('.js-az-finder-indicator');
          const svg = h3.querySelector('svg');

          if (count > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'js-az-finder-indicator badge bg-azurite mx-2 small';
              badge.textContent = count;

            } else {
              badge.textContent = count;
            }
            if (svg && svg.parentNode === h3) {
              h3.insertBefore(badge, svg);
            }
          } else if (badge) {
            badge.remove();
          }
        };

        inputs.forEach((input) =>
          input.addEventListener('change', updateIndicator, { passive: true })
        );

        updateIndicator();
      });
    },
  };
})(Drupal);
