import { Core } from '@easepick/core';
import { IBaseConfig, IPlugin, IEventDetail } from './interface';
export declare class BasePlugin {
    picker: Core;
    options: IBaseConfig;
    priority: number;
    dependencies: string[];
    /**
     * - Called automatically via PluginManager.initialize() or PluginManager.addInstance() -
     * Add plugin to the picker
     *
     * @param picker
     */
    attach(picker: Core): void;
    /**
     * - Called automatically via PluginManager.removeInstance() -
     * Remove plugin from the picker
     */
    detach(): void;
    /**
     * Check dependencies for plugin
     *
     * @returns Boolean
     */
    private dependenciesNotFound;
    /**
     * Return plugins list as string array
     *
     * @returns []
     */
    private pluginsAsStringArray;
    /**
     * Return camelCase in kebab-case
     * Eg.: `userName` -> `user-name`
     *
     * @param str
     * @returns String
     */
    private camelCaseToKebab;
}
export { IBaseConfig, IPlugin, IEventDetail };
