/**
 * @file
 * auto_submit.js
 *
 * Provides a "form auto-submit" feature for the AZ Exposed Filters module.
 */

(function ($, Drupal, once) {

  /**
   * To make a form auto submit, all you have to do is 3 things:.
   *
   * Use the "az_exposed_filters/auto_submit" js library.
   *
   * On gadgets you want to auto-submit when changed, add the
   * data-az-exposed-filters-auto-submit attribute. With FAPI, add:
   * @code
   *  '#attributes' => array('data-az-exposed-filters-auto-submit' => ''),
   * @endcode
   *
   * If you want to have auto-submit for every form element, add the
   * data-az-exposed-filters-auto-submit-full-form to the form. With FAPI, add:
   * @code
   *   '#attributes' => array('data-az-exposed-filters-auto-submit-full-form' => ''),
   * @endcode
   *
   * If you want to exclude a field from the az-exposed-filters-auto-submit-full-form auto
   * submission, add an attribute of data-az-exposed-filters-auto-submit-exclude to the form
   * element. With FAPI, add:
   * @code
   *   '#attributes' => array('data-az-exposed-filters-auto-submit-exclude' => ''),
   * @endcode
   *
   * Finally, you have to identify which button you want clicked for autosubmit.
   * The behavior of this button will be honored if it's ajaxy or not:
   * @code
   *  '#attributes' => array('data-az-exposed-filters-auto-submit-click' => ''),
   * @endcode
   *
   * Currently only 'select', 'radio', 'checkbox' and 'textfield' types are
   * supported. We probably could use additional support for HTML5 input types.
   */
  Drupal.behaviors.azExposedFiltersAutoSubmit = {
    attach: function (context) {
      // When exposed as a block, the form #attributes are moved from the form
      // to the block element, thus the second selector.
      // @see \Drupal\block\BlockViewBuilder::preRender
      var selectors = 'form[data-az-exposed-filters-auto-submit-full-form], [data-az-exposed-filters-auto-submit-full-form] form, [data-az-exposed-filters-auto-submit]';

      // The change event bubbles so we only need to bind it to the outer form
      // in case of a full form, or a single element when specified explicitly.
      $(selectors, context).addBack(selectors).each(function (i, e) {
        // Store the current form.
        var $form = $(e);

        // Retrieve the autosubmit delay for this particular form.
        var autoSubmitDelay = $form.data('az-exposed-filters-auto-submit-delay') || 500;

        // Attach event listeners.
        $(once('az-exposed-filters-auto-submit', $form))
          // On change, trigger the submit immediately.
          .on('change', triggerSubmit)
          // On keyup, wait for a specified number of milliseconds before
          // triggering autosubmit. Each new keyup event resets the timer.
          .on('keyup', Drupal.debounce(triggerSubmit, autoSubmitDelay));
      });

      /**
       * Triggers form autosubmit when conditions are right.
       *
       * - Checks first that the element that was the target of the triggering
       *   event is `:text` or `textarea`, but is not `.hasDatePicker`.
       * - Checks that the keycode of the keyup was not in the list of ignored
       *   keys (navigation keys etc).
       *
       * @param {object} e - The triggering event.
       */
      function triggerSubmit(e) {
        // e.keyCode: key.
        var ignoredKeyCodes = [
          16, // Shift.
          17, // Ctrl.
          18, // Alt.
          20, // Caps lock.
          33, // Page up.
          34, // Page down.
          35, // End.
          36, // Home.
          37, // Left arrow.
          38, // Up arrow.
          39, // Right arrow.
          40, // Down arrow.
          9, // Tab.
          13, // Enter.
          27  // Esc.
        ];

        // Triggering element.
        var $target = $(e.target);
        var $submit = $target.closest('form').find('[data-az-exposed-filters-auto-submit-click]');

        // Don't submit on changes to excluded elements or a submit element.
        if ($target.is('[data-az-exposed-filters-auto-submit-exclude], :submit') || ($target.attr('autocomplete') == 'off' && !$target.hasClass('az-exposed-filters-datepicker'))) {
          return true;
        }

        // Submit only if this is a non-datepicker textfield and if the
        // incoming keycode is not one of the excluded values.
        if (
          $target.is(':text:not(.hasDatepicker), textarea')
          && $.inArray(e.keyCode, ignoredKeyCodes) === -1
        ) {
          $submit.click();
        }
        // Only trigger submit if a change was the trigger (no keyup).
        else if (e.type === 'change') {
          $submit.click();
        }
      }
    }
  }

}(jQuery, Drupal, once));
