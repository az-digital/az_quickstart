/**
 * @file
 * Default JavaScript file for Block Class.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.blockClass = {
    attach: function (context, settings) {

      // Verify if the block class was render in Drupal Modal.
      var isDrupalModal = $('.show-items-used').closest('#drupal-modal').length;

      // If was loaded in Modal don't show a duplicated Modal.
      if (isDrupalModal) {

        // Hide the items used link.
        $('.show-items-used').css( "display", "none" );

        // Collapse the details field when the Modal is open.
        $('.replaced-id-details').find('summary').trigger( "click" );

        // Collapse the attribute as well on this scenario.
        $('.attribute-details').find('summary').trigger( "click" );

      }

      // Verify if there validated items and should be displayed.
      verifyValidatedItems();

      addAnotherAttributeItem();

      removeAttributeItem();

      verify_to_disable_add_more_attribute_button();

      disable_remove_attribute();

      /**
       * This method will verify if there is validated items and should be displayed.
      */
      function verifyValidatedItems() {

        // Get all multiple items.
        var multiple_textfields = $('.multiple-textfield');

        // Do a foreach in all items.
        multiple_textfields.each(function( index ) {

          // Verify if there a value in the item.
          if ($(this).val() != '') {

            // If there is value, use display block to show item for the user.
            $(this).parent('div').css('display', 'block');

            // Show all items, parent and the item as well.
            $(this).css('display', 'block');

          }

        });

        var multiple_textfield_attribute = $('.multiple-textfield-attribute');

        multiple_textfield_attribute.each(function( index ) {

          if ($(this).find('input').val() != '') {

            $(this).addClass('displayed-attribute-field');
            $(this).removeClass('hidden-attribute-field');

          }

        });

      }

      // Hide the parent of hidden items on block class field.
      $('.hidden-class-field').parent().css( "display", "none" );

      // Hide for the Claro, Olivero and other themes.
      $('.hidden-class-field').closest('.js-form-type-textfield').css( "display", "none" );

      // When the user is typing we can remove the spaces because isn't used
      // When there are multiple class fields.
      $('.multiple-textfield').keyup(function() {
        $(this).val($(this).val().replace(/ /g, ""));
      });

      // Remove the spaces on replaced ID item in the block settings.
      $('.replaced-id-item').keyup(function() {
        $(this).val($(this).val().replace(/ /g, ""));
      });

      // When the user is typing in the multiple attribute field remove all
      // spaces as well because isn't allowed on that fields.
      var multiple_attributes = $('.multiple-textfield-attribute');

      multiple_attributes.each(function( index ) {

        var key_input = $(this).find('input').first();

        key_input.keyup(function() {
          key_input.val(key_input.val().replace(/ /g, ""));
        });

      });

      verify_to_disable_add_more_item_button();

      // Verify if there is only item or more to lock / unlock the removal.
      disable_removal_unique_class();

      /**
       * This method will add another attribute item.
      */
       function addAnotherAttributeItem() {

        $(".block-class-add-another-attribute").unbind().click(function(event) {

          // Prevent Default to stop the normal behavior and avoid going to
          // another page.
          event.preventDefault();

          $('.hidden-attribute-field').first().css('display','block');

          $('.hidden-attribute-field').first().addClass('displayed-attribute-field');

          $('.hidden-attribute-field').first().removeClass('hidden-attribute-field');

          verify_to_disable_add_more_attribute_button();

          disable_remove_attribute();

        });

      }

      /**
       * This method will remove one attribute item.
      */
      function removeAttributeItem() {

        $(".block-class-remove-attribute").unbind().click(function(event) {

          event.preventDefault();

          var last_attribute_visible = $('.displayed-attribute-field');

          if (last_attribute_visible.length == 1) {
            return false;
          }

          last_attribute_visible.last().find('input').val('');

          last_attribute_visible.last().css('display','none');

          last_attribute_visible.last().addClass('hidden-attribute-field');

          last_attribute_visible.last().removeClass('displayed-attribute-field');

          verify_to_disable_add_more_attribute_button();

          disable_remove_attribute();

        });
      }

      // On click to add another item, prevent default to stop going to another
      // page.
      $(".block-class-add-another-item").unbind().click(function(event) {

        // Prevent Default to stop the normal behavior and avoid going to
        // another page.
        event.preventDefault();

        // Show the parent div of the hidden class field.
        $('.hidden-class-field').first().parent('div').css('display','block');

        $('.hidden-class-field').first().parent('div').css('display','block');

        $('.hidden-class-field').first().closest('.js-form-type-textfield').css('display','block');

        // Put a class to identify the multiple textfield that is visible.
        $('.hidden-class-field').first().addClass('displayed-class-field');

        // Remove the "hidden class" from this field to show it.
        $('.hidden-class-field').first().removeClass('hidden-class-field');

        // Verify if there is another item that possibility us to add a new one.
        verify_to_disable_add_more_item_button();

        // Verify if there is only item or more to lock / unlock the removal.
        disable_removal_unique_class();

      });

      // On click to remove item, prevent default to stop going to another
      // page.
      $(".block-class-remove-item").unbind().click(function(event) {

        // Prevent Default to stop the normal behavior and avoid going to
        // another page.
        event.preventDefault();

        // If there is only one class don't remove this one.
        if ($('.displayed-class-field').length == 1) {
          return false;
        }

        // Remove the value of the field.
        $('.displayed-class-field').last().val('');

        // Get the last field and put the display none to remove this one.
        $('.displayed-class-field').last().parent('div').css('display','none');

        $('.displayed-class-field').last().closest('.js-form-type-textfield').css('display','none');

        // Add the class to identify again that this field is hidden.
        $('.displayed-class-field').last().addClass('hidden-class-field');

        // Remove the class that was identifying this field as displayed.
        $('.displayed-class-field').last().removeClass('displayed-class-field');

        // Verify if there is another item that possibility us to add a new one.
        verify_to_disable_add_more_item_button();

        // Verify if there is only item or more to lock / unlock the removal.
        disable_removal_unique_class();

      });


      /**
       * This method will verify if there is another item to be added.
      */
      function verify_to_disable_add_more_item_button() {

        // Get the qty_classes_per_block items.
        var qty_classes_per_block = $('.multiple-textfield').length;

        // Get the qty of items in the page.
        var qty_items_in_the_page = $('.displayed-class-field').length;

        // Verify if the qty items in the settings page is the same in the page.
        if (qty_classes_per_block == qty_items_in_the_page) {

          // If yes disable the button to add more.
          $('.block-class-add-another-item').prop('disabled', true);

          // Display the help text to show the instructions about how to update
          // the items per block.
          $('.help-text-qty-items').removeClass('help-text-qty-items-hidden');

          return;

        }

        // If there is more, add the button to add more to be possible to add
        // new items in the page.
        $('.block-class-add-another-item').prop('disabled', false);

        // Hide the help text that show the instructions about how to update the
        // items per block.
        $('.help-text-qty-items').addClass('help-text-qty-items-hidden');

      }

      /**
       * This method will verify if there is another attribute to be added.
      */
      function verify_to_disable_add_more_attribute_button() {

        var qty_attributes_per_block = $('.multiple-textfield-attribute').length;

        var qty_attributes_in_the_page = $('.displayed-attribute-field').length;

        // Verify if the qty items in the settings page is the same in the page.
        if (qty_attributes_per_block == qty_attributes_in_the_page) {

          // If yes disable the button to add more.
          $('.block-class-add-another-attribute').prop('disabled', true);

          return;

        }

        // If there is more, add the button to add more to be possible to add
        // new items in the page.
        $('.block-class-add-another-attribute').prop('disabled', false);

      }

      /**
       * This method will verify if is the last attribute that can't be removed.
       */
       function disable_remove_attribute() {

        // If there is only one item don't allow remove this.
        if ($('.displayed-attribute-field').length == 1) {
          $('.block-class-remove-attribute').prop('disabled', true);
          return;
        }

        // If there are more items put the option to remove if want.
        $('.block-class-remove-attribute').prop('disabled', false);

      }

      /**
       * This method will verify if is the last one that can't be removed.
       */
      function disable_removal_unique_class() {

        // If there is only one item don't allow remove this.
        if ($('.displayed-class-field').length == 1) {
          $('#edit-class-third-party-settings-block-class-remove-item').prop('disabled', true);
          return;
        }

        // If there are more items put the option to remove if want.
        $('#edit-class-third-party-settings-block-class-remove-item').prop('disabled', false);

      }

      // Get the default case of Block Class.
      var default_case = 'standard';

      // Verify if the Drupal.settings is available to be used.
      if (typeof settings.block_class != 'undefined' && settings.block_class.default_case != 'undefined') {

        // Get the default case from the settings page.
        default_case = settings.block_class.default_case;

        // Verify if is different than standard because with this all case type
        // Can be used, lowercase and uppercase. So isn't necessary validation.
        if (default_case != 'standard') {

          // If the default is Uppercase convert all letters to Uppercase.
          if (default_case == 'uppercase') {

            $('.block-class-class, .multiple-textfield, .block-class-attributes, .block-class-bulk-operations-insert-classes_to_be_added, .block-class-bulk-operations-update-class-new-class, .replaced-id-item').keyup(function() {
              $(this).val($(this).val().toUpperCase());
            });

            // Transform the text to uppercase when the default case is uppercase.
            $('.multiple-textfield-attribute').find('input').keyup(function() {
              $(this).val($(this).val().toUpperCase());
            });
          }
          else {
            // If the default is lowercase convert all letters to lowercase.
            $('.block-class-class, .multiple-textfield, .block-class-attributes, .block-class-bulk-operations-insert-classes_to_be_added, .block-class-bulk-operations-update-class-new-class, .replaced-id-item').keyup(function() {
              $(this).val($(this).val().toLowerCase());
            });

            // Transform the text based on the settings item.
            $('.multiple-textfield-attribute').find('input').keyup(function() {
              $(this).val($(this).val().toLowerCase());
            });

          }
        }
      }

      // Verify if there validated items and should be displayed.
      verifyValidatedItems();

    }
  };
})(jQuery, Drupal, drupalSettings);
