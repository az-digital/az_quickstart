/**
 * @file
 * Shared integration helpers for Vanilla Calendar Pro.
 */

/* global VanillaCalendarProUtils */

((Drupal, window) => {
  const OPEN_KEYS = new Set(['Enter', 'ArrowDown', ' ']);
  const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

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

  /**
   * Validates calendar date components and returns normalized Y-m-d.
   *
   * @param {number} year
   *   Year component.
   * @param {number} month
   *   Month component (1-12).
   * @param {number} day
   *   Day component (1-31).
   *
   * @return {string|null}
   *   Normalized date or NULL when invalid.
   */
  const toNormalizedIsoDate = (year, month, day) => {
    if (
      !Number.isInteger(year) ||
      !Number.isInteger(month) ||
      !Number.isInteger(day) ||
      month < 1 ||
      month > 12 ||
      day < 1 ||
      day > 31
    ) {
      return null;
    }

    const normalized = new Date(Date.UTC(year, month - 1, day));
    if (
      normalized.getUTCFullYear() !== year ||
      normalized.getUTCMonth() !== month - 1 ||
      normalized.getUTCDate() !== day
    ) {
      return null;
    }

    return [
      String(year).padStart(4, '0'),
      String(month).padStart(2, '0'),
      String(day).padStart(2, '0'),
    ].join('-');
  };

  /**
   * Normalizes and validates a Y-m-d value (allows unpadded month/day).
   *
   * @param {string} value
   *   Raw date value.
   *
   * @return {string|null}
   *   Normalized Y-m-d date or NULL when invalid.
   */
  const normalizeIsoDate = (value) => {
    const match = String(value || '')
      .trim()
      .match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);

    if (!match) {
      return null;
    }

    return toNormalizedIsoDate(
      Number(match[1]),
      Number(match[2]),
      Number(match[3]),
    );
  };

  /**
   * Normalizes and validates a partial date value.
   *
   * Accepts Y, Y-m, or Y-m-d and returns Y-m-d.
   *
   * @param {string} value
   *   Raw date value.
   *
   * @return {string|null}
   *   Normalized Y-m-d date or NULL when invalid.
   */
  const normalizeToFullDate = (value) => {
    const match = String(value || '')
      .trim()
      .match(/^(\d{4})(?:-(\d{1,2}))?(?:-(\d{1,2}))?$/);

    if (!match) {
      return null;
    }

    return toNormalizedIsoDate(
      Number(match[1]),
      match[2] ? Number(match[2]) : 1,
      match[3] ? Number(match[3]) : 1,
    );
  };

  /**
   * Trims a normalized Y-m-d date to requested precision.
   *
   * @param {string} value
   *   Date-like value.
   * @param {number} partsCount
   *   Number of parts to keep (1-3).
   *
   * @return {string}
   *   Trimmed date or empty string when invalid.
   */
  const trimDateToPartsCount = (value, partsCount) => {
    const normalized = normalizeToFullDate(value);
    return normalized
      ? normalized.split('-').slice(0, partsCount).join('-')
      : '';
  };

  /**
   * Returns visible/focusable elements for a scope.
   *
   * @param {ParentNode} scope
   *   Scope to query for focusable elements.
   * @param {HTMLElement} [excludeWithin]
   *   Optional element whose descendants should be excluded.
   *
   * @return {HTMLElement[]}
   *   Focusable elements in DOM order.
   */
  const getFocusableElements = (scope, excludeWithin) => {
    const queryScope = scope || document;

    return Array.from(queryScope.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
      (el) =>
        (!excludeWithin || !excludeWithin.contains(el)) &&
        el.offsetParent !== null &&
        el.tabIndex >= 0 &&
        !el.hasAttribute('disabled') &&
        el.getAttribute('aria-disabled') !== 'true',
    );
  };

  /**
   * Returns whether a Tab key event should exit a calendar popup.
   *
   * Exit is triggered only at boundaries:
   * - Shift+Tab on first focusable element
   * - Tab on last focusable element
   *
   * @param {KeyboardEvent} event
   *   Keydown event.
   * @param {HTMLElement} calendarElement
   *   Calendar root element.
   *
   * @return {boolean}
   *   TRUE if the event is a boundary Tab that should close the popup.
   */
  const shouldExitCalendarOnTab = (event, calendarElement) => {
    if (!event || event.key !== 'Tab' || !calendarElement) {
      return false;
    }

    const focusableInCalendar = getFocusableElements(calendarElement);
    if (!focusableInCalendar.length) {
      return true;
    }

    const active = document.activeElement;
    const first = focusableInCalendar[0];
    const last = focusableInCalendar[focusableInCalendar.length - 1];

    return event.shiftKey ? active === first : active === last;
  };

  /**
   * Moves focus relative to an anchor element while excluding calendar content.
   *
   * @param {HTMLElement} calendarElement
   *   Calendar root element to exclude from page focus traversal.
   * @param {HTMLElement} anchorElement
   *   Element used as the position reference in tab order.
   * @param {'next'|'previous'} direction
   *   Direction to move focus from the anchor.
   *
   * @return {boolean}
   *   TRUE when focus moved successfully.
   */
  const focusRelativeToAnchor = (calendarElement, anchorElement, direction) => {
    if (!calendarElement || !anchorElement) {
      return false;
    }

    const focusable = getFocusableElements(document, calendarElement);

    const idx = focusable.indexOf(anchorElement);

    if (direction === 'previous' && idx > 0 && focusable[idx - 1]) {
      focusable[idx - 1].focus();
      return true;
    }

    if (direction === 'next' && idx >= 0 && focusable[idx + 1]) {
      focusable[idx + 1].focus();
      return true;
    }

    return false;
  };

  Drupal.azCore = Drupal.azCore || {};
  Drupal.azCore.datePickerIntegration = {
    bindCalendarOpenHandlers,
    getNormalizedSelectedDates,
    normalizeIsoDate,
    normalizeToFullDate,
    trimDateToPartsCount,
    getFocusableElements,
    shouldExitCalendarOnTab,
    focusRelativeToAnchor,
  };
})(Drupal, window);
