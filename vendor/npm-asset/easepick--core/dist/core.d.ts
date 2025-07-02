import Calendar from './calendar';
import { DateTime } from '@easepick/datetime';
import PluginManager from './pluginManager';
import { IPickerConfig, IPickerElements } from './types';
export declare class Core {
    Calendar: Calendar;
    PluginManager: PluginManager;
    calendars: DateTime[];
    datePicked: DateTime[];
    cssLoaded: number;
    binds: {
        hidePicker: any;
        show: any;
    };
    options: IPickerConfig;
    ui: IPickerElements;
    version: string;
    constructor(options: IPickerConfig);
    /**
     * Add listener to container element
     *
     * @param type
     * @param listener
     * @param options
     */
    on(type: string, listener: (event: any) => void, options?: unknown): void;
    /**
     * Remove listener from container element
     *
     * @param type
     * @param listener
     * @param options
     */
    off(type: string, listener: (event: any) => void, options?: unknown): void;
    /**
     * Dispatch an event
     *
     * @param type
     * @param detail
     * @returns
     */
    trigger(type: string, detail?: unknown): boolean;
    /**
     * Destroy picker
     */
    destroy(): void;
    /**
     * Fired on render event
     *
     * @param event
     */
    onRender(event: CustomEvent): void;
    onView(event: CustomEvent): void;
    /**
     *
     * @param element
     */
    onClickHeaderButton(element: HTMLElement): void;
    /**
     *
     * @param element
     */
    onClickCalendarDay(element: HTMLElement): void;
    /**
     *
     * @param element
     */
    onClickApplyButton(element: HTMLElement): void;
    /**
     *
     * @param element
     * @returns
     */
    onClickCancelButton(element: HTMLElement): void;
    /**
     * Fired on click event
     *
     * @param event
     */
    onClick(event: any): void;
    /**
     * Determine if the picker is visible or not
     *
     * @returns Boolean
     */
    isShown(): boolean;
    /**
     * Show the picker
     *
     * @param event
     */
    show(event?: any): void;
    /**
     * Hide the picker
     */
    hide(): void;
    /**
     * Set date programmatically
     *
     * @param date
     */
    setDate(date: Date | string | number): void;
    /**
     *
     * @returns DateTime
     */
    getDate(): DateTime;
    /**
     * Parse `date` option or value of input element
     */
    parseValues(): void;
    /**
     * Update value of input element
     */
    updateValues(): void;
    /**
     * Function for documentClick option
     * Allows the picker to close when the user clicks outside
     *
     * @param e
     */
    hidePicker(e: any): void;
    /**
     * Render entire picker layout
     *
     * @param date
     */
    renderAll(date?: DateTime): void;
    /**
     * Determines if the element is buttons of header (previous month, next month)
     *
     * @param element
     * @returns Boolean
     */
    isCalendarHeaderButton(element: HTMLElement): boolean;
    /**
     * Determines if the element is day element
     *
     * @param element
     * @returns Boolean
     */
    isCalendarDay(element: HTMLElement): boolean;
    /**
     * Determines if the element is the apply button
     *
     * @param element
     * @returns Boolean
     */
    isApplyButton(element: HTMLElement): boolean;
    /**
     * Determines if the element is the cancel button
     *
     * @param element
     * @returns Boolean
     */
    isCancelButton(element: HTMLElement): boolean;
    /**
     * Change visible month
     *
     * @param date
     */
    gotoDate(date: Date | string | number): void;
    /**
     * Clear date selection
     */
    clear(): void;
    /**
     * Handling parameters passed by the user
     */
    private handleOptions;
    /**
     * Apply CSS passed by the user
     */
    private handleCSS;
    /**
     * Calculate the position of the picker
     *
     * @param element
     * @returns { top, left }
     */
    private adjustPosition;
}
export { Core as create, };
