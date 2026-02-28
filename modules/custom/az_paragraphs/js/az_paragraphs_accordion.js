/**
 * @file
 * Accordion paragraph behavior form interactions.
 *
 * Handles accordion toggles for Expand all/Collapse all buttons.
 */

((Drupal, once) => {
  // Attach once behavior for Expand all buttons.
  Drupal.behaviors.azAccordionExpandAll = {
    attach: (context) => {
      function collapseAccordionItem(el) {
        el.classList.remove('show');
      }

      function expandAccordionItem(el) {
        el.classList.add('show');
      }

      function addAccordionToggleListeners() {
        const toggles = context.querySelectorAll
          ? context.querySelectorAll('[id^="accordion-toggle"]')
          : [];

        toggles.forEach((toggle) => {
          if (toggle._azAccordionBound) {
            return;
          }
          toggle._azAccordionBound = true;

          toggle.addEventListener('click', (e) => {
            e.preventDefault();

            // Use the button's data-target attribute to find the exact accordion
            // container. data-target should be like '#accordion-123'.
            const targetSelector =
              toggle.getAttribute('data-target') || toggle.dataset.target;

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
            const accordionButtons =
              accordionEl.querySelectorAll('.collapse');

            if (e.currentTarget.textContent === 'Collapse all') {
              // Collapse all accordion items
              [...accordionButtons].map(collapseAccordionItem);

              // Update text to 'expand all'
              e.currentTarget.textContent = 'Expand all';
            } else {
              // Expand all elements
              [...accordionButtons].map(expandAccordionItem);

              // Update text to 'collapse all'
              e.currentTarget.textContent = 'Collapse all';
            }
          });
        });
      }

      once('azAccordionToggleButtons', 'body').forEach(
        addAccordionToggleListeners,
        context,
      );
    },
  };
})(Drupal, once);
