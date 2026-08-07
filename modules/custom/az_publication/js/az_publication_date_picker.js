/**
 * @file
 * Publication date picker behavior.
 */

/* global VanillaCalendarPro */

((Drupal, once) => {
  Drupal.behaviors.datetimeTweaksDefaultDate = {
    attach(context) {
      const datePickerIntegration = Drupal.azCore?.datePickerIntegration;

      if (!window.VanillaCalendarPro || !datePickerIntegration) {
        return;
      }

      const elements = once(
        'azpublicationdate',
        'input.az-publication-date-picker',
        context,
      );

      elements.forEach((element) => {
        const dateFormat = element.dataset.drupalDateFormat || 'Y-m-d';
        const components = dateFormat.split('-').filter((part) => part !== '');
        const datePartsCount = Math.min(Math.max(components.length, 1), 3);
        const mode = element.dataset.azPublicationDateMode || 'default';

        // Keep user-visible values aligned to the selected granularity.
        const value = String(element.value || '').trim();
        let selectedDate = null;
        if (value) {
          selectedDate = datePickerIntegration.normalizeToFullDate(value);
          if (selectedDate) {
            element.value = datePickerIntegration.trimDateToPartsCount(
              selectedDate,
              datePartsCount,
            );
          } else {
            // Do not seed calendar state with invalid pre-existing text.
            element.value = '';
          }
        }

        const writeValueFromCalendar = (self) => {
          if (!self.context.inputElement) {
            return;
          }

          let selected = null;

          // Month/year modes are view-driven; selectedDates can remain stale.
          if (mode === 'month' || mode === 'year') {
            const year = self.context.selectedYear;
            const monthIndex = self.context.selectedMonth;
            if (Number.isInteger(year) && Number.isInteger(monthIndex)) {
              const month =
                mode === 'month'
                  ? String(monthIndex + 1).padStart(2, '0')
                  : '01';
              selected = `${year}-${month}-01`;
            }
          } else {
            [selected] = datePickerIntegration.getNormalizedSelectedDates(self);
          }

          if (selected) {
            self.context.inputElement.value =
              datePickerIntegration.trimDateToPartsCount(
                selected,
                datePartsCount,
              );
            self.hide();
          } else {
            self.context.inputElement.value = '';
          }
        };

        let calendarTabHandler = null;
        let suppressOnHideFocus = false;

        const config = {
          inputMode: true,
          openOnFocus: false,
          positionToInput: ['bottom', 'left'],
          selectedTheme: 'light',
          themeAttrDetect: false,
          selectionDatesMode: 'single',
          type: mode,
          enableJumpToSelectedDate: true,
          onChangeToInput: writeValueFromCalendar,
          onClickMonth(self) {
            requestAnimationFrame(() => writeValueFromCalendar(self));
          },
          onClickYear(self) {
            requestAnimationFrame(() => writeValueFromCalendar(self));
          },
          onShow(self) {
            const calendarEl = self.context.mainElement;
            const focusAnchor = self.context.inputElement;
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
              // onHide fires synchronously from hide(), restoring focus to input.
              self.hide();
              // Move focus in natural tab order relative to the input anchor.
              datePickerIntegration.focusRelativeToAnchor(
                calendarEl,
                focusAnchor,
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
            // Restore focus after close unless focus was moved explicitly.
            if (suppressOnHideFocus) {
              suppressOnHideFocus = false;
            } else if (self.context.inputElement) {
              self.context.inputElement.focus();
            }
          },
        };

        if (selectedDate) {
          config.selectedDates = [selectedDate];
        }

        const calendar = new VanillaCalendarPro.Calendar(element, config);
        calendar.init();

        // Allow direct text editing: silently discard invalid user input.
        element.addEventListener('change', () => {
          const userValue = String(element.value || '').trim();
          const normalized =
            datePickerIntegration.normalizeToFullDate(userValue);

          if (!userValue) {
            calendar.set(
              { selectedDates: [] },
              { dates: true, year: true, month: true },
            );
            return;
          }

          if (normalized) {
            element.value = datePickerIntegration.trimDateToPartsCount(
              normalized,
              datePartsCount,
            );
            calendar.set(
              { selectedDates: [normalized] },
              { dates: true, year: true, month: true },
            );
            return;
          }

          element.value = '';
          calendar.set(
            { selectedDates: [] },
            { dates: true, year: true, month: true },
          );
        });

        let firstOpen = true;

        const openCalendar = () => {
          const { scrollX, scrollY } = window;
          calendar.show();

          // After date-type AJAX rebuilds, first input-mode open can jump the
          // viewport. Restore scroll once, then allow normal behavior.
          if (firstOpen) {
            requestAnimationFrame(() => {
              window.scrollTo(scrollX, scrollY);
            });
            firstOpen = false;
          }
        };

        datePickerIntegration.bindCalendarOpenHandlers(element, openCalendar);
      });
    },
  };
})(Drupal, once);
