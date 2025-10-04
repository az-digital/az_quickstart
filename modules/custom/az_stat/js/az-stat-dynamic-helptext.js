/**
 * @file
 * Provides dynamic help text updates for az_stat column span field.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Generate aspect ratio help text based on stat_width.
   * 
   * @param {string} statWidth - The stat width value (col-lg-3, col-lg-4, etc.)
   * @returns {string} - The HTML help text
   */
  function getAspectRatioHelpText(statWidth) {
    var helpText = '<strong>Recommended aspect ratios (W:H):</strong><br>';
    
    // Get aspect ratio data from drupalSettings
    var aspectRatios = drupalSettings.azStat && drupalSettings.azStat.aspectRatios 
      ? drupalSettings.azStat.aspectRatios 
      : {};
    
    if (aspectRatios[statWidth]) {
      var ratios = aspectRatios[statWidth];
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
   * Update all aspect ratio help text elements with new stat_width value.
   * 
   * @param {string} newStatWidth - The new stat width value
   */
  function updateAspectRatioHelp(newStatWidth) {
    $('.aspect-ratio-help').each(function() {
      var $helpElement = $(this);
      var currentStatWidth = $helpElement.data('current-stat-width');
      
      // Only update if the stat width has changed
      if (currentStatWidth !== newStatWidth) {
        var newHelpText = getAspectRatioHelpText(newStatWidth);
        $helpElement.html(newHelpText);
        $helpElement.data('current-stat-width', newStatWidth);
      }
    });
  }

  /**
   * Initialize dynamic help text behavior.
   */
  Drupal.behaviors.azStatDynamicHelp = {
    attach: function (context, settings) {
      // Selector for the stat_width field based on actual HTML structure
      var selector = 'select[name*="[behavior_plugins][az_stats_paragraph_behavior][stat_width]"]';
      
      // Attach change event listener to stat_width selector
      var elements = once('az-stat-dynamic-help', selector, context);
      $(elements).on('change', function() {
        var newStatWidth = $(this).val();
        updateAspectRatioHelp(newStatWidth);
      });
    }
  };

})(jQuery, Drupal, once);
