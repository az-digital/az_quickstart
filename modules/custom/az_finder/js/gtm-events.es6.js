/**
 * @file
 * gtm-events.es6.js
 *
 * Add GTM events to the Finder search box, checkboxes, and view content.
 */
((Drupal, once) => {
  Drupal.behaviors.azFinderGTMEvents = {
    attach(context) {
      const triggerGTagEvent = (event) => {
        /* eslint-disable no-undef */
        if (event.target.tagName === 'DIV') {
          const titleElement = event.target.querySelector('.field--name-title');
          gtag('event', 'Finder Event', {
            source_url: event.target.baseURI,
            event_type: 'View content item click',
            target_label: titleElement.innerText,
            target_url: '',
          });
        } else if (event.target.tagName === 'INPUT') {
          gtag('event', 'Finder Event', {
            source_url: event.target.baseURI,
            event_type: 'Checkbox/Radio button filter',
            target_label: event.target.nextSibling.innerText,
            target_url: '',
          });
        }
        /* eslint-enable no-undef */
      };

      const finderExposedFormDivs = once(
        'az-finder-gtm-events',
        '[data-az-better-exposed-filters]',
        context,
      );
      const finderViewDisplays = [];

      finderExposedFormDivs.forEach((container) => {
        finderViewDisplays.push(container.dataset.azViewDisplay);
        const textInputFields = container.querySelectorAll(
          'input[type="text"], input[type="search"]',
        );
        const checkboxesAndRadios = container.querySelectorAll(
          'input[type="checkbox"], input[type="radio"]',
        );

        const addGTMEvents = () => {
          /*
          textInputFields.forEach((inputField) =>
            inputField.addEventListener('input', updateActiveFilterDisplay, {
                passive: true,
            }),
          );
          */
          checkboxesAndRadios.forEach((input) =>
            input.addEventListener('click', triggerGTagEvent, {
              passive: true,
            }),
          );
        };

        // Initial update call.
        addGTMEvents();
      });

      // @todo Add listeners each time view content is updated.
      finderViewDisplays.forEach((container) => {
        const viewDisplay = container.split('-');
        const finderViewContent = once(
          'az-finder-gtm-events',
          `.view.view-id-${viewDisplay[0]}.view-display-id-${viewDisplay[1]} > .view-content > div`,
          context,
        );
        finderViewContent.forEach((viewItem) => {
          viewItem.addEventListener('click', triggerGTagEvent, {
            passive: true,
          });
        });
      });
    },
  };
})(Drupal, once);
