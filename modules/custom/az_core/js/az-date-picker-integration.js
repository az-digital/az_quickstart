/**
 * @file
 * Shared integration helpers for Vanilla Calendar Pro.
 */

/* global VanillaCalendarProUtils */

((Drupal, window) => {
  const OPEN_KEYS = new Set(['Enter', 'ArrowDown', ' ']);

  /**
   * Adds consistent mouse and keyboard open handlers to an input element.
   *
   * @param {HTMLInputElement} element
   *   Input element that should open a calendar.
   * @param {Function} openCalendar
   *   Callback that opens the calendar instance.
   */
  const bindCalendarOpenHandlers = (element, openCalendar) => {
    if (!element || typeof openCalendar !== 'function') {
      return;
    }

    element.addEventListener('mousedown', (event) => {
      event.preventDefault();
      openCalendar();
    });

    element.addEventListener('focus', () => {
      openCalendar();
    });

    element.addEventListener('keydown', (event) => {
      if (OPEN_KEYS.has(event.key)) {
        event.preventDefault();
        openCalendar();
      }
    });
  };

  /**
   * Reads selected dates from calendar state and returns normalized values.
   *
   * @param {Object} calendarInstance
   *   Vanilla Calendar Pro instance callback context.
   *
   * @return {string[]}
   *   Unique normalized dates in Y-m-d format.
   */
  const getNormalizedSelectedDates = (calendarInstance) => {
    if (!window.VanillaCalendarProUtils || !calendarInstance) {
      return [];
    }

    const selectedValues =
      (calendarInstance.context && calendarInstance.context.selectedDates) ||
      calendarInstance.selectedDates ||
      [];

    return [...new Set(VanillaCalendarProUtils.parseDates(selectedValues))];
  };

  Drupal.azCore = Drupal.azCore || {};
  Drupal.azCore.datePickerIntegration = {
    bindCalendarOpenHandlers,
    getNormalizedSelectedDates,
  };
})(Drupal, window);
