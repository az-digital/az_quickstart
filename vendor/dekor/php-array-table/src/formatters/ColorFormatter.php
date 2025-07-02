<?php

namespace dekor\formatters;

use dekor\ArrayToTextTableException;

class ColorFormatter extends BaseColumnFormatter
{
    public $colors = [
        'Default' => self::DEFAULT_COLOR,

        'Black' => '0;30',
        'Dark Grey' => '1;30',
        'Red' => '0;31',
        'Light Red' => '1;31',
        'Green' => '0;32',
        'Light Green' => '1;32',
        'Brown' => '0;33',
        'Yellow' => '1;33',
        'Blue' => '0;34',
        'Light Blue' => '1;34',
        'Magenta' => '0;35',
        'Light Magenta' => '1;35',
        'Cyan' => '0;36',
        'Light Cyan' => '1;36',
        'Light Grey' => '0;37',
        'White' => '1;37',
    ];

    const DEFAULT_COLOR = '0m';

    protected function applyBefore($value, $formatterValue)
    {
        return $value;
    }

    protected function applyAfter($value, $formatterValue)
    {
        if ($formatterValue == 'Default') {
            return $value;
        }

        if (!isset($this->colors[$formatterValue])) {
            throw new ArrayToTextTableException('Unknown color to apply: ' . $formatterValue);
        }

        $color = $this->colors[$formatterValue];

        return "\e[" . $color . "m" . $value . "\e[" . self::DEFAULT_COLOR;
    }
}