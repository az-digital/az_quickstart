/**
 * @file
 * Accordion paragraph behavior form interactions.
 *
 * Handles accordion toggles for Expand all/Collapse all buttons.
 */

((Drupal) => {

  // Attach once behavior for Expand all buttons.
  Drupal.behaviors.azAccordionExpandAll = {
    attach: (context) => {
      let toggles = context.querySelectorAll ?
        context.querySelectorAll('[id^="accordion-toggle"]') : [];

      toggles.forEach((toggle) => {
        if (toggle._azAccordionBound) {
          return;
        }
        toggle._azAccordionBound = true;

        toggle.addEventListener('click', (e) => {
          e.preventDefault();

          // Use the button's data-target attribute to find the exact accordion
          // container. data-target should be like '#accordion-123'.
          let targetSelector = toggle.getAttribute('data-target') || toggle.dataset.target;
          let accordionEl = null;

          if (targetSelector) {
            accordionEl = document.querySelector(targetSelector);
          }

          // Fallback: try to find a nearby .accordion element.
          if (!accordionEl) {
            let next = toggle.nextElementSibling;
            while (next) {
              if (next.classList && next.classList.contains('accordion')) {
                accordionEl = next;
                break;
              }
              next = next.nextElementSibling;
            }
          }

          if (!accordionEl) {
            accordionEl = document.querySelector('.accordion');
          }

          if (!accordionEl) {
            return;
          }

          // Toggle all collapse items within this accordion container.
          let collapseItems = accordionEl.querySelectorAll('.collapse');
          const lastState = this.dataset.lastState;

          if (lastState === null || lastState === '0') {
            // Collapse all elements
            collapseItems.forEach((el) => {
              el.classList.remove('show');
            });

            // Update state and text to 'expand all'
            this.dataset.lastState = '1';
            this.textContent = "Expand all";
          } else {
            // Show all elements
            collapseItems.forEach(el => {
              el.classList.add('show');
            });

            // Update state and text to 'collapse all'
            this.dataset.lastState = '0';
            this.textContent = "Collapse all";
          }
        });
      });
    },
  };
})(Drupal);
