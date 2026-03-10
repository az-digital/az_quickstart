/**
 * @file
 * Publication date picker behavior.
 */

/* global VanillaCalendarPro */

((Drupal, once) => {
  /**
   * Pads month/day values for Drupal datetime parsing.
   *
   * @param {string} value
   *   Raw input value.
   *
   * @return {string}
   *   A full date in Y-m-d format.
   */
  const normalizeToFullDate = (value) => {
    const parts = String(value || '')
      .split('-')
      .filter((part) => part !== '');
    while (parts.length < 3) {
      parts.push('01');
    }
    return parts.slice(0, 3).join('-');
  };

  /**
   * Trims a normalized Y-m-d date to match publication date type precision.
   *
   * @param {string} value
   *   Date string in Y-m-d format.
   * @param {string} mode
   *   Publication date mode (`year`, `month`, or `default`).
   *
   * @return {string}
   *   A date string formatted to the selected precision.
   */
  const trimDateForMode = (value, mode) => {
    const fullDate = normalizeToFullDate(value);
    const parts = fullDate.split('-');
    if (mode === 'year') {
      return parts[0];
    }
    if (mode === 'month') {
      return parts.slice(0, 2).join('-');
    }
    return parts.slice(0, 3).join('-');
  };

  Drupal.behaviors.datetimeTweaksDefaultDate = {
    attach(context) {
      const datePickerIntegration = Drupal.azCore?.datePickerIntegration;

      if (!window.VanillaCalendarPro || !datePickerIntegration) {
        return;
      }

      const elements = once(
        'azpublicationdate',
        '.az-publication-date-picker input, input.az-publication-date-picker',
        context,
      );

      elements.forEach((element) => {
        const mode = element.dataset.azPublicationDateMode || 'default';

        // Keep user-visible values aligned to the selected granularity.
        const value = String(element.value || '').trim();
        let selectedDate = null;
        if (value) {
          selectedDate = normalizeToFullDate(value);
          element.value = trimDateForMode(selectedDate, mode);
        }

        const syncValueFromCalendar = (self) => {
          if (!self.context.inputElement) {
            return;
          }

          let selected = datePickerIntegration.getNormalizedSelectedDates(self)[0];

          if (!selected) {
            const calendarContext = self.context || {};
            const year = calendarContext.selectedYear;
            const monthIndex = calendarContext.selectedMonth;
            if (Number.isInteger(year)) {
              const month = Number.isInteger(monthIndex)
                ? String(monthIndex + 1).padStart(2, '0')
                : '01';
              selected = `${year}-${month}-01`;
            }
          }

          if (selected) {
            self.context.inputElement.value = trimDateForMode(selected, mode);
            self.context.inputElement.dispatchEvent(
              new Event('change', { bubbles: true }),
            );
            self.hide();
          } else {
            self.context.inputElement.value = '';
          }
        };

        const config = {
          inputMode: true,
          openOnFocus: false,
          positionToInput: ['bottom', 'left'],
          selectedTheme: 'light',
          themeAttrDetect: false,
          selectionDatesMode: 'single',
          type: mode,
          onChangeToInput: syncValueFromCalendar,
          onClickMonth(self) {
            requestAnimationFrame(() => {
              syncValueFromCalendar(self);
            });
          },
          onClickYear(self) {
            requestAnimationFrame(() => {
              syncValueFromCalendar(self);
            });
          },
        };

        if (selectedDate) {
          config.selectedDates = [selectedDate];
        }

        const calendar = new VanillaCalendarPro.Calendar(element, config);
        calendar.init();

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
