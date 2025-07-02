/**
 * @file
 * auto_submit.js
 *
 * Provides a "form auto-submit" feature for the Better Exposed Filters module.
 */

(function ($, Drupal, once) {

  /**
   * To make a form auto submit, all you have to do is 3 things:.
   *
   * Use the "better_exposed_filters/auto_submit" js library.
   *
   * On gadgets, you want to auto-submit when changed, add the
   * data-bef-auto-submit attribute. With FAPI, add:
   * @code
   *  '#attributes' => array('data-bef-auto-submit' => ''),
   * @endcode
   *
   * If you want to have auto-submit for every form element, add the
   * data-bef-auto-submit-full-form to the form. With FAPI, add:
   * @code
   *   '#attributes' => array('data-bef-auto-submit-full-form' => ''),
   * @endcode
   *
   * If you want to exclude a field from the bef-auto-submit-full-form auto
   * submission, add an attribute of data-bef-auto-submit-exclude to the form
   * element. With FAPI, add:
   * @code
   *   '#attributes' => array('data-bef-auto-submit-exclude' => ''),
   * @endcode
   *
   * Finally, you have to identify which button you want clicked for autosubmit.
   * The behavior of this button will be honored if it's ajax or not:
   * @code
   *  '#attributes' => array('data-bef-auto-submit-click' => ''),
   * @endcode
   *
   * Currently only 'select', 'radio', 'checkbox' and 'textfield' types are
   * supported. We probably could use additional support for HTML5 input types.
   */
  Drupal.behaviors.betterExposedFiltersAutoSubmit = {
    attach: function (context, settings) {
      // When exposed as a block, the form #attributes are moved from the form
      // to the block element, thus the second selector.
      // @see \Drupal\block\BlockViewBuilder::preRender
      const selectors = 'form[data-bef-auto-submit-full-form], [data-bef-auto-submit-full-form] form, [data-bef-auto-submit]';

      $(selectors, context).addBack(selectors).find('input:text:not(.hasDatepicker), textarea').each(function () {
        const $el = $(this);
        const $valueLength = $el.val().length * 2;

        $el[0].setSelectionRange($valueLength, $valueLength);
        setFocus($el, $valueLength);
      });

      function setFocus($el) {
        const observer = new IntersectionObserver((entries) => {

          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const $lastTriggeredSelector = $(settings.bef_autosubmit_target).attr('data-drupal-selector');
              if ($el.attr('data-drupal-selector') && $el.attr('data-drupal-selector') === $lastTriggeredSelector) {
                $el.focus();
              }
            }
          });
        });

        observer.observe($el.get(0));
      }

      // The change event bubbles, so we only need to bind it to the outer form
      // in case of a full form, or a single element when specified explicitly.
      $(selectors, context).addBack(selectors).each(function (i, e) {
        const $e = $(e);
        // Store the current form.
        let $needsAutoSubmit = $e;

        // Retrieve the autosubmit delay for this particular form.
        let autoSubmitDelay = 500;
        if (e.hasAttribute('data-bef-auto-submit-delay')) {
          autoSubmitDelay = $e.data('bef-auto-submit-delay');
        }
        else if (!e.hasAttribute('data-bef-auto-submit-full-form')) {
          // Find the container but skip checking if this element already is
          // 'form[data-bef-auto-submit-full-form]'.
          const $container = $e.closest('[data-bef-auto-submit-full-form]');
          if ($container.length && $container.get(0).hasAttribute('data-bef-auto-submit-delay')) {
            $needsAutoSubmit = $container;
            autoSubmitDelay = $container.data('bef-auto-submit-delay');
          }
        }

        // Ensure that have a delay.
        autoSubmitDelay = autoSubmitDelay || 500;
        // Separate debounce instance for date inputs change event.
        let dateInputChangeDebounce = Drupal.debounce(triggerSubmit, autoSubmitDelay, false);

        // Attach event listeners.
        $(once('bef-auto-submit', $needsAutoSubmit))
          // On change, trigger the submit immediately unless it's a date
          // input which emits change event as soon as the date value is
          // valid, even if user is still typing.
          .on('change', function (e) {
            return e.target.type === 'date'
              ? dateInputChangeDebounce(e)
              : triggerSubmit(e);
          })
          // On keyup, wait for a specified number of milliseconds before
          // triggering autosubmit. Each new keyup event resets the timer.
          .on('keyup', Drupal.debounce(triggerSubmit, autoSubmitDelay, false));
      });

      /**
       * Triggers form autosubmit when conditions are right.
       *
       * - Checks first that the element that was the target of the triggering
       *   event is `:text` or `textarea`, but is not `.hasDatePicker`.
       * - Checks that the keycode of the keyup was not in the list of ignored
       *   keys (navigation keys etc.).
       *
       * @param {object} e - The triggering event.
       */
      function triggerSubmit(e) {
        // e.keyCode: key.
        const ignoredKeyCodes = [
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
          27,  // Esc.
        ];

        // Triggering element.
        const $target = $(e.target);
        const $submit = $target.closest('form').find('[data-bef-auto-submit-click]');

        // Don't submit on changes to excluded elements,submit elements, or select2 autocomplete.
        if ($target.is('[data-bef-auto-submit-exclude], :submit, .select2-search__field, .chosen-search-input')) {
          return true;
        }

        // Submit only if this is a non-datepicker textfield and if the
        // incoming keycode is not one of the excluded values, and it has a
        // minimum character length.
        let textfieldMinimumLength = $target.closest('form').data('bef-auto-submit-minimum-length') || 1;
        let inputTextLength = $target.val().length;
        let textfieldHasMinimumLength = ($target.is(':text:not(.hasDatepicker), textarea') && inputTextLength >= textfieldMinimumLength) || $target.is('[type="checkbox"], [type="radio"]');
        if (
          (textfieldHasMinimumLength || inputTextLength === 0)
          && $.inArray(e.keyCode, ignoredKeyCodes) === -1
          && !e.altKey
          && !e.ctrlKey
          && !e.shiftKey
        ) {
          $submit.click();
        }
        // Only trigger submit if a change was the trigger (no keyup).
        else if (e.type === 'change') {
          let target = $target.is(':text:not(.hasDatepicker), textarea');
          if (target && textfieldHasMinimumLength) {
            $submit.click();
          }
          else if (!target) {
            $submit.click();
          }
        }

        settings.bef_autosubmit_target = $target;
      }
    },
  };

}(jQuery, Drupal, once));
