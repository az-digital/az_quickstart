(function (Drupal) {
  'use strict';

  // Attach once behavior for Expand all buttons.
  Drupal.behaviors.azAccordionExpandAll = {
    attach: function (context) {
      var toggles = context.querySelectorAll ? context.querySelectorAll('[id^="accordion-toggle"]') : [];
      toggles.forEach(function (toggle) {
        if (toggle._azAccordionBound) {
          return;
        }
        toggle._azAccordionBound = true;

        toggle.addEventListener('click', function (e) {
          e.preventDefault();

          // Use the button's data-target attribute to find the exact accordion
          // container. data-target should be like '#accordion-123'.
          var targetSelector = toggle.getAttribute('data-target') || toggle.dataset.target;
          var accordionEl = null;
          if (targetSelector) {
            accordionEl = document.querySelector(targetSelector);
          }

          // Fallback: try to find a nearby .accordion element.
          if (!accordionEl) {
            var next = toggle.nextElementSibling;
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
          var collapseItems = accordionEl.querySelectorAll('.collapse');
          const lastState = this.dataset.lastState;

          if (lastState === null || lastState === "0") {
            // Collapse all elements
            collapseItems.forEach(el => {
              el.classList.remove('show');
            });

            // Update state and text to 'expand all'
            this.dataset.lastState = "1";
            this.textContent = "Expand all";
          } else {
            // Show all elements
            collapseItems.forEach(el => {
              el.classList.add('show');
            });

            // Update state and text to 'collapse all'
            this.dataset.lastState = "0";
            this.textContent = "Collapse all";
          }
        });
      });
    }
  };

})(Drupal);
