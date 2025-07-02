<?php declare(strict_types=1);

namespace FileEye\MimeMap\Map;

use FileEye\MimeMap\MappingException;

/**
 * Interface for MimeMap maps.
 *
 * @extends MapInterface<MimeMap>
 */
interface MimeMapInterface extends MapInterface
{
    /**
     * Determines if a MIME type exists.
     *
     * @param string $type The type to be found.
     */
    public function hasType(string $type): bool;

    /**
     * Determines if a MIME type alias exists.
     *
     * @param string $alias The alias to be found.
     */
    public function hasAlias(string $alias): bool;

    /**
     * Determines if an entry exists from the 'extensions' array.
     *
     * @param string $extension The extension to be found.
     */
    public function hasExtension(string $extension): bool;

    /**
     * Lists all the MIME types defined in the map.
     *
     * @param string $match (Optional) a match wildcard to limit the list.
     *
     * @return list<string>
     */
    public function listTypes(?string $match = null): array;

    /**
     * Lists all the MIME types aliases defined in the map.
     *
     * @param string $match (Optional) a match wildcard to limit the list.
     *
     * @return list<string>
     */
    public function listAliases(?string $match = null): array;

    /**
     * Lists all the extensions defined in the map.
     *
     * @param string $match (Optional) a match wildcard to limit the list.
     *
     * @return list<string>
     */
    public function listExtensions(?string $match = null): array;

    /**
     * Adds a description of a MIME type.
     *
     * @param string $type
     *   A MIME type.
     * @param string $description
     *   The description of the MIME type.
     *
     * @throws MappingException if $type is an alias.
     */
    public function addTypeDescription(string $type, string $description): MimeMapInterface;

    /**
     * Adds an alias of a MIME type.
     *
     * @param string $type
     *   A MIME type.
     * @param string $alias
     *   An alias of $type.
     *
     * @throws MappingException if no $type is found.
     */
    public function addTypeAlias(string $type, string $alias): MimeMapInterface;

    /**
     * Adds a type-to-extension mapping.
     *
     * @param string $type
     *   A MIME type.
     * @param string $extension
     *   A file extension.
     *
     * @throws MappingException if $type is an alias.
     */
    public function addTypeExtensionMapping(string $type, string $extension): MimeMapInterface;

    /**
     * Gets the descriptions of a MIME type.
     *
     * @param string $type The type to be found.
     *
     * @return list<string> The mapped descriptions.
     */
    public function getTypeDescriptions(string $type): array;

    /**
     * Gets the aliases of a MIME type.
     *
     * @param string $type The type to be found.
     *
     * @return list<string> The mapped aliases.
     */
    public function getTypeAliases(string $type): array;

    /**
     * Gets the content of an entry from the 't' array.
     *
     * @param string $type The type to be found.
     *
     * @return list<string> The mapped file extensions.
     */
    public function getTypeExtensions(string $type): array;

    /**
     * Changes the default extension for a MIME type.
     *
     * @param string $type
     *   A MIME type.
     * @param string $extension
     *   A file extension.
     *
     * @throws MappingException if no mapping found.
     */
    public function setTypeDefaultExtension(string $type, string $extension): MimeMapInterface;

    /**
     * Removes the entire mapping of a type.
     *
     * @param string $type
     *   A MIME type.
     *
     * @return bool
     *   true if the mapping was removed, false if the type was not present.
     */
    public function removeType(string $type): bool;

    /**
     * Removes a MIME type alias.
     *
     * @param string $type
     *   A MIME type.
     * @param string $alias
     *   The alias to be removed.
     *
     * @return bool
     *   true if the alias was removed, false if the alias was not present.
     */
    public function removeTypeAlias(string $type, string $alias): bool;

    /**
     * Removes a type-to-extension mapping.
     *
     * @param string $type
     *   A MIME type.
     * @param string $extension
     *   The file extension to be removed.
     *
     * @return bool
     *   true if the mapping was removed, false if the mapping was not present.
     */
    public function removeTypeExtensionMapping(string $type, string $extension): bool;

    /**
     * Gets the parent types of an alias.
     *
     * There should not be multiple types for an alias.
     *
     * @param string $alias The alias to be found.
     *
     * @return list<string>
     */
    public function getAliasTypes(string $alias): array;

    /**
     * Gets the content of an entry from the 'extensions' array.
     *
     * @param string $extension The extension to be found.
     *
     * @return list<string> The mapped MIME types.
     */
    public function getExtensionTypes(string $extension): array;

    /**
     * Changes the default MIME type for a file extension.
     *
     * Allows a MIME type alias to be set as default for the extension.
     *
     * @param string $extension
     *   A file extension.
     * @param string $type
     *   A MIME type.
     *
     * @throws MappingException if no mapping found.
     */
    public function setExtensionDefaultType(string $extension, string $type): MimeMapInterface;
}
