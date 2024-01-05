/**
 * @file
 * az_exposed_filters_sliders.js
 *
 * Adds jQuery UI Slider functionality to an exposed filter.
 */

(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.az_exposed_filters_slider = {
    attach: function (context, settings) {
      if (drupalSettings.az_exposed_filters.slider) {
        $.each(drupalSettings.az_exposed_filters.slider_options, function (i, sliderOptions) {
          var data_selector = 'edit-' + sliderOptions.dataSelector;

          // Collect all possible input fields for this filter.
          var $inputs = $(once('slider-filter', "input[data-drupal-selector=" + data_selector + "], input[data-drupal-selector=" + data_selector + "-max], input[data-drupal-selector=" + data_selector + "-min]", context));

          // This is a single-value filter.
          if ($inputs.length === 1) {
            // This is a single-value filter.
            var $input = $($inputs[0]);

            // Get the default value. We use slider min if there is no default.
            var defaultValue = parseFloat(($input.val() === '') ? sliderOptions.min : $input.val());

            // Set the element value in case we are using the slider min.
            $input.val(defaultValue);

            // Build the HTML and settings for the slider.
            var slider = $('<div class="az-exposed-filters-slider"></div>').slider({
              min: parseFloat(sliderOptions.min),
              max: parseFloat(sliderOptions.max),
              step: parseFloat(sliderOptions.step),
              animate: sliderOptions.animate ? sliderOptions.animate : false,
              orientation: sliderOptions.orientation,
              value: defaultValue,
              slide: function (event, ui) {
                $input.val(ui.value);
              },
              // This fires when the value is set programmatically or the stop
              // event fires. This takes care of the case that a user enters a
              // value into the text field that is not a valid step of the
              // slider. In that case the slider will go to the nearest step and
              // this change event will update the text area.
              change: function (event, ui) {
                $input.val(ui.value);
              },
              // Attach stop listeners.
              stop: function (event, ui) {
                // Click the auto submit button.
                $(this).parents('form').find('[data-az-exposed-filters-auto-submit-click]').click();
              }
            });

            $input.after(slider);

            // Update the slider when the field is updated.
            $input.blur(function () {
              azExposedFiltersUpdateSlider($(this), null, sliderOptions);
            });
          }
          else if ($inputs.length === 2) {
            // This is an in-between or not-in-between filter. Use a range
            // filter and tie the min and max into the two input elements.
            var $min = $($inputs[0]),
                $max = $($inputs[1]),
                // Get the default values. We use slider min & max if there are
                // no defaults.
                defaultMin = parseFloat(($min.val() == '') ? sliderOptions.min : $min.val()),
                defaultMax = parseFloat(($max.val() == '') ? sliderOptions.max : $max.val());

            // Set the element value in case we are using the slider min & max.
            $min.val(defaultMin);
            $max.val(defaultMax);

            var slider = $('<div class="az-exposed-filters-slider"></div>').slider({
              range: true,
              min: parseFloat(sliderOptions.min),
              max: parseFloat(sliderOptions.max),
              step: parseFloat(sliderOptions.step),
              animate: sliderOptions.animate ? sliderOptions.animate : false,
              orientation: sliderOptions.orientation,
              values: [defaultMin, defaultMax],
              // Update the textfields as the sliders are moved.
              slide: function (event, ui) {
                $min.val(ui.values[0]);
                $max.val(ui.values[1]);
              },
              // This fires when the value is set programmatically or the
              // stop event fires. This takes care of the case that a user
              // enters a value into the text field that is not a valid step
              // of the slider. In that case the slider will go to the
              // nearest step and this change event will update the text
              // area.
              change: function (event, ui) {
                $min.val(ui.values[0]);
                $max.val(ui.values[1]);
              },
              // Attach stop listeners.
              stop: function (event, ui) {
                // Click the auto submit button.
                $(this).parents('form').find('[data-az-exposed-filters-auto-submit-click]').click();
              }
            });

            $min.after(slider);

            // Update the slider when the fields are updated.
            $min.blur(function () {
              azExposedFiltersUpdateSlider($(this), 0, sliderOptions);
            });
            $max.blur(function () {
              azExposedFiltersUpdateSlider($(this), 1, sliderOptions);
            });
          }
        })
      }
    }
  }

  /**
   * Update a slider when a related input element is changed.
   *
   * We don't need to check whether the new value is valid based on slider min,
   * max, and step because the slider will do that automatically and then we
   * update the textfield on the slider's change event.
   *
   * We still have to make sure that the min & max values of a range slider
   * don't pass each other though, however once this jQuery UI bug is fixed we
   * won't have to.
   *
   * @see: http://bugs.jqueryui.com/ticket/3762
   *
   * @param $el
   *   A jQuery object of the updated element.
   * @param valIndex
   *   The index of the value for a range slider or null for a non-range slider.
   * @param sliderOptions
   *   The options for the current slider.
   */
  function azExposedFiltersUpdateSlider($el, valIndex, sliderOptions) {
    var val = parseFloat($el.val()),
        currentMin = $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('values', 0),
        currentMax = $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('values', 1);

    // If we have a range slider.
    if (valIndex != null) {
      // Make sure the min is not more than the current max value.
      if (valIndex === 0 && val > currentMax) {
        val = currentMax;
      }
      // Make sure the max is not more than the current max value.
      if (valIndex === 1 && val < currentMin) {
        val = currentMin;
      }
      // If the number is invalid, go back to the last value.
      if (isNaN(val)) {
        val = $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('values', valIndex);
      }
    }
    else {
      // If the number is invalid, go back to the last value.
      if (isNaN(val)) {
        val = $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('value');
      }
    }
    // Make sure we are a number again.
    val = parseFloat(val, 10);
    // Set the slider to the new value.
    // The slider's change event will then update the textfield again so that
    // they both have the same value.
    if (valIndex != null) {
      $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('values', valIndex, val);
    }
    else {
      $el.parents('div.views-widget').next('.az-exposed-filters-slider').slider('value', val);
    }
  }

})(jQuery, Drupal, drupalSettings, once);
