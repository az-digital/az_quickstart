/**
 * @file
 * active-filter-count.es6.js
 *
 * This file contains the JavaScript needed to count active filters in
 * exposed filter forms.
 */
((Drupal) => {
  Drupal.behaviors.azFinderTaxonomyIndexTidWidget = {
    attach(context, settings) {
      const filterContainers = context.querySelectorAll(
        '[data-az-better-exposed-filters]',
      );

      function setupSVGButtonListeners(container) {
        const svgLevel0ReplaceButtons = container.querySelectorAll(
          '.js-svg-replace-level-0',
        );
        const svgLevel1ReplaceButtons = container.querySelectorAll(
          '.js-svg-replace-level-1',
        );
        const { icons } = settings.azFinder;

        function getNewSVGMarkup(isExpanded, level) {
          if (level === 0) {
            return isExpanded ? icons.level_0_collapse : icons.level_0_expand;
          }
          return isExpanded ? icons.level_1_collapse : icons.level_1_expand;
        }

        function toggleSVG(event) {
          const button = event.currentTarget;
          const level = button.classList.contains('js-svg-replace-level-0')
            ? 0
            : 1;
          const isExpanded = button.getAttribute('aria-expanded') === 'true';
          const newSVGMarkup = getNewSVGMarkup(isExpanded, level);

          button.querySelector('svg').outerHTML = newSVGMarkup;
        }

        svgLevel0ReplaceButtons.forEach((button) => {
          button.addEventListener('click', toggleSVG);
        });

        svgLevel1ReplaceButtons.forEach((button) => {
          button.addEventListener('click', toggleSVG);
        });
      }

      filterContainers.forEach((container) => {
        setupSVGButtonListeners(container, settings);
      });
    },
  };
})(Drupal);
