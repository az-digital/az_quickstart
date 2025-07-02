export declare class DateTime extends Date {
    static parseDateTime(date: unknown, format?: string, lang?: string): Date;
    private static regex;
    private static readonly MONTH_JS;
    private static shortMonths;
    private static longMonths;
    /**
     * Returns group and pattern for match function
     *
     * @param token
     * @param lang
     * @returns { group, pattern }
     */
    private static formatPatterns;
    protected lang: string;
    constructor(date?: unknown, format?: string, lang?: string);
    /**
     * Returns the week number
     *
     * @param firstDay
     * @returns Number
     */
    getWeek(firstDay: number): number;
    /**
     * Duplicate the date
     *
     * @returns DateTime
     */
    clone(): DateTime;
    /**
     * Convert DateTime to Date object
     *
     * @returns Date
     */
    toJSDate(): Date;
    /**
     * Find DateTime object (this) in passed DateTime array
     *
     * @param array
     * @param inclusivity
     * @returns Boolean
     */
    inArray(array: Array<DateTime | DateTime[]>, inclusivity?: string): boolean;
    /**
     * Check if a DateTime is between two other DateTime, optionally looking at unit scale
     *
     * @param date1
     * @param date2
     * @param inclusivity
     * @returns Boolean
     */
    isBetween(date1: DateTime, date2: DateTime, inclusivity?: string): boolean;
    /**
     * Check if a DateTime is before another DateTime.
     *
     * @param date
     * @param unit
     * @returns Boolean
     */
    isBefore(date: DateTime, unit?: string): boolean;
    /**
     * Check if a DateTime is before or the same as another DateTime.
     *
     * @param date
     * @param unit
     * @returns Boolean
     */
    isSameOrBefore(date: DateTime, unit?: string): boolean;
    /**
     * Check if a DateTime is after another DateTime.
     *
     * @param date
     * @param unit
     * @returns Boolean
     */
    isAfter(date: DateTime, unit?: string): boolean;
    /**
     * Check if a DateTime is after or the same as another DateTime.
     *
     * @param date
     * @param unit
     * @returns Boolean
     */
    isSameOrAfter(date: DateTime, unit?: string): boolean;
    /**
     * Check if a DateTime is the same as another DateTime.
     *
     * @param date
     * @param unit
     * @returns Boolean
     */
    isSame(date: DateTime, unit?: string): boolean;
    /**
     * Mutates the original DateTime by adding time.
     *
     * @param duration
     * @param unit
     */
    add(duration: number, unit?: string): DateTime;
    /**
     * Mutates the original DateTime by subtracting time.
     *
     * @param duration
     * @param unit
     */
    subtract(duration: number, unit?: string): DateTime;
    /**
     * Returns diff between two DateTime
     *
     * @param date
     * @param unit
     * @returns Number
     */
    diff(date: DateTime, unit?: string): number;
    /**
     * Format output
     *
     * @param format
     * @param lang
     * @returns String
     */
    format(format: string, lang?: string): string;
    /**
     * Returns the midnight timestamp of a date
     *
     * @param date
     * @returns Date
     */
    private midnight_ts;
    /**
     * Returns the formatted string of the passed token
     *
     * @param token
     * @param lang
     * @returns String
     */
    private formatTokens;
}
