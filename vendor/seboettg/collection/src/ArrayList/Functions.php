<?php

namespace Seboettg\Collection\ArrayList;

final class Functions
{

    public static function strval($value): string {
        if (is_double($value)) {
            $str = \strval($value);
            if (strlen($str) == 1) {
                return sprintf("%1\$.1f",$value);
            }
            return \strval($value);
        }
        if (is_bool($value)) {
            return $value ? "true" : "false";
        }
        return "$value";
    }
}

function strval($value): string {
    return Functions::strval($value);
}
