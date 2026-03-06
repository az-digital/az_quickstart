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
      if (context !== document) {
        return;
      }
      // Set the accordion item 'show' class.
      function setAccordionItemStatus(el, status) {
        if (status === 'true') {
          el.classList.add('show');
        } else {
          el.classList.remove('show');
        }
      }

      // Set the accordion button aria-expanded attribute and 'collapsed' class.
      function setAccordionBtnStatus(el, status) {
        el.setAttribute('aria-expanded', status);

        if (status === 'true') {
          el.classList.remove('collapsed');
        } else {
          el.classList.add('collapsed');
        }
      }

      // Update the toggle button text based on accordion state.
      function updateToggleButtonText(accordionEl, toggleBtn) {
        if (!accordionEl || !toggleBtn) {
          return;
        }

        const accordionItems = accordionEl.querySelectorAll('.collapse');
        if (accordionItems.length === 0) {
          return;
        }

        // Count how many items have the 'show' class.
        const expandedCount = Array.from(accordionItems).filter((item) =>
          item.classList.contains('show'),
        ).length;

        // Update button text based on state.
        if (expandedCount === 0) {
          toggleBtn.textContent = 'Expand all';
        } else if (expandedCount === accordionItems.length) {
          toggleBtn.textContent = 'Collapse all';
        }
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

          const accordionButtons =
            accordionEl.querySelectorAll('[aria-expanded]');
          const accordionItems = accordionEl.querySelectorAll('.collapse');

          // Add a listener for the main toggle button click.
          toggle.addEventListener('click', (e) => {
            e.preventDefault();

            // Toggle all collapse items within this accordion container.
            if (e.currentTarget.textContent === 'Collapse all') {
              // Collapse all accordion items.
              const setAccordionItem = (el) =>
                setAccordionItemStatus(el, 'false');
              [...accordionItems].map(setAccordionItem);

              // Set aria-expanded attribute to false.
              const setAccordionBtn = (el) =>
                setAccordionBtnStatus(el, 'false');
              [...accordionButtons].map(setAccordionBtn);

              // Update toggle text to 'expand all'.
              e.currentTarget.textContent = 'Expand all';
            } else {
              // Expand all accordion items.
              const setAccordionItem = (el) =>
                setAccordionItemStatus(el, 'true');
              [...accordionItems].map(setAccordionItem);

              // Set aria-expanded attribute to true.
              const setAccordionBtn = (el) => setAccordionBtnStatus(el, 'true');
              [...accordionButtons].map(setAccordionBtn);

              // Update toggle text to 'collapse all'.
              e.currentTarget.textContent = 'Collapse all';
            }
          });

          // Add listeners for individual accordion item buttons.
          accordionButtons.forEach((btn) => {
            if (btn._azAccordionItemBound) {
              return;
            }
            btn._azAccordionItemBound = true;

            btn.addEventListener('click', () => {
              // Update button text after a delay to account for Bootstrap DOM updating.
              setTimeout(() => {
                updateToggleButtonText(accordionEl, toggle);
              }, 50);
            });
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
