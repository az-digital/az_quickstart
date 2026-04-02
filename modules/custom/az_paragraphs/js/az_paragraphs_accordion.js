/**
 * @file
 * Accordion paragraph behavior form interactions.
 *
 * Handles accordion toggles for Expand all/Collapse all buttons.
 */

/* global arizonaBootstrap */

((Drupal, once) => {
  // Attach once behavior for Expand all buttons.
  Drupal.behaviors.azAccordionExpandAll = {
    attach: (context) => {
      if (context !== document) {
        return;
      }

      // Set the accordion item 'show' class with Bootstrap collapse animation.
      function setAccordionItemStatus(el, isOpen) {
        if (!el?.classList) return;

        // Get or create Bootstrap collapse instance.
        const bsCollapse =
          arizonaBootstrap.Collapse.getInstance(el) ||
          new arizonaBootstrap.Collapse(el, { toggle: false });

        if (isOpen) {
          bsCollapse.show();
        } else {
          bsCollapse.hide();
        }
      }

      // Update the toggle button text based on accordion state.
      function updateToggleButtonText(accordionItems, toggleBtn) {
        if (!accordionItems?.length || !toggleBtn) return;

        // Count how many items have the 'show' class.
        const expandedCount = [...accordionItems].filter((el) =>
          el.classList.contains('show'),
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
        const toggles = context.querySelectorAll('[id^="accordion-toggle"]');

        toggles.forEach((toggle) => {
          if (toggle._azAccordionBound) {
            return;
          }
          toggle._azAccordionBound = true;

          // Use the button's data-target attribute to find the exact accordion
          // container. data-target should be like '#accordion-123'.
          const targetSelector = toggle.dataset.target;

          const accordionEl =
            document.querySelector(targetSelector) ||
            toggle.closest('.accordion-wrapper')?.querySelector('.accordion') ||
            document.querySelector('.accordion');

          if (!accordionEl) {
            return;
          }

          const accordionItems = accordionEl.querySelectorAll('.collapse');

          // Add a listener for the main toggle button click.
          toggle.addEventListener('click', (e) => {
            e.preventDefault();

            // Toggle all collapse items within this accordion container.
            if (e.currentTarget.textContent === 'Collapse all') {
              // Collapse all accordion items.
              const setAccordionItem = (el) =>
                setAccordionItemStatus(el, false);
              accordionItems.forEach(setAccordionItem);
            } else {
              // Expand all accordion items.
              const setAccordionItem = (el) => setAccordionItemStatus(el, true);
              accordionItems.forEach(setAccordionItem);
            }

            updateToggleButtonText(accordionItems, toggle);
          });

          // Add listeners for individual accordion items.
          accordionItems.forEach((item) => {
            if (item._azAccordionEventBound) return;
            item._azAccordionEventBound = true;

            item.addEventListener('shown.bs.collapse', () => {
              updateToggleButtonText(accordionItems, toggle);
            });

            item.addEventListener('hidden.bs.collapse', () => {
              updateToggleButtonText(accordionItems, toggle);
            });
          });
        });
      }

      once('azAccordionToggleButtons', 'body').forEach(
        addAccordionToggleListeners,
      );
    },
  };
})(Drupal, once);
