<?php declare(strict_types=1);

namespace FileEye\MimeMap;

/**
 * Class for working with MIME type parameters.
 */
class TypeParameter
{
    /**
     * @param string      $name    Parameter name.
     * @param string      $value   Parameter value.
     * @param string|null $comment Comment for this parameter.
     *
     * @api
     */
    public function __construct(
        protected readonly string $name,
        protected readonly string $value,
        protected readonly ?string $comment = null,
    ) {
    }

    /**
     * Gets the parameter name.
     *
     * @api
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the parameter value.
     *
     * @api
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Does this parameter have a comment?
     *
     * @api
     */
    public function hasComment(): bool
    {
        return (bool) $this->comment;
    }

    /**
     * Gets the parameter comment.
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function getComment(): string
    {
        if ($this->hasComment()) {
            assert(is_string($this->comment));
            return $this->comment;
        }
        throw new UndefinedException('Parameter comment is not defined');
    }

    /**
     * Gets a string representation of this parameter.
     *
     * @param int $format The format of the output string.
     *
     * @api
     */
    public function toString(int $format = Type::FULL_TEXT): string
    {
        $val = $this->name . '="' . str_replace('"', '\\"', $this->value) . '"';
        if ($format > Type::FULL_TEXT && $this->hasComment()) {
            $val .= ' (' . $this->getComment() . ')';
        }
        return $val;
    }
}
