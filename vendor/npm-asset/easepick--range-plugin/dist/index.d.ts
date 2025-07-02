import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { IRangeConfig } from './interface';
import './index.scss';
export declare class RangePlugin extends BasePlugin implements IPlugin {
    tooltipElement: HTMLElement;
    triggerElement: HTMLElement;
    binds: {
        setStartDate: any;
        setEndDate: any;
        setDateRange: any;
        getStartDate: any;
        getEndDate: any;
        onView: any;
        onShow: any;
        onMouseEnter: any;
        onMouseLeave: any;
        onClickCalendarDay: any;
        onClickApplyButton: any;
        parseValues: any;
        updateValues: any;
        clear: any;
    };
    options: IRangeConfig;
    /**
     * Returns plugin name
     *
     * @returns String
     */
    getName(): string;
    /**
     * - Called automatically via BasePlugin.attach() -
     * The function execute on initialize the picker
     */
    onAttach(): void;
    /**
     * - Called automatically via BasePlugin.detach() -
     */
    onDetach(): void;
    /**
     * Parse `startDate`, `endDate` options or value of input elements
     */
    private parseValues;
    /**
     * Update value of input element
     */
    private updateValues;
    /**
     * Clear selection
     */
    private clear;
    /**
     * Function `show` event
     *
     * @param event
     */
    private onShow;
    /**
     * Function `view` event
     * Adds HTML layout of current plugin to the picker layout
     *
     * @param event
     */
    private onView;
    /**
     * Function for documentClick option
     * Allows the picker to close when the user clicks outside
     *
     * @param e
     */
    private hidePicker;
    /**
     * Set startDate programmatically
     *
     * @param date
     */
    private setStartDate;
    /**
     * Set endDate programmatically
     *
     * @param date
     */
    private setEndDate;
    /**
     * Set date range programmatically
     *
     * @param start
     * @param end
     */
    private setDateRange;
    /**
     *
     * @returns DateTime
     */
    private getStartDate;
    /**
     *
     * @returns
     */
    private getEndDate;
    /**
     * Handle `mouseenter` event
     *
     * @param event
     */
    private onMouseEnter;
    /**
     * Handle `mouseleave` event
     *
     * @param event
     */
    private onMouseLeave;
    private onClickCalendarDay;
    private onClickApplyButton;
    /**
     * Displays tooltip of selected days
     *
     * @param element
     * @param text
     */
    private showTooltip;
    /**
     * Hide tooltip
     */
    private hideTooltip;
    /**
     * Determines if the locale option contains all required plurals
     */
    private checkIntlPluralLocales;
    /**
     * Handle `repick` option
     */
    private initializeRepick;
    /**
     * Determines if the element is the picker container
     *
     * @param element
     * @returns Boolean
     */
    private isContainer;
}
