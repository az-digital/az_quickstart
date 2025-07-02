import { Core } from './core';
export default class PluginManager {
    picker: Core;
    instances: {};
    constructor(picker: Core);
    /**
     * Initialize user-supplied plugins (if any)
     */
    initialize(): void;
    /**
     * Return instance of plugin
     *
     * @param name
     * @returns Plugin
     */
    getInstance<T>(name: string): T;
    /**
     * Add plugin «on the fly» to the picker
     *
     * @param name
     */
    addInstance<T>(name: string): T;
    /**
     * Remove plugin from the picker
     *
     * @param name
     */
    removeInstance(name: string): boolean;
    /**
     * Reload plugin
     *
     * @param name
     */
    reloadInstance<T>(name: string): T;
    /**
     * Find plugin function by the name
     *
     * @param name
     * @returns Plugin
     */
    private getPluginFn;
}
