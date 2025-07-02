(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.smartDateRecur = {
    attach(context, settings) {
      const repeatLabels = {
        DAILY: '',
        WEEKLY: '',
        MONTHLY: '',
        YEARLY: '',
      };

      const selectedLabels = {
        DAILY: Drupal.t('days', {}, { context: 'Smart Date Recur' }),
        WEEKLY: Drupal.t('weeks', {}, { context: 'Smart Date Recur' }),
        MONTHLY: Drupal.t('months', {}, { context: 'Smart Date Recur' }),
        YEARLY: Drupal.t('years', {}, { context: 'Smart Date Recur' }),
      };

      function durationToMinutes(element) {
        const wrapper = element.closest('fieldset');
        const freq = wrapper.querySelector('.recur-repeat');
        if (freq.value !== 'MINUTELY') {
          // The rest only needed for Minutes.
          return;
        }
        const durationSelect = wrapper.querySelector('select.field-duration');
        let durationVal = durationSelect.value;
        if (durationVal === 'custom') {
          durationVal = parseInt(durationSelect.dataset.duration, 10);
        }
        const interval = wrapper.querySelector('.field-interval');
        interval.value = durationVal;
      }

      function updateInterval(element) {
        const wrapper = element.closest('fieldset');
        const freq = wrapper.querySelector('.recur-repeat');
        if (freq.value === 'MINUTELY') {
          // When changing to minutes, set to the current duration.
          durationToMinutes(element);
        } else if (freq.dataset.freq === 'MINUTELY') {
          // Only reset if changing from minutes.
          const interval = wrapper.querySelector('.field-interval');
          interval.value = '';
        }
        freq.dataset.freq = freq.value;
      }

      function setDataFreq(element) {
        const wrapper = element.closest('fieldset');
        const freq = wrapper.querySelector('.recur-repeat');
        freq.dataset.freq = freq.value;
      }

      function toggleMinutesHours(element) {
        const wrapper = element.closest('fieldset');
        const freq = wrapper.querySelector('.recur-repeat');
        const optionMinutes = freq.querySelector("option[value = 'MINUTELY']");
        const optionHours = freq.querySelector("option[value = 'HOURLY']");
        const isChecked = element.checked;
        if (isChecked) {
          if (optionMinutes) {
            optionMinutes.disabled = true;
          }
          if (optionHours) {
            optionHours.disabled = true;
          }
        } else {
          if (optionMinutes) {
            optionMinutes.disabled = false;
          }
          if (optionHours) {
            optionHours.disabled = false;
          }
        }
      }

      function updateRepeatLabels(element) {
        const postRepeat = element.dataset.repeat;
        // Store the new value for future comparisons.
        element.dataset.repeat = element.value;
        let option = element.querySelector('option[value=""]');
        let newLabels = false;
        if (!postRepeat && element.value) {
          // Recurring enabled, use selected labels.
          newLabels = selectedLabels;
        } else if (postRepeat && !element.value) {
          // Recurring disabled, use empty labels.
          newLabels = repeatLabels;
        }
        if (newLabels) {
          // Labels set, update appropriately.
          Object.entries(newLabels).forEach((entry) => {
            const [value, label] = entry;
            option = element.querySelector(`option[value="${value}"]`);
            if (option) {
              option.text = label;
            }
          });
        }
      }

      function setRepeatLabels(element) {
        Array.from(element.options).forEach(function (optionElement) {
          if (optionElement.value) {
            repeatLabels[optionElement.value] = optionElement.text;
          }
        });
        if (element.value) {
          updateRepeatLabels(element);
        }
      }

      // Manipulate the labels for BYDAY checkboxes.
      once(
        'smartDateRecurByDay',
        '.smartdate--widget .byday-checkboxes label',
        context,
      ).forEach(function (element) {
        element.title = element.textContent;
        element.tabIndex = 0;
        // Check the input on space bar or return.
        element.addEventListener(
          'keydown',
          function (event) {
            if (event.keyCode === 13 || event.keyCode === 32) {
              element.previousElementSibling.click();
              event.preventDefault();
            }
          },
          false,
        );
      });

      once(
        'smartDateRecurAllDay',
        '.smartdate--widget .allday',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            toggleMinutesHours(element);
          },
          false,
        );
      });

      // Manipulate the labels for BYHOUR and BYMINUTE checkboxes.
      once(
        'smartDateRecurHoursMinutes',
        '.smart-date--minutes input, .smart-date--hours input',
        context,
      ).forEach(function (element) {
        element.tabIndex = 0;
      });

      // special handler for duration updates
      once(
        'smartDateRecurDuration',
        '.smartdate--widget select.field-duration',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            durationToMinutes(element);
          },
          false,
        );
      });

      once(
        'smartDateRecurRepeat',
        '.smartdate--widget select.recur-repeat',
        context,
      ).forEach(function (element) {
        setDataFreq(element);
        setRepeatLabels(element);
        element.addEventListener(
          'change',
          function () {
            updateInterval(element);
            updateRepeatLabels(element);
          },
          false,
        );
      });

      once(
        'smartDateRecurDuration',
        '.smartdate--widget .time-end',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            durationToMinutes(element);
          },
          false,
        );
      });
    },
  };
})(Drupal, drupalSettings, once);
