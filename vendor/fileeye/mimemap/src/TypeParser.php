<?php declare(strict_types=1);

namespace FileEye\MimeMap;

/**
 * Class for parsing RFC 2045 Content-Type Header Fields.
 */
class TypeParser
{
    /**
     * Parse a mime-type and set the class variables.
     *
     * @param string $typeString
     *   MIME type string to parse.
     * @param Type $type
     *   The Type object to receive the components.
     *
     * @throws MalformedTypeException when $typeString is malformed.
     */
    public static function parse(string $typeString, Type $type): void
    {
        // Media and SubType are separated by a slash '/'.
        $media = static::parseStringPart($typeString, 0, '/');
        if (!$media['string']) {
            throw new MalformedTypeException('Media type not found');
        }
        if (!$media['delimiter_matched']) {
            throw new MalformedTypeException('Slash \'/\' to separate media type and subtype not found');
        }
        $type->setMedia(strtolower($media['string']));
        if ($media['comment'] !== null) {
            $type->setMediaComment($media['comment']);
        }

        // SubType and Parameters are separated by semicolons ';'.
        $sub = static::parseStringPart($typeString, $media['end_offset'] + 1, ';');
        if (!$sub['string']) {
            throw new MalformedTypeException('Media subtype not found');
        }
        $type->setSubType(strtolower($sub['string']));
        if ($sub['comment'] !== null) {
            $type->setSubTypeComment($sub['comment']);
        }

        // Loops through the parameter.
        while ($sub['delimiter_matched']) {
            $sub = static::parseStringPart($typeString, $sub['end_offset'] + 1, ';');
            $tmp = explode('=', $sub['string'], 2);
            $p_name = trim($tmp[0]);
            $p_val = str_replace('\\"', '"', trim($tmp[1] ?? ''));
            $type->addParameter($p_name, $p_val, $sub['comment']);
        }
    }

    /**
     * Parses a part of the content MIME type string.
     *
     * Splits string and comment until a delimiter is found.
     *
     * @param string $string
     *   Input string.
     * @param int $offset
     *   Offset to start parsing from.
     * @param string $delimiter
     *   Stop parsing when delimiter found.
     *
     * @return array{'string': string, 'comment': string|null, 'delimiter_matched': bool, 'end_offset': int}
     *   An array with the following keys:
     *   'string' - the uncommented part of $string
     *   'comment' - the comment part of $string
     *   'delimiter_matched' - true if a $delimiter stopped the parsing, false
     *                         otherwise
     *   'end_offset' - the last position parsed in $string.
     */
    public static function parseStringPart(string $string, int $offset, string $delimiter): array
    {
        $inquote   = false;
        $escaped   = false;
        $incomment = 0;
        $newstring = '';
        $comment = '';

        for ($n = $offset; $n < strlen($string); $n++) {
            if ($string[$n] === $delimiter && !$escaped && !$inquote && $incomment === 0) {
                break;
            }

            if ($escaped) {
                if ($incomment == 0) {
                    $newstring .= $string[$n];
                } else {
                    $comment .= $string[$n];
                }
                $escaped = false;
                continue;
            }

            if ($string[$n] == '\\') {
                if ($incomment > 0) {
                    $comment .= $string[$n];
                }
                $escaped = true;
                continue;
            }

            if (!$inquote && $incomment > 0 && $string[$n] == ')') {
                $incomment--;
                if ($incomment == 0) {
                    $comment .= ' ';
                }
                continue;
            }

            if (!$inquote && $string[$n] == '(') {
                $incomment++;
                continue;
            }

            if ($string[$n] == '"') {
                if ($incomment > 0) {
                    $comment .= $string[$n];
                } else {
                    if ($inquote) {
                        $inquote = false;
                    } else {
                        $inquote = true;
                    }
                }
                continue;
            }

            if ($incomment == 0) {
                $newstring .= $string[$n];
                continue;
            }

            $comment .= $string[$n];
        }

        if ($incomment > 0) {
            throw new MalformedTypeException('Comment closing bracket missing: ' . $comment);
        }

        return [
          'string' => trim($newstring),
          'comment' => empty($comment) ? null : trim($comment),
          'delimiter_matched' => isset($string[$n]) ? ($string[$n] === $delimiter) : false,
          'end_offset' => $n,
        ];
    }
}
