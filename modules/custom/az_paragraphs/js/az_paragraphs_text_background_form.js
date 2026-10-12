/**
 * @file
 * Text with background paragraph behavior form interactions.
 *
 * Limits the background pattern options to the patterns allowed by the
 * currently selected background color.
 */

((Drupal, once) => {
  Drupal.behaviors.azTextBackgroundParagraphBehavior = {
    attach: (context) => {
      const patternSelects = once(
        'az-text-background-pattern',
        'select[data-az-restricted-patterns]',
        context,
      );

      patternSelects.forEach((patternSelect) => {
        let restrictions;
        try {
          restrictions = JSON.parse(
            patternSelect.getAttribute('data-az-restricted-patterns'),
          );
        } catch (e) {
          return; // Skip if the restriction list can't be read.
        }

        // Find the background color select belonging to the same paragraph.
        const colorInputId = patternSelect.getAttribute(
          'data-az-text-background-pattern-for',
        );
        const colorSelect = document.querySelector(
          `select[data-az-text-background-color-input-id="${colorInputId}"]`,
        );

        if (!colorSelect) {
          return; // Skip if we can't find the background color select.
        }

        // Cache the restricted options so they can be restored later. They are
        // kept in their original order so that re-appending a restored option
        // leaves the dropdown in its declared order.
        const restrictedOptions = Array.from(patternSelect.options)
          .filter((option) => option.value in restrictions)
          .map((option) => ({ element: option, value: option.value }));

        if (!restrictedOptions.length) {
          return;
        }

        // Show only the patterns the current background color allows. When the
        // selected pattern is no longer allowed, fall back to no pattern.
        const syncOptions = () => {
          let optionsChanged = false;
          let selectionRemoved = false;

          restrictedOptions.forEach(({ element, value }) => {
            const allowed = restrictions[value].includes(colorSelect.value);

            if (allowed && !element.isConnected) {
              patternSelect.appendChild(element);
              optionsChanged = true;
            } else if (!allowed && element.isConnected) {
              if (patternSelect.value === value) {
                selectionRemoved = true;
              }
              element.remove();
              optionsChanged = true;
            }
          });

          if (selectionRemoved) {
            patternSelect.value = '';
          }

          if (optionsChanged) {
            // Chosen renders its own copy of the option list, so it has to be
            // told to redraw whenever the available options change.
            patternSelect.dispatchEvent(
              new Event('chosen:updated', { bubbles: true, cancelable: true }),
            );
          }

          if (selectionRemoved) {
            patternSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };

        colorSelect.addEventListener('change', syncOptions);

        // Apply the restrictions to the stored values on page load, so that a
        // paragraph saved with a pattern its background color no longer allows
        // is corrected while it is being edited rather than on save.
        syncOptions();
      });
    },
  };
})(Drupal, once);
