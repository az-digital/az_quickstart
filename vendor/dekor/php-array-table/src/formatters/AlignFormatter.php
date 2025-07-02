<?php

namespace dekor\formatters;

use dekor\ArrayToTextTableException;

class AlignFormatter extends BaseColumnFormatter
{
    const ALLOWED_ALIGN = ['left', 'right', 'center'];

    protected function applyBefore($value, $formatterValue)
    {
        return $value;
    }

    /**
     * @throws ArrayToTextTableException
     */
    protected function applyAfter($value, $formatterValue)
    {
        if (!in_array($formatterValue, self::ALLOWED_ALIGN)) {
            throw new ArrayToTextTableException(
                'Invalid align. Only allowed: ' . implode(', ', self::ALLOWED_ALIGN)
            );
        }

        $length = mb_strlen($value);

        $value = trim($value);
        $trimLength = mb_strlen($value);

        switch ($formatterValue) {
            case 'center':
                $halfDelta = ($length - $trimLength) / 2;
                return str_repeat(' ', floor($halfDelta)) . $value . str_repeat(' ', ceil($halfDelta));

            case 'right':
                return str_repeat(' ', $length - $trimLength - 1). $value . ' ';
        }

        return $value;
    }
}