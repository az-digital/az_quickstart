import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { LockPlugin } from '@easepick/lock-plugin';
import { RangePlugin } from '@easepick/range-plugin';
import { IAmpPlugin } from './interface';
import './index.scss';
export declare class AmpPlugin extends BasePlugin implements IPlugin {
    rangePlugin: RangePlugin;
    lockPlugin: LockPlugin;
    priority: number;
    binds: {
        onView: any;
        onColorScheme: any;
    };
    options: IAmpPlugin;
    protected matchMedia: any;
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
     * Adds `tabIndex` to the picker elements
     *
     * @param event
     */
    private onView;
    /**
     *
     * @param evt
     */
    private onColorScheme;
    /**
     *
     * @param evt
     */
    handleDropdown(evt: any): void;
    /**
     *
     * @param event
     */
    private handleResetButton;
    /**
     *
     * @param event
     */
    private handleWeekNumbers;
}
