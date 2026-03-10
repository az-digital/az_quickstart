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
   * Trims a normalized Y-m-d date to requested precision.
   *
   * @param {string} value
   *   Date string in Y-m-d format.
   * @param {number} partsCount
   *   Number of date components to keep.
   *
   * @return {string}
   *   A date string formatted to the selected precision.
   */
  const trimDateToPartsCount = (value, partsCount) => {
    return normalizeToFullDate(value).split('-').slice(0, partsCount).join('-');
  };

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
        let value = String(element.value || '').trim();
        let selectedDate = null;
        if (value) {
          value = value.split('-').filter((part) => part !== '');
          while (value.length < datePartsCount) {
            value.push('01');
          }
          value = value.slice(0, datePartsCount).join('-');
          element.value = value;
          selectedDate = normalizeToFullDate(value);
        }

        const writeValueFromCalendar = (self) => {
          if (!self.context.inputElement) {
            return;
          }

          let selected =
            datePickerIntegration.getNormalizedSelectedDates(self)[0];

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
            self.context.inputElement.value = trimDateToPartsCount(
              selected,
              datePartsCount,
            );
            self.hide();
          } else {
            self.context.inputElement.value = '';
          }
        };

        // onClickMonth/onClickYear fire before calendar state updates; defer
        // via rAF so selectedYear/selectedMonth are committed before we read them.
        const writeValueFromViewSelection = (self) => {
          requestAnimationFrame(() => {
            writeValueFromCalendar(self);
          });
        };

        const config = {
          inputMode: true,
          openOnFocus: false,
          positionToInput: ['bottom', 'left'],
          selectedTheme: 'light',
          themeAttrDetect: false,
          selectionDatesMode: 'single',
          type: mode,
          onChangeToInput: writeValueFromCalendar,
          onClickMonth: writeValueFromViewSelection,
          onClickYear: writeValueFromViewSelection,
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
