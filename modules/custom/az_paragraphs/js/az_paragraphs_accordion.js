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
      // Set the 'show' class on the accordion item.
      function setAccordionStatus(el, status) {
        if (status === 'expanded') {
          el.classList.add('show');
        } else {
          el.classList.remove('show');
        }
      }

      // Set aria-expanded attribute to true or false.
      function setAccordionAriaStatus(el, status) {
        el.setAttribute('aria-expanded', status);
      }

      // Add event listeners to the accordion toggle buttons.
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
              accordionEl.querySelectorAll('[aria-expanded]');
            const accordionItems = accordionEl.querySelectorAll('.collapse');

            if (e.currentTarget.textContent === 'Collapse all') {
              // Collapse all accordion items
              const setAccordionItemStatus = (el) =>
                setAccordionStatus(el, 'collapsed');
              [...accordionItems].map(setAccordionItemStatus);

              // Set aria-expanded attribute to false
              const setAccordionBtnAria = (el) =>
                setAccordionAriaStatus(el, 'false');
              [...accordionButtons].map(setAccordionBtnAria);

              // Update toggle text to 'expand all'
              e.currentTarget.textContent = 'Expand all';
            } else {
              // Expand all accordion items
              const setAccordionItemStatus = (el) =>
                setAccordionStatus(el, 'expanded');
              [...accordionItems].map(setAccordionItemStatus);

              // Set aria-expanded attribute to true
              const setAccordionBtnAria = (el) =>
                setAccordionAriaStatus(el, 'true');
              [...accordionButtons].map(setAccordionBtnAria);

              // Update toggle text to 'collapse all'
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
