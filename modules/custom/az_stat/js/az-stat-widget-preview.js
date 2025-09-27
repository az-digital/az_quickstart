/**
 * @file
 * Az Stat Widget Preview functionality.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Attaches Az Stat Widget Preview behavior.
   */
  Drupal.behaviors.azStatWidgetPreview = {
    attach: function (context, settings) {
      console.log('Az Stat Widget Preview behavior attached');
      
      // Find all stat widget details elements
      var detailsElements = once('az-stat-widget-preview', 'details.az-stat-widget', context);
      detailsElements.forEach(function (element) {
        console.log('Found az-stat-widget details element', element);
        var $details = $(element);
        var $previewWrapper = $details.siblings('.widget-preview-wrapper');
        
        console.log('Preview wrapper found:', $previewWrapper.length);
        
        if ($previewWrapper.length === 0) {
          console.log('No preview wrapper found, skipping');
          return;
        }

        // Listen for toggle events
        $details.on('toggle', function () {
          console.log('Details toggled, open:', this.open);
          var isOpen = this.open;
          
          // Only update preview when closing (showing preview)
          if (!isOpen) {
            console.log('Updating preview...');
            updatePreview($details, $previewWrapper);
          }
        });
      });
    }
  };

  /**
   * Updates the preview with current form values.
   */
  function updatePreview($details, $previewWrapper) {
    console.log('updatePreview called');
    var $preview = $previewWrapper.find('.widget-preview-stat');
    console.log('Preview element found:', $preview.length);
    
    // Get current form values - look inside the details element
    var $headingInput = $details.find('input[name*="[details][stat_heading]"]');
    var $descriptionInput = $details.find('input[name*="[details][stat_description]"]');
    var $sourceTextarea = $details.find('textarea[name*="[details][stat_source]"]');
    var $optionsSelect = $details.find('select[name*="[details][options]"]');
    
    console.log('Form elements found:', {
      headingInput: $headingInput.length,
      descriptionInput: $descriptionInput.length,
      sourceTextarea: $sourceTextarea.length,
      optionsSelect: $optionsSelect.length
    });
    
    var heading = $headingInput.val() || '';
    var description = $descriptionInput.val() || '';
    var source = $sourceTextarea.val() || '';
    var selectedOption = $optionsSelect.val() || 'text-bg-white';
    
    console.log('Form values:', {heading: heading, description: description, source: source, option: selectedOption});
    
    // Update preview text content - now we can use the stat-heading class
    var $headingElement = $preview.find('.stat-heading strong');
    var $descriptionElement = $preview.find('.stat-description');
    var $sourceElement = $preview.find('.stat-source');
    
    console.log('Preview elements found:', {
      heading: $headingElement.length, 
      description: $descriptionElement.length, 
      source: $sourceElement.length
    });
    
    if ($headingElement.length) {
      console.log('Updating heading from "' + $headingElement.text() + '" to "' + heading + '"');
      $headingElement.text(heading);
    } else {
      console.log('No heading element found to update');
    }
    
    if ($descriptionElement.length) {
      console.log('Updating description from "' + $descriptionElement.text() + '" to "' + description + '"');
      $descriptionElement.text(description);
    } else {
      console.log('No description element found to update');
    }
    
    if ($sourceElement.length && source) {
      console.log('Updating source from "' + $sourceElement.text().trim() + '" to "' + source + '"');
      $sourceElement.text(source);
    } else {
      console.log('No source element found to update, or source is empty');
    }
    
    // Update preview background color
    if ($preview.length) {
      // Remove all existing background color classes
      var bgClasses = [
        'text-bg-white', 'bg-transparent', 'text-bg-red', 'text-bg-blue', 'text-bg-sky',
        'text-bg-oasis', 'text-bg-azurite', 'text-bg-midnight', 'text-bg-bloom', 'text-bg-chili',
        'text-bg-cool-gray', 'text-bg-warm-gray', 'text-bg-gray-100', 'text-bg-gray-200',
        'text-bg-gray-300', 'text-bg-leaf', 'text-bg-river', 'text-bg-silver', 'text-bg-ash', 'text-bg-mesa'
      ];
      
      $preview.removeClass(bgClasses.join(' '));
      
      // Add the new background color class
      if (selectedOption) {
        console.log('Updating background color to:', selectedOption);
        $preview.addClass(selectedOption);
      }
    }
    
    // Hide/show elements based on content
    if (heading) {
      $headingElement.show();
    } else {
      $headingElement.hide();
    }
    
    if (description) {
      $descriptionElement.show();
    } else {
      $descriptionElement.hide();
    }
    
    if (source) {
      $sourceElement.show();
    } else {
      $sourceElement.hide();
    }
  }

})(jQuery, Drupal, once);