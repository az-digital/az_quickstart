/**
 * @file
 * bef_sliders.js
 *
 * Adds Sliders functionality to an exposed filter.
 */

(function ($, document, Drupal, drupalSettings, once) {
  Drupal.behaviors.better_exposed_filters_slider = {
    attach: function (context, settings) {
      if (drupalSettings.better_exposed_filters.slider) {
        $.each(drupalSettings.better_exposed_filters.slider_options, function (i, sliderOptions) {
          let slider;
          const data_selector = 'edit-' + sliderOptions.dataSelector;
          const direction = $('html[dir="rtl"]').length > 0 ? 'rtl' : 'ltr';

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
            slider = document.createElement('div');
            slider.className = 'bef-slider';

            // Element must be part of the DOM tree for getComputedStyle to
            // return non-empty values.
            // @see https://github.com/leongersen/noUiSlider/blob/15.5.1/dist/nouislider.js#L1000
            document.body.appendChild(slider);

            noUiSlider.create(slider, {
              range: {
                'min': parseFloat(sliderOptions.min),
                'max': parseFloat(sliderOptions.max)
              },
              step: parseFloat(sliderOptions.step),
              animate: !!sliderOptions.animate,
              animationDuration: parseInt(sliderOptions.animate),
              orientation: sliderOptions.orientation,
              start: [defaultValue],
              direction: direction,
              format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                  return Math.trunc(Number(value));
                },
                // 'from' the formatted value.
                from: function (value) {
                  return Math.trunc(Number(value));
                }
              }
            });
            // This fires every time the slider values are changed, either by a
            // user or by calling API methods. Additionally, it fires
            // immediately when bound. In most cases, this event should be more
            // convenient than the 'slide' event.
            slider.noUiSlider.on('update', function (values, handle) {
              $input.val(values[handle]);
            });
            // This fires every time a slider stops changing, including after
            // calls to the .set() method. This event can be considered as the
            // 'end of slide'.
            slider.noUiSlider.on('set', function () {
              // Click the auto submit button.
              $(slider).parents('form').find('[data-bef-auto-submit-click]').click();
            });

            $input.after(slider);

            // Update the slider when the field is updated.
            $input.blur(function () {
              befUpdateSlider($(this), null, slider);
            });
          }
          else if ($inputs.length === 2) {
            // This is an in-between or not-in-between filter. Use a range
            // filter and tie the min and max into the two input elements.
            var $min = $($inputs[0]),
                $max = $($inputs[1]),
                // Get the default values. We use slider min & max if there are
                // no defaults.
                defaultMin = parseFloat(($min.val() === '') ? sliderOptions.min : $min.val()),
                defaultMax = parseFloat(($max.val() === '') ? sliderOptions.max : $max.val());

            // Set the element value in case we are using the slider min & max.
            $min.val(defaultMin);
            $max.val(defaultMax);

            slider = document.createElement('div');
            slider.className = 'bef-slider';

            // Element must be part of the DOM tree for getComputedStyle to
            // return non-empty values.
            // @see https://github.com/leongersen/noUiSlider/blob/15.5.1/dist/nouislider.js#L1000
            document.body.appendChild(slider);

            noUiSlider.create(slider, {
              range: {
                'min': parseFloat(sliderOptions.min),
                'max': parseFloat(sliderOptions.max)
              },
              step: parseFloat(sliderOptions.step),
              animate: !!sliderOptions.animate,
              animationDuration: parseInt(sliderOptions.animate),
              orientation: sliderOptions.orientation,
              start: [defaultMin, defaultMax],
              connect: true,
              direction: direction,
              format: {
                // 'to' the formatted value. Receives a number.
                to: function (value) {
                  return Math.trunc(Number(value));
                },
                // 'from' the formatted value.
                from: function (value) {
                  return Math.trunc(Number(value));
                }
              }
            });
            // Update the textfields as the sliders are moved.
            slider.noUiSlider.on('update', function (values) {
              $min.val(values[0]);
              $max.val(values[1]);
            });
            // This fires every time a slider stops changing, including after
            // calls to the .set() method. This event can be considered as the
            // 'end of slide'.
            slider.noUiSlider.on('set', function () {
              // Click the auto submit button.
              $(slider).parents('form').find('[data-bef-auto-submit-click]').click();
            });

            $min.after(slider);

            // Update the slider when the fields are updated.
            $min.blur(function () {
              befUpdateSlider($(this), 0, slider);
            });
            $max.blur(function () {
              befUpdateSlider($(this), 1, slider);
            });
          }
        });
      }
    }
  };

  /**
   * Update a slider when a related input element is changed.
   *
   * We don't need to check whether the new value is valid based on slider min,
   * max, and step because the slider will do that automatically, and then we
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
   * @param slider
   *   The current slider.
   */
  function befUpdateSlider($el, valIndex, slider) {
    var val = parseFloat($el.val()),
        currentMin = slider.noUiSlider.get(true)[0],
        currentMax = slider.noUiSlider.get(true)[1];

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
        val = slider.noUiSlider.get(true)[valIndex];
      }
    }
    else {
      // If the number is invalid, go back to the last value.
      if (isNaN(val)) {
        val = slider.noUiSlider.get(true);
      }
    }
    // Make sure we are a number again.
    val = parseFloat(val, 10);
    // Set the slider to the new value.
    // The slider's change event will then update the textfield again so that
    // they both have the same value.
    if (valIndex != null) {
      slider.noUiSlider.setHandle(valIndex, val, null, true);
    }
    else {
      slider.noUiSlider.set(val);
    }
  }

})(jQuery, this.document, Drupal, drupalSettings, once);
