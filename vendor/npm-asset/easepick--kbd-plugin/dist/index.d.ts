import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { RangePlugin } from '@easepick/range-plugin';
import { IKbdPlugin } from './interface';
import './index.scss';
export declare class KbdPlugin extends BasePlugin implements IPlugin {
    docElement: HTMLElement;
    rangePlugin: RangePlugin;
    binds: {
        onView: any;
        onKeydown: any;
    };
    options: IKbdPlugin;
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
     * Function for `keydown` event
     * Handle keys when the picker has focus
     *
     * @param event
     */
    private onKeydown;
    /**
     * Find closest day elements
     *
     * @param layout
     * @param target
     * @param isAllow
     * @returns Boolean
     */
    private findAllowableDaySibling;
    /**
     * Switch month via buttons (previous month, next month)
     *
     * @param evt
     */
    private changeMonth;
    /**
     * Handle ArrowUp and ArrowDown keys
     *
     * @param evt
     */
    private verticalMove;
    /**
     * Handle ArrowLeft and ArrowRight keys
     *
     * @param evt
     */
    private horizontalMove;
    /**
     * Handle Enter and Space keys
     *
     * @param evt
     */
    private handleEnter;
    /**
     * Manually fire `mouseenter` event to display date range correctly
     *
     * @param evt
     */
    private onMouseEnter;
}
