/**
 * @file
 * Rankings paragraph behavior form interactions.
 *
 * Handles the relationship between ranking_clickable and ranking_hover_effect checkboxes.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.azRankingsParagraphBehavior = {
    attach: function (context, settings) {
      // Find all ranking_clickable checkboxes
      const $clickableCheckboxes = $(once('az-rankings-clickable', 'input[name*="[ranking_clickable]"]', context));

      $clickableCheckboxes.each(function() {
        const $clickable = $(this);
        
        // Find the corresponding ranking_hover_effect checkbox
        // It should be in the same form, with a similar name pattern
        const clickableName = $clickable.attr('name');
        const hoverEffectName = clickableName.replace('[ranking_clickable]', '[ranking_hover_effect]');
        const $hoverEffect = $('input[name="' + hoverEffectName + '"]');

        if ($hoverEffect.length === 0) {
          return; // Skip if we can't find the hover effect checkbox
        }

        // When ranking_clickable is unchecked, also uncheck ranking_hover_effect
        $clickable.on('change', function() {
          if (!$clickable.is(':checked')) {
            // Uncheck hover effect when clickable is unchecked
            if ($hoverEffect.is(':checked')) {
              $hoverEffect.prop('checked', false).trigger('change');
            }
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);
