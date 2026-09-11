import { default as OptionsCalendar } from './options';
import { ContextVariables, DateAny, DateMode, DatesArr, FormatDateString, HtmlElementPosition, Labels, Layouts, Locale, LocaleStated, MonthsCount, Options, Popup, Popups, Positions, PositionToInput, Range, Reset, Styles, ThemesDefault, TimePicker, ToggleSelected, TypesCalendar, WeekDayID, WeekDays } from './types';
export declare class Calendar extends OptionsCalendar {
    private static memoizedElements;
    constructor(selector: HTMLElement | string, options?: Options);
    private queryAndMemoize;
    init: () => () => void;
    update: (resetOptions?: Partial<Reset>) => void;
    destroy: () => void;
    show: () => void;
    hide: () => void;
    set: (options: Options, resetOptions?: Partial<Reset>) => void;
    readonly context: Readonly<ContextVariables>;
}
export { DateAny, DateMode, DatesArr, FormatDateString, HtmlElementPosition, Labels, Layouts, Locale, LocaleStated, MonthsCount, Options, Popup, Popups, Positions, PositionToInput, ContextVariables, Range, Reset, Styles, ThemesDefault, TimePicker, ToggleSelected, TypesCalendar, WeekDayID, WeekDays, };
