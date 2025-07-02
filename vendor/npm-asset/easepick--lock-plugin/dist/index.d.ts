import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { ILockConfig } from './interface';
import './index.scss';
export declare class LockPlugin extends BasePlugin implements IPlugin {
    priority: number;
    binds: {
        onView: any;
    };
    options: ILockConfig;
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
     * - Called automatically via BasePlugin.attach() -
     * The function execute on initialize the picker
     */
    onDetach(): void;
    /**
     * Function `view` event
     * Mark day elements as locked
     *
     * @param event
     */
    private onView;
    /**
     * Checks availability date
     *
     * @param date
     * @param start
     * @returns Boolean
     */
    private dateIsNotAvailable;
    /**
     * Checks the date range for availability
     *
     * @param date1
     * @param date2
     * @returns Boolean
     */
    private rangeIsNotAvailable;
    /**
     * Handle `minDate` option
     *
     * @param date
     * @returns Boolean
     */
    private lockMinDate;
    /**
     * Handle `maxDate` option
     *
     * @param date
     * @returns Boolean
     */
    private lockMaxDate;
    /**
     * Handle `minDays` option
     *
     * @param date
     * @returns Boolean
     */
    private lockMinDays;
    /**
     * Handle `maxDays` option
     *
     * @param date
     * @returns Boolean
     */
    private lockMaxDays;
    /**
     * Handle `selectForward` option
     *
     * @param date
     * @returns Boolean
     */
    private lockSelectForward;
    /**
     * Handle `selectBackward` option
     *
     * @param date
     * @returns Boolean
     */
    private lockSelectBackward;
    /**
     * Handle `filter` option
     *
     * @param date
     * @returns Boolean
     */
    private testFilter;
}
