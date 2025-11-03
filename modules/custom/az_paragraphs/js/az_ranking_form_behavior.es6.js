/**
 * @file
 * Rankings paragraph behavior form interactions.
 *
 * Handles the relationship between ranking_clickable and ranking_hover_effect checkboxes.
 */

((Drupal, once) => {

  Drupal.behaviors.azRankingsParagraphBehavior = {
    attach: (context) => {
      // Find all ranking_clickable checkboxes
      const clickableCheckboxes = once(
        'az-rankings-clickable',
        'input[name*="[ranking_clickable]"]',
        context,
      );

      clickableCheckboxes.forEach((clickableElement) => {
        // Find the corresponding ranking_hover_effect checkbox
        // It should be in the same form, with a similar name pattern
        const clickableName = clickableElement.getAttribute('name');
        const hoverEffectName = clickableName.replace(
          '[ranking_clickable]', 
          '[ranking_hover_effect]'
        );
        const hoverEffectElement = document.querySelector(
          `input[name="${hoverEffectName}"]`
        );

        if (!hoverEffectElement) {
          return; // Skip if we can't find the hover effect checkbox
        }

        // When ranking_clickable is unchecked, also uncheck ranking_hover_effect
        clickableElement.addEventListener('change', () => {
          if (!clickableElement.checked) {
            // Uncheck hover effect when clickable is unchecked
            if (hoverEffectElement.checked) {
              hoverEffectElement.checked = false;
              // Trigger change event
              hoverEffectElement.dispatchEvent(
                new Event('change', { bubbles: true })
              );
            }
          }
        });
      });
    },
  };
})(Drupal, once);
