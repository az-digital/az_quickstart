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
   *
   * @return {string[]}
   *   Selected date values for Vanilla Calendar Pro.
   */
  const getSelectedDatesFromInputs = (begin, end) => {
    const selectedDates = [];
    const beginDate = begin.value.trim();
    const endDate = end.value.trim();
    if (beginDate && endDate) {
      selectedDates.push(`${beginDate}:${endDate}`);
    }
    else if (beginDate) {
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

        const selectedDates = getSelectedDatesFromInputs(begin, end);

        const calendar = new VanillaCalendarPro.Calendar(begin, {
          inputMode: true,
          selectedTheme: 'light',
          themeAttrDetect: false,
          selectionDatesMode: 'multiple-ranged',
          enableEdgeDatesOnly: true,
          selectedDates,
          onChangeToInput(self) {
            const values =
              datePickerIntegration.getNormalizedSelectedDates(self).slice(0, 2);
            begin.value = values[0] || '';
            end.value = values[1] || '';

            if (values.length === 2) {
              self.hide();
            }
          },
        });
        calendar.init();

        datePickerIntegration.bindCalendarOpenHandlers(end, () => {
          calendar.show();
        });
      });
    },
  };
})(Drupal, once);
