/**
 * @file
 * active-filter-reset.es6.js
 *
 * This file contains the JavaScript needed to reset active filters in
 * exposed filter forms.
 */
((Drupal) => {
  Drupal.behaviors.azFinderActiveFilterReset = {
    attach(context) {
      const clickHandler = (selector, action) => {
        const element = selector.querySelector('.js-form-submit');
        if (element && action) action(element);
      };

      const resetFilters = (container) => {
        const checkboxes = container.querySelectorAll('input[type="checkbox"]');
        const textFields = container.querySelectorAll('input[type="text"]');

        checkboxes.forEach((checkbox) => {
          checkbox.checked = false;
        });
        textFields.forEach((textField) => {
          textField.value = '';
        });
        clickHandler(container, (element) => element.click());

        const event = new CustomEvent('az-finder-filter-reset', {
          bubbles: true,
          detail: { message: 'Filters have been reset.' },
        });
        container.dispatchEvent(event);
      };

      context
        .querySelectorAll('[data-az-better-exposed-filters]')
        .forEach((container) => {
          const resetButton = container.querySelector(
            '.js-active-filters-reset',
          );
          if (resetButton) {
            resetButton.addEventListener('click', (event) => {
              event.preventDefault();
              resetFilters(container);
            });
          }
        });
    },
  };
})(Drupal);
