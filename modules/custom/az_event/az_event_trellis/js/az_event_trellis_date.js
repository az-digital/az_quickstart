/**
 * @file
 * Trellis date picker behavior.
 */

/* global VanillaCalendarPro */

((Drupal, once) => {
  /**
   * Normalizes begin/end input values to a calendar selectedDates array.
   *
   * @param {HTMLInputElement} begin
   *   Begin date input element.
   * @param {HTMLInputElement} end
   *   End date input element.
   * @param {Object} datePickerIntegration
   *   Shared date picker integration helpers.
   *
   * @return {string[]}
   *   Selected date values for Vanilla Calendar Pro.
   */
  const getSelectedDatesFromInputs = (begin, end, datePickerIntegration) => {
    const selectedDates = [];
    const beginDate = datePickerIntegration.normalizeIsoDate(begin.value);
    const endDate = datePickerIntegration.normalizeIsoDate(end.value);
    if (beginDate && endDate) {
      selectedDates.push(`${beginDate}:${endDate}`);
    } else if (beginDate) {
      selectedDates.push(beginDate);
    }
    return selectedDates;
  };

  Drupal.behaviors.trellisDatePicker = {
    attach(context) {
      const datePickerIntegration = Drupal.azCore?.datePickerIntegration;

      const elements = once('aztrellisdate', '.az-trellis-daterange', context);
      elements.forEach((element) => {
        const begin = element;
        const id = element.dataset.azTrellisDaterangeEnd;
        const end = document.getElementById(id);

        if (!end || !window.VanillaCalendarPro || !datePickerIntegration) {
          return;
        }

        const selectedDates = getSelectedDatesFromInputs(
          begin,
          end,
          datePickerIntegration,
        );

        let calendarTabHandler = null;
        let suppressOnHideFocus = false;

        const config = {
          inputMode: true,
          openOnFocus: false,
          selectedTheme: 'light',
          themeAttrDetect: false,
          selectionDatesMode: 'multiple-ranged',
          enableEdgeDatesOnly: true,
          enableJumpToSelectedDate: true,
          selectedDates,
          onChangeToInput(self) {
            const values = datePickerIntegration
              .getNormalizedSelectedDates(self)
              .slice(0, 2);
            begin.value = values[0] || '';
            end.value = values[1] || '';

            if (values.length === 2) {
              self.hide();
            }
          },
          onShow(self) {
            const calendarEl = self.context.mainElement;
            calendarTabHandler = (event) => {
              if (event.key !== 'Tab') return;

              if (
                !datePickerIntegration.shouldExitCalendarOnTab(
                  event,
                  calendarEl,
                )
              ) {
                return;
              }

              event.preventDefault();
              suppressOnHideFocus = true;
              // onHide fires synchronously, resets inputElement to begin and focuses it.
              self.hide();
              // Move focus in natural tab order around the begin/end pair.
              datePickerIntegration.focusRelativeToAnchor(
                calendarEl,
                event.shiftKey ? begin : end,
                event.shiftKey ? 'previous' : 'next',
              );
            };
            calendarEl.addEventListener('keydown', calendarTabHandler);
          },
          onHide(self) {
            if (calendarTabHandler) {
              self.context.mainElement.removeEventListener(
                'keydown',
                calendarTabHandler,
              );
              calendarTabHandler = null;
            }
            // Reset to begin input after closing so default input-mode behavior
            // stays anchored to the primary field.
            self.context.inputElement = begin;
            if (suppressOnHideFocus) {
              suppressOnHideFocus = false;
            } else {
              begin.focus();
            }
          },
        };

        const calendar = new VanillaCalendarPro.Calendar(begin, config);
        calendar.init();

        // Enable keyboard opening for begin input (Enter/Arrow/Space).
        datePickerIntegration.bindCalendarOpenHandlers(begin, () => {
          calendar.context.inputElement = begin;
          calendar.show();
        });

        const syncFromInputs = () => {
          const beginRaw = String(begin.value || '').trim();
          const endRaw = String(end.value || '').trim();
          let beginValue = datePickerIntegration.normalizeIsoDate(beginRaw);
          let endValue = datePickerIntegration.normalizeIsoDate(endRaw);

          // Silently discard invalid manual values.
          if (beginRaw && !beginValue) {
            begin.value = '';
          }
          if (endRaw && !endValue) {
            end.value = '';
          }

          beginValue = datePickerIntegration.normalizeIsoDate(begin.value);
          endValue = datePickerIntegration.normalizeIsoDate(end.value);

          const newDates = [];
          if (beginValue && endValue) {
            begin.value = beginValue;
            end.value = endValue;
            newDates.push(`${beginValue}:${endValue}`);
          } else if (beginValue) {
            begin.value = beginValue;
            newDates.push(beginValue);
          }

          calendar.set(
            { selectedDates: newDates },
            { dates: true, year: true, month: true },
          );
        };

        // Allow direct text editing on begin/end inputs.
        begin.addEventListener('change', syncFromInputs);
        end.addEventListener('change', syncFromInputs);

        let endOpenQueued = false;
        datePickerIntegration.bindCalendarOpenHandlers(end, () => {
          if (endOpenQueued) {
            return;
          }

          // Treat End as the active input while opening from End so this click
          // is not interpreted as an outside click by input-mode handlers.
          calendar.context.inputElement = end;
          endOpenQueued = true;
          requestAnimationFrame(() => {
            calendar.show();
            endOpenQueued = false;
          });
        });
      });
    },
  };
})(Drupal, once);
