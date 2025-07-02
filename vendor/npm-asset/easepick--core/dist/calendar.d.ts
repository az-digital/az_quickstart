import { Core } from './core';
import { DateTime } from '@easepick/datetime';
export default class Calendar {
    picker: Core;
    constructor(picker: Core);
    /**
     * Render transferred date and view
     *
     * @param date
     * @param view
     */
    render(date: DateTime, view: string): void;
    /**
     * Function for `Container` view
     *
     * @param date
     */
    getContainerView(date: DateTime): void;
    /**
     * Function for `Header` view
     *
     * @param date
     */
    getHeaderView(date: DateTime): void;
    /**
     * Function for `Main` view
     *
     * @param date
     */
    getMainView(date: DateTime): void;
    /**
     * Function for `Footer` view
     *
     * @param date
     */
    getFooterView(date: DateTime): void;
    /**
     * Function for `CalendarHeader` view
     *
     * @param date
     * @returns HTMLElement
     */
    getCalendarHeaderView(date: DateTime): HTMLElement;
    /**
     * Function for `CalendarDayNames` view
     *
     * @param date
     * @returns HTMLElement
     */
    getCalendarDayNamesView(): HTMLElement;
    /**
     * Function for `CalendarDays` view
     *
     * @param date
     * @returns HTMLElement
     */
    getCalendarDaysView(date: DateTime): HTMLElement;
    /**
     * Function for `CalendarDay` view
     *
     * @param date
     * @returns HTMLElement
     */
    getCalendarDayView(date: DateTime): HTMLElement;
    /**
     * Function for `CalendarFooter` view
     *
     * @param lang
     * @param date
     * @returns HTMLElement
     */
    getCalendarFooterView(lang: string, date: DateTime): HTMLElement;
    /**
     * Count the number of days of indentation
     *
     * @param date
     * @param firstDay
     * @returns Number
     */
    calcOffsetDays(date: DateTime, firstDay: number): number;
}
