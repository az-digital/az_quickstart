(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.smartDate = {
    attach(context, settings) {
      // eslint-disable-next-line no-extend-native
      Date.prototype.addDays = function (days) {
        const newDate = this.getDate() + parseInt(days, 10);
        this.setDate(newDate);
      };

      function pad(str, max) {
        str = str.toString();
        return str.length < max ? pad(`0${str}`, max) : str;
      }

      function startChanged(element) {
        if (!element.value) {
          return;
        }
        const wrapper = element.closest('fieldset');
        const duration = element.dataset.duration;
        const startDate = element.value;
        const end = new Date(Date.parse(startDate));
        let newEnd = element.value;
        // Update end date if a duration is set.
        if (!Number.isNaN(duration) && duration > 0) {
          end.addDays(duration);
          // ISO 8601 string get encoded as UTC so add the timezone offset.
          const isIso8061 = startDate.match(/\d{4}-\d{2}-\d{2}/);
          if (isIso8061 && end.getTimezoneOffset() !== 0) {
            end.setMinutes(end.getMinutes() + end.getTimezoneOffset());
            newEnd = `${end.getFullYear()}-${pad(end.getMonth() + 1, 2)}-${pad(
              end.getDate(),
              2,
            )}`;
          } else {
            newEnd = end.toLocaleDateString();
          }
        }
        wrapper.querySelector('.time-end.form-date').value = newEnd;
      }

      function endChanged(element) {
        const wrapper = element.closest('fieldset');
        const start = wrapper.querySelector('.time-start.form-date');
        const end = element;
        const startDate = new Date(Date.parse(start.value));
        const endDate = new Date(Date.parse(end.value));
        // Update duration if a number can be determined.
        const duration = (endDate - startDate) / (1000 * 60 * 60 * 24);
        if (duration === 0 || duration > 0) {
          start.dataset.duration = duration;
        }
      }

      // Update the end values when the start is changed.
      once(
        'smartDateStartChange',
        '.smartdate--widget .time-start input',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            startChanged(element);
          },
          false,
        );
      });
      once(
        'smartDateEndChange',
        '.smartdate--widget .time-end input',
        context,
      ).forEach(function (element) {
        // Set initial duration.
        endChanged(element);
        // Update the duration when end changed.
        element.addEventListener(
          'change',
          function () {
            endChanged(element);
          },
          false,
        );
      });
    },
  };
})(Drupal, drupalSettings, once);
