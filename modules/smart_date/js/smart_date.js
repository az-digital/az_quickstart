(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.smartDate = {
    attach(context, settings) {
      function pad(str, max) {
        str = str.toString();
        return str.length < max ? pad(`0${str}`, max) : str;
      }

      function parentDisplay(element) {
        element.parentElement.style.display = '';
      }

      function parentNoDisplay(element) {
        element.parentElement.style.display = 'none';
      }

      function durationClick(element) {
        const wrapper = element.closest('.smartdate--widget');
        const durationSelect = wrapper.querySelector('select.field-duration');
        const duration = element.dataset.value;
        // Update the select to show the appropriate value.
        if (
          durationSelect.querySelectorAll(`option[value="${duration}"]`)
            .length !== 0
        ) {
          durationSelect.value = duration;
          const e = new Event('change');
          durationSelect.dispatchEvent(e);
        }
        /* eslint-disable-next-line no-use-before-define */
        convertDuration(wrapper);
        const endDate = wrapper.querySelector('.time-end.form-time');
        endDate.focus();
      }

      function removeDurationList(wrapper) {
        const DurationList = wrapper.querySelectorAll(
          '.smart-date--duration-list',
        );
        if (DurationList) {
          DurationList.forEach((element) => {
            element.remove();
          });
          const endDate = wrapper.querySelector('.time-end.form-time');
          endDate.setAttribute('aria-expanded', 'false');
        }
      }

      function convertDuration(wrapper) {
        const durationId = `${wrapper.id}--duration-list`;
        const durationSelect = wrapper.querySelector('select.field-duration');
        const startTime = wrapper.querySelector('.time-start.form-time').value;
        const startDate = wrapper.querySelector('.time-start.form-date').value;
        const end = new Date(Date.parse(startDate));
        const startArray = startTime.split(':');
        const endDate = wrapper.querySelector('.time-end.form-time');
        // Add accessibility attributes.
        endDate.setAttribute('role', 'combobox');
        endDate.setAttribute('aria-autocomplete', 'list');
        endDate.setAttribute('aria-haspopup', 'true');
        endDate.setAttribute('aria-expanded', 'true');
        endDate.setAttribute('aria-controls', durationId);
        endDate.setAttribute('aria-owns', durationId);
        endDate.setAttribute('autocomplete', 'off');
        const durationList = document.createElement('ul');
        durationList.tabIndex = -1;
        durationList.setAttribute('role', 'listbox');
        durationList.id = durationId;
        for (let i = 0; i < durationSelect.options.length; i++) {
          if (durationSelect.options[i].value === 'custom') {
            continue;
          }
          const durationOption = document.createElement('li');
          durationOption.tabIndex = -1;
          durationOption.setAttribute('role', 'option');
          end.setHours(startArray[0]);
          end.setMinutes(
            parseInt(startArray[1], 10) +
              parseInt(durationSelect.options[i].value, 10),
          );

          // Update End Time input.
          const endVal = end.toLocaleTimeString([], { timeStyle: 'short' });
          const timeNode = document.createTextNode(endVal);
          const timeSpan = document.createElement('span');
          timeSpan.appendChild(timeNode);
          timeSpan.classList.add('smart-date--duration-list--time');
          if (endVal.length > 5) {
            timeSpan.classList.add('12h-format');
          }
          durationOption.appendChild(timeSpan);

          const durNode = document.createTextNode(
            durationSelect.options[i].text,
          );
          const durSpan = document.createElement('span');
          durSpan.appendChild(durNode);
          durSpan.classList.add('smart-date--duration-list--duration');
          durationOption.appendChild(durSpan);

          durationOption.dataset.value = durationSelect.options[i].value;
          if (durationSelect.options[i].value === durationSelect.value) {
            durationOption.classList.add('active');
            durationOption.setAttribute('aria-selected', 'true');
          }
          durationOption.addEventListener(
            'click',
            function () {
              durationClick(durationOption);
            },
            false,
          );
          durationList.appendChild(durationOption);
        }
        durationList.classList.add('smart-date--duration-list');
        removeDurationList(wrapper);
        endDate.parentElement.appendChild(durationList);
      }

      function durationKeys(event) {
        const keyName = event.key;
        if (keyName !== 'ArrowUp' && keyName !== 'ArrowDown') {
          return;
        }
        const wrapper = event.srcElement.closest('.smartdate--widget');
        const DurationList = wrapper.querySelectorAll(
          '.smart-date--duration-list li',
        );
        if (!DurationList) {
          return;
        }
        const values = [];
        const links = [];
        let active = null;
        // Create arrays for the values and links, with matching keys.
        DurationList.forEach((element) => {
          if (active === null && element.classList.contains('active')) {
            active = element.dataset.value;
            element.classList.remove('active');
          }
          values.push(element.dataset.value);
          links.push(element);
        });
        let activeIndex = values.findIndex((x) => x === active);
        const max = values.length;
        // Move the active duration.
        if (keyName === 'ArrowUp') {
          activeIndex--;
          if (activeIndex < 0) {
            activeIndex = max - 1;
          }
        } else {
          activeIndex++;
          if (activeIndex >= max) {
            activeIndex = 0;
          }
        }
        const e = new Event('click');
        links[activeIndex].dispatchEvent(e);
      }

      function checkEndDate(wrapper) {
        const startDate = wrapper.querySelector('.time-start.form-date');
        const endDate = wrapper.querySelector('.time-end.form-date');
        const hideMe = Number(endDate.dataset.hide);
        const allday = wrapper.querySelector('.allday');
        if (
          hideMe === 1 &&
          endDate.value === startDate.value &&
          (!allday || allday.checked === false)
        ) {
          endDate.style.visibility = 'hidden';
        } else {
          endDate.style.visibility = 'visible';
        }
      }

      function hideLabels(wrapper, hide = true) {
        let displayVal = 'none';
        if (!hide) {
          displayVal = '';
        }
        wrapper
          .querySelectorAll('.form-type--date label.form-item__label')
          .forEach(function (label) {
            label.style.display = displayVal;
          });
      }

      function calcDuration(wrapper) {
        const startTime = wrapper.querySelector('.time-start.form-time').value;
        const startDate = wrapper.querySelector('.time-start.form-date').value;
        const endTime = wrapper.querySelector('.time-end.form-time').value;
        const endDate = wrapper.querySelector('.time-end.form-date').value;
        if (!startTime || !startDate || !endTime || !endDate) {
          return 0;
        }
        // Split times into hours and minutes.
        const startArray = startTime.split(':');
        const endArray = endTime.split(':');
        let duration = 0;
        if (startDate !== endDate) {
          // The range spans more than one day, so use Date objects to calculate duration.
          const start = new Date(startDate);
          start.setHours(startArray[0]);
          start.setMinutes(parseInt(startArray[1], 10));
          const end = new Date(endDate);
          end.setHours(endArray[0]);
          end.setMinutes(parseInt(endArray[1], 10));
          duration = (end.getTime() - start.getTime()) / (60 * 1000);
        } else {
          // Convert to minutes and get the difference.
          duration =
            (parseInt(endArray[0], 10) - parseInt(startArray[0], 10)) * 60 +
            (parseInt(endArray[1], 10) - parseInt(startArray[1], 10));
        }
        return duration;
      }

      function setEndDate(element) {
        const wrapper = element.closest('.smartdate--widget');
        const durationSelect = wrapper.querySelector('select.field-duration');
        let duration = false;
        if (durationSelect.value === 'custom') {
          duration = parseInt(durationSelect.dataset.duration, 10);
        } else {
          duration = parseInt(durationSelect.value, 10);
        }
        if (duration === false || duration === 'custom') {
          return;
        }

        const startDate = wrapper.querySelector('.time-start.form-date').value;
        if (!startDate) {
          return;
        }
        const startTime = wrapper.querySelector('.time-start.form-time').value;
        if (!startTime && startDate) {
          // If only the start date has been specified update only the end date.
          wrapper.querySelector('.time-end.form-date').value = startDate;
          return;
        }

        const startArray = startTime.split(':');
        let end = new Date();
        if (startDate.length) {
          // Use Date objects to automatically roll over days when necessary.
          // ISO 8601 string get encoded as UTC so add the timezone offset.
          end = new Date(Date.parse(startDate));
          const isIso8061 = startDate.match(/\d{4}-\d{2}-\d{2}/);
          if (isIso8061 && end.getTimezoneOffset() !== 0) {
            end.setMinutes(end.getMinutes() + end.getTimezoneOffset());
          }
        }

        // Calculate and set End Time only if All Day is not checked.
        if (
          !wrapper.querySelector('input.allday') ||
          wrapper.querySelector('input.allday').checked === false
        ) {
          end.setHours(startArray[0]);
          end.setMinutes(parseInt(startArray[1], 10) + duration);

          // Update End Time input.
          const endVal = `${pad(end.getHours(), 2)}:${pad(
            end.getMinutes(),
            2,
          )}`;
          wrapper.querySelector('.time-end.form-time').value = endVal;
        }

        // Update End Date input.
        const newEnd = `${end.getFullYear()}-${pad(
          end.getMonth() + 1,
          2,
        )}-${pad(end.getDate(), 2)}`;
        wrapper.querySelector('.time-end.form-date').value = newEnd;
        checkEndDate(wrapper);
      }

      function durationChanged(element) {
        const wrapper = element.closest('.smartdate--widget');
        if (element.value === 'custom') {
          // Reset end time and add focus.
          const wrapper = element.closest('fieldset');
          const endTime = wrapper.querySelector('.time-end.form-time');
          endTime.value = '';
          endTime.focus();
        } else {
          // Fire normal setEndDate().
          setEndDate(element);
        }
        checkEndDate(wrapper);
      }

      function setInitialDuration(element) {
        let duration = element.value;
        const wrapper = element.closest('.smartdate--widget');
        if (duration === 'custom') {
          duration = calcDuration(wrapper);
        } else if (+duration === 0) {
          // Call this to hide the end date and time.
          durationChanged(element);
        }
        // Store the numeric value in a property so it can be used programmatically.
        element.dataset.duration = duration;
        // Handle cases where only one non-custom value is allowed.
        if (element.options.length === 1 && duration !== 'custom') {
          if (+duration === 0) {
            // Hide the entire duration wrapper.
            parentNoDisplay(element);
            // Hide the end date and time.
            const endTimeInput = wrapper.querySelector('.time-end.form-time');
            const endDateInput = wrapper.querySelector('.time-end.form-date');
            const separator = wrapper.querySelector('.smartdate--separator');
            parentNoDisplay(endTimeInput);
            parentNoDisplay(endDateInput);
            hideLabels(wrapper);
            if (separator) {
              separator.style.display = 'none';
            }
          } else {
            // Append option label to field label and hide the select.
            const durationText = element.options[0].text;
            const label = element.parentElement.querySelectorAll('label');
            element.style.display = 'none';
            label[0].append(` ${durationText}`);
          }
        }
      }

      // Add/change inputs based on initial config.
      function augmentInputs(element) {
        // Add "All day checkbox" if config permits.
        const allday = element.dataset.allday;
        if (
          allday &&
          allday !== '0' &&
          (element.querySelectorAll('select [value="custom"]').length > 0 ||
            element.querySelectorAll('select [value="1439"]').length > 0)
        ) {
          // Create the input element.
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.classList.add('allday');

          // Create the label element.
          const label = document.createElement('label');
          label.classList.add('allday-label');
          // Insert the input into the label.
          label.appendChild(checkbox);
          label.appendChild(document.createTextNode(Drupal.t('All day')));

          element.parentElement.insertAdjacentElement('beforebegin', label);
        }
        // If a forced duration, make end date and time read only.
        if (element.querySelectorAll('select [value="custom"]').length === 0) {
          const fieldset = element.closest('fieldset');
          const endTimeInput = fieldset.querySelector('.time-end.form-time');
          const endDateInput = fieldset.querySelector('.time-end.form-date');
          endTimeInput.readOnly = true;
          endTimeInput.ariaReadOnly = true;
          endDateInput.readOnly = true;
          endDateInput.ariaReadOnly = true;
        }
        const wrapper = element.closest('.smartdate--widget');
        checkEndDate(wrapper);
      }

      function setDuration(element) {
        const wrapper = element.closest('.smartdate--widget');
        const duration = calcDuration(wrapper);
        if (+duration === 0) {
          return;
        }
        const durationSelect = wrapper.querySelector('select.field-duration');
        if (+durationSelect.dataset.overlay === 1) {
          convertDuration(wrapper);
        }
        // Store the numeric value in a property so it can be used programmatically.
        durationSelect.dataset.duration = duration;
        // Update the select to show the appropriate value.
        if (
          durationSelect.querySelectorAll(`option[value="${duration}"]`)
            .length !== 0
        ) {
          durationSelect.value = duration;
        } else {
          durationSelect.value = 'custom';
        }
      }

      function setAllDay(element) {
        const checkbox = element;
        const wrapper = checkbox.closest('.smartdate--widget');
        const startTime = wrapper.querySelector('.time-start.form-time');
        const endTime = wrapper.querySelector('.time-end.form-time');
        const duration = wrapper.querySelector('select.field-duration');
        const startDate = wrapper.querySelector('input.time-start.form-date');
        const endDate = wrapper.querySelector('input.time-end.form-date');
        // Set initial state of checkbox based on initial values.
        if (startTime.value === '00:00:00' && endTime.value === '23:59:00') {
          checkbox.checked = true;
          checkbox.dataset.duration = duration.dataset.default;
          parentNoDisplay(startTime);
          parentNoDisplay(endTime);
          hideLabels(wrapper);
          parentNoDisplay(duration);
        } else {
          checkbox.dataset.duration = duration.value;
        }
        if (
          startDate.value !== '' &&
          endDate.value !== '' &&
          checkbox.checked === true
        ) {
          duration.parentElement.style.visibility = 'hidden';
          if (+duration.dataset.overlay !== 1) {
            parentDisplay(duration);
          }
        }
      }

      function checkAllDay(element) {
        const checkbox = element;
        const wrapper = checkbox.closest('.smartdate--widget');
        const startTime = wrapper.querySelector('input.time-start.form-time');
        const endTime = wrapper.querySelector('.time-end.form-time');
        const duration = wrapper.querySelector('select.field-duration');
        const durationWrapper = duration.parentElement;

        if (checkbox.checked === true) {
          if (+checkbox.dataset.duration === 0) {
            const endDate = wrapper.querySelector('input.time-end.form-date');
            parentDisplay(endDate);
            const endDateLabel = wrapper.querySelector('.time-start + .label');
            if (endDateLabel) {
              endDateLabel.style.display = '';
            }
          }
          // Save the current start and endDate.
          checkbox.dataset.start = startTime.value;
          checkbox.dataset.end = endTime.value;
          checkbox.dataset.duration = duration.value;
          durationWrapper.style.visibility = 'hidden';
          // Set the duration to a corresponding value.
          if (
            duration.querySelectorAll('option[value="custom"]').length !== 0
          ) {
            duration.value = 'custom';
          } else if (
            duration.querySelectorAll('option[value="1439"]').length !== 0
          ) {
            duration.value = '1439';
          }
          // Set to all day $values and hide time elements.
          parentNoDisplay(startTime);
          startTime.value = '00:00';
          parentNoDisplay(endTime);
          endTime.value = '23:59';
          hideLabels(wrapper);
          // Force the end date visible.
          const endDate = wrapper.querySelector('.time-end.form-date');
          endDate.style.visibility = 'visible';
        } else {
          // Restore from data values.
          if (checkbox.dataset.start) {
            startTime.value = checkbox.dataset.start;
          } else {
            startTime.value = '';
          }
          if (checkbox.dataset.end) {
            endTime.value = checkbox.dataset.end;
          } else {
            endTime.value = '';
          }
          if (checkbox.dataset.duration || +checkbox.dataset.duration === 0) {
            duration.value = checkbox.dataset.duration;
          } else {
            duration.dataset.duration = checkbox.dataset.default;
          }
          if (!endTime.value) {
            setEndDate(startTime);
          }
          // Make time inputs visible.
          parentDisplay(startTime);
          parentDisplay(endTime);
          durationWrapper.style.visibility = 'visible';
          hideLabels(wrapper, false);
          if (duration.value === 0) {
            // Call this to hide the end date and time.
            durationChanged(duration);
          }
          checkEndDate(wrapper);
        }
      }

      function implementDurationOverlay() {
        once(
          'smartDateDurationList',
          '.smartdate--widget .time-end input[type="time"]',
          context,
        ).forEach(function (element) {
          const wrapper = element.closest('.smartdate--widget');
          wrapper.dataset.duration_wrapper = 1;
          element.addEventListener(
            'focus',
            function () {
              convertDuration(wrapper);
              element.addEventListener('keydown', durationKeys, false);
            },
            false,
          );
          element.addEventListener(
            'focusout',
            function () {
              const existingDurationList = wrapper.querySelectorAll(
                '.smart-date--duration-list',
              );
              if (existingDurationList) {
                existingDurationList.forEach((element) => {
                  // Pause before hiding to preserve focus after list clicks.
                  setTimeout(function () {
                    element.style.display = 'none';
                  }, 300);
                });
              }
              element.removeEventListener('keydown', durationKeys, false);
            },
            false,
          );
        });
      }

      once(
        'smartDateDuration',
        '.smartdate--widget select.field-duration',
        context,
      ).forEach(function (element) {
        setInitialDuration(element);
        augmentInputs(element);
        element.addEventListener(
          'change',
          function () {
            durationChanged(element);
          },
          false,
        );
        // If overlay enabled, make appropriate changes.
        if (element.dataset.overlay === 1 || element.dataset.overlay === '1') {
          parentNoDisplay(element);
          implementDurationOverlay();
        }
      });
      once('smartDateAllDay', '.allday', context).forEach(function (element) {
        setAllDay(element);
        element.addEventListener(
          'change',
          function () {
            checkAllDay(element);
          },
          false,
        );
      });
      once(
        'smartDateHideSeconds',
        '.smartdate--widget input[type="time"]',
        context,
      ).forEach(function (element) {
        element.step = 60;
        // For browsers that don't respect the step value, trim empty seconds.
        if (
          element.defaultValue &&
          element.defaultValue.substring(6, 8) === '00'
        ) {
          element.defaultValue = element.defaultValue.substring(0, 5);
        }
      });
      once(
        'smartDateStartChange',
        '.smartdate--widget .time-start input',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            setEndDate(element);
          },
          false,
        );
      });
      once(
        'smartDateEndChange',
        '.smartdate--widget .time-end',
        context,
      ).forEach(function (element) {
        element.addEventListener(
          'change',
          function () {
            setDuration(element);
          },
          false,
        );
      });
    },
  };
})(Drupal, drupalSettings, once);
