import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { RangePlugin } from '@easepick/range-plugin';
import { ITimeConfig } from './interface';
import './index.scss';
export declare class TimePlugin extends BasePlugin implements IPlugin {
    options: ITimeConfig;
    rangePlugin: RangePlugin;
    timePicked: {
        input: any;
        start: any;
        end: any;
    };
    timePrePicked: {
        input: any;
        start: any;
        end: any;
    };
    binds: {
        getDate: any;
        getStartDate: any;
        getEndDate: any;
        onView: any;
        onInput: any;
        onChange: any;
        onClick: any;
        setTime: any;
        setStartTime: any;
        setEndTime: any;
    };
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
     * Function `view` event
     * Adds HTML layout of current plugin to the picker layout
     *
     * @param event
     */
    private onView;
    /**
     *
     * @param event
     */
    private onInput;
    /**
     * Handle `change` event
     *
     * @param event
     */
    private onChange;
    private onClick;
    /**
     * Set time programmatically
     *
     * @param value
     * @param keyName
     */
    private setTime;
    /**
     * Set start time programmatically
     *
     * @param value
     * @param keyName
     */
    private setStartTime;
    /**
     * Set end time programmatically
     *
     * @param value
     * @param keyName
     */
    private setEndTime;
    private handleTimeString;
    /**
     * Adds time to DateTime object
     * Replaces the original `getDate` function
     *
     * @returns DateTime
     */
    private getDate;
    /**
     * Adds time to DateTime object
     * Replaces the original `getStartDate` function
     *
     * @returns DateTime
     */
    private getStartDate;
    /**
     * Adds time to DateTime object
     * Replaces the original `getEndDate` function
     *
     * @returns DateTime
     */
    private getEndDate;
    /**
     *
     * @returns HTMLElement
     */
    private getSingleInput;
    /**
     *
     * @returns HTMLElement
     */
    private getStartInput;
    /**
     *
     * @returns HTMLElement
     */
    private getEndInput;
    /**
     * Returns `input[type="time"]` element
     *
     * @param name
     * @returns HTMLElement
     */
    private getNativeInput;
    /**
     * Returns `select` element
     *
     * @param name
     * @returns HTMLElement
     */
    private getCustomInput;
    /**
     * Handle 12H time
     *
     * @param period
     * @param date
     * @param value
     * @returns DateTime
     */
    private handleFormat12;
    private parseValues;
}
