import { BasePlugin, IPlugin } from '@easepick/base-plugin';
import { IPresetConfig } from './interface';
import './index.scss';
export declare class PresetPlugin extends BasePlugin implements IPlugin {
    dependencies: string[];
    binds: {
        onView: any;
        onClick: any;
    };
    options: IPresetConfig;
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
     * Handle click event
     *
     * @param event
     */
    private onClick;
    /**
     * Determines if HTMLElement is preset buttons
     *
     * @param element
     * @returns Boolean
     */
    private isPresetButton;
}
