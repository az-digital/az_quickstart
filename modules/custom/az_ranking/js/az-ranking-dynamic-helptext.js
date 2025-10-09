/**
 * @file
 * Provides dynamic help text updates for az_ranking column span field.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Generate aspect ratio help text based on ranking_width.
   * 
   * @param {string} rankingWidth - The ranking width value (col-lg-3, col-lg-4, etc.)
   * @returns {string} - The HTML help text
   */
  function getAspectRatioHelpText(rankingWidth) {
    var helpText = '<strong>Recommended aspect ratios (W:H):</strong><br>';
    
    // Get aspect ratio data from drupalSettings
    var aspectRatios = drupalSettings.azRanking && drupalSettings.azRanking.aspectRatios 
      ? drupalSettings.azRanking.aspectRatios 
      : {};
    
    if (aspectRatios[rankingWidth]) {
      var ratios = aspectRatios[rankingWidth];
      var lines = [];
      
      for (var key in ratios) {
        if (ratios.hasOwnProperty(key)) {
          var label = key === 'any' ? 'Any column span' : key + ' column' + (key.includes('+') || parseInt(key) > 1 ? 's' : '');
          lines.push(label + ': <strong>' + ratios[key] + '</strong>');
        }
      }
      
      helpText += lines.join('<br>');
      return helpText;
    }
    
    // No aspect ratio data found, return empty string
    return '';
  }

  /**
   * Update all aspect ratio help text elements with new ranking_width value.
   * 
   * @param {string} newRankingWidth - The new ranking width value
   */
  function updateAspectRatioHelp(newRankingWidth) {
    $('.aspect-ratio-help').each(function() {
      var $helpElement = $(this);
      var currentRankingWidth = $helpElement.data('current-ranking-width');
      
      // Only update if the ranking width has changed
      if (currentRankingWidth !== newRankingWidth) {
        var newHelpText = getAspectRatioHelpText(newRankingWidth);
        $helpElement.html(newHelpText);
        $helpElement.data('current-ranking-width', newRankingWidth);
      }
    });
  }

  /**
   * Initialize dynamic help text behavior.
   */
  Drupal.behaviors.azRankingDynamicHelp = {
    attach: function (context, settings) {
      // Selector for the ranking_width field based on actual HTML structure
      var selector = 'select[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_width]"]';
      
      // Attach change event listener to ranking_width selector
      var elements = once('az-ranking-dynamic-help', selector, context);
      $(elements).on('change', function() {
        var newRankingWidth = $(this).val();
        updateAspectRatioHelp(newRankingWidth);
      });
    }
  };

})(jQuery, Drupal, once);
