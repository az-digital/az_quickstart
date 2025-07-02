<?php declare(strict_types=1);

namespace FileEye\MimeMap;

use FileEye\MimeMap\Map\MimeMapInterface;

/**
 * Interface for Type objects.
 */
interface TypeInterface
{
    /**
     * Constructor.
     *
     * The type string will be parsed and the appropriate class vars set.
     *
     * @param string $typeString
     *   MIME type string to be parsed.
     * @param class-string<MimeMapInterface>|null $mapClass
     *   (Optional) The FQCN of the map class to use.
     *
     * @api
     */
    public function __construct(string $typeString, ?string $mapClass = null);

    /**
     * Gets a MIME type's media.
     *
     * Note: 'media' refers to the portion before the first slash.
     *
     * @api
     */
    public function getMedia(): string;

    /**
     * Sets a MIME type's media.
     *
     * @api
     */
    public function setMedia(string $media): TypeInterface;

    /**
     * Checks if the MIME type has media comment.
     *
     * @api
     */
    public function hasMediaComment(): bool;

    /**
     * Gets the MIME type's media comment.
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function getMediaComment(): string;

    /**
     * Sets the MIME type's media comment.
     *
     * @param string $comment (optional) a comment; when missing any existing comment is removed.
     *
     * @api
     */
    public function setMediaComment(?string $comment = null): TypeInterface;

    /**
     * Gets a MIME type's subtype.
     *
     * @api
     */
    public function getSubType(): string;

    /**
     * Sets a MIME type's subtype.
     *
     * @api
     */
    public function setSubType(string $subType): TypeInterface;

    /**
     * Checks if the MIME type has subtype comment.
     *
     * @api
     */
    public function hasSubTypeComment(): bool;

    /**
     * Gets the MIME type's subtype comment.
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function getSubTypeComment(): string;

    /**
     * Sets the MIME type's subtype comment.
     *
     * @param string|null $comment (optional) a comment; when missing any existing comment is removed.
     *
     * @api
     */
    public function setSubTypeComment(?string $comment = null): TypeInterface;

    /**
     * Checks if the MIME type has any parameter.
     *
     * @api
     */
    public function hasParameters(): bool;

    /**
     * Get the MIME type's parameters.
     *
     * @return TypeParameter[]
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function getParameters(): array;

    /**
     * Checks if the MIME type has a parameter.
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function hasParameter(string $name): bool;

    /**
     * Get a MIME type's parameter.
     *
     * @throws UndefinedException
     *
     * @api
     */
    public function getParameter(string $name): TypeParameter;

    /**
     * Add a parameter to this type
     *
     * @api
     */
    public function addParameter(string $name, string $value, ?string $comment = null): void;

    /**
     * Remove a parameter from this type.
     *
     * @api
     */
    public function removeParameter(string $name): void;

    /**
     * Create a textual MIME type from object values.
     *
     * This function performs the opposite function of parse().
     *
     * @param int $format
     *   The format of the output string.
     *
     * @api
     */
    public function toString(int $format = Type::FULL_TEXT): string;

    /**
     * Is this type experimental?
     *
     * Note: Experimental types are denoted by a leading 'x-' in the media or
     * subtype, e.g. text/x-vcard or x-world/x-vrml.
     *
     * @api
     */
    public function isExperimental(): bool;

    /**
     * Is this a vendor MIME type?
     *
     * Note: Vendor types are denoted with a leading 'vnd. in the subtype.
     *
     * @api
     */
    public function isVendor(): bool;

    /**
     * Is this a wildcard type?
     *
     * @api
     */
    public function isWildcard(): bool;

    /**
     * Is this an alias?
     *
     * @api
     */
    public function isAlias(): bool;

    /**
     * Perform a wildcard match on a MIME type
     *
     * Example:
     * $type = new Type('image/png');
     * $type->wildcardMatch('image/*');
     *
     * @param string $wildcard
     *   Wildcard to check against.
     *
     * @return bool
     *   True if there was a match, false otherwise.
     *
     * @api
     */
    public function wildcardMatch(string $wildcard): bool;

    /**
     * Builds a list of MIME types existing in the map.
     *
     * If the current type is a wildcard, than all the types matching the
     * wildcard will be returned.
     *
     * @throws MappingException if no mapping found.
     *
     * @return array<int,int|string>
     *
     * @api
     */
    public function buildTypesList(): array;

    /**
     * Checks if a description for the MIME type exists.
     *
     * @api
     */
    public function hasDescription(): bool;

    /**
     * Returns a description for the MIME type, if existing in the map.
     *
     * @param bool $includeAcronym
     *   (Optional) if true and an acronym description exists for the type,
     *   the returned description will contain the acronym and its description,
     *   appended with a comma. Defaults to false.
     *
     * @throws MappingException if no description found.
     *
     * @api
     */
    public function getDescription(bool $includeAcronym = false): string;

    /**
     * Returns all the aliases related to the MIME type(s).
     *
     * If the current type is a wildcard, than all aliases of all the
     * types matching the wildcard will be returned.
     *
     * @throws MappingException on error.
     *
     * @return list<string>
     *
     * @api
     */
    public function getAliases(): array;

    /**
     * Returns the MIME type's preferred file extension.
     *
     * @throws MappingException if no mapping found.
     *
     * @api
     */
    public function getDefaultExtension(): string;

    /**
     * Returns all the file extensions related to the MIME type(s).
     *
     * If the current type is a wildcard, than all extensions of all the types matching the wildcard will be returned.
     *
     * @throws MappingException if no mapping found.
     *
     * @return list<string>
     *
     * @api
     */
    public function getExtensions(): array;
}
