import { DateTime } from '@easepick/datetime';
export interface IBaseConfig {
}
export interface IPlugin {
    binds?: unknown;
    getName(): string;
    onAttach(): void;
    onDetach(): void;
}
export interface IEventDetail {
    view?: string;
    date?: DateTime;
    target?: HTMLElement;
    index?: number;
}
