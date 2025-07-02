<?php declare(strict_types=1);

namespace FileEye\MimeMap\Map;

use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\MappingException;
use FileEye\MimeMap\TypeParser;

/**
 * Abstract class for mapping file extensions to MIME types.
 *
 * This class cannot be instantiated; extend from it to implement a map.
 *
 * @extends BaseMap<MimeMap>
 */
abstract class AbstractMap extends BaseMap implements MimeMapInterface
{
    public static function getInstance(): MimeMapInterface
    {
        $instance = parent::getInstance();
        assert($instance instanceof MimeMapInterface);
        return $instance;
    }

    /**
     * Normalizes a mime-type string to Media/Subtype.
     *
     * @param string $typeString
     *   MIME type string to parse.
     *
     * @throws MalformedTypeException when $typeString is malformed.
     *
     * @return string
     *   A MIME type string in the 'Media/Subtype' format.
     */
    protected function normalizeType(string $typeString): string
    {
        // Media and SubType are separated by a slash '/'.
        $media = TypeParser::parseStringPart($typeString, 0, '/');

        if (!$media['string']) {
            throw new MalformedTypeException('Media type not found');
        }
        if (!$media['delimiter_matched']) {
            throw new MalformedTypeException('Slash \'/\' to separate media type and subtype not found');
        }

        // SubType and Parameters are separated by semicolons ';'.
        $sub = TypeParser::parseStringPart($typeString, $media['end_offset'] + 1, ';');

        if (!$sub['string']) {
            throw new MalformedTypeException('Media subtype not found');
        }

        return strtolower($media['string']) . '/' . strtolower($sub['string']);
    }

    public function hasType(string $type): bool
    {
        $type = $this->normalizeType($type);
        return (bool) $this->getMapEntry('t', $type);
    }

    public function hasAlias(string $alias): bool
    {
        $alias = $this->normalizeType($alias);
        return (bool) $this->getMapEntry('a', $alias);
    }

    public function hasExtension(string $extension): bool
    {
        $extension = strtolower($extension);
        return (bool) $this->getMapEntry('e', $extension);
    }

    public function listTypes(?string $match = null): array
    {
        return array_map('strval', $this->listEntries('t', $match));
    }

    public function listAliases(?string $match = null): array
    {
        return array_map('strval', $this->listEntries('a', $match));
    }

    public function listExtensions(?string $match = null): array
    {
        return array_map('strval', $this->listEntries('e', $match));
    }

    public function addTypeDescription(string $type, string $description): MimeMapInterface
    {
        $type = $this->normalizeType($type);

        // Consistency checks.
        if ($this->hasAlias($type)) {
            throw new MappingException("Cannot add description for '{$type}', '{$type}' is an alias");
        }

        $this->addMapSubEntry('t', $type, 'desc', $description);
        return $this;
    }

    public function addTypeAlias(string $type, string $alias): MimeMapInterface
    {
        $type = $this->normalizeType($type);
        $alias = $this->normalizeType($alias);

        // Consistency checks.
        if (!$this->hasType($type)) {
            throw new MappingException("Cannot set '{$alias}' as alias for '{$type}', '{$type}' not defined");
        }
        if ($this->hasType($alias)) {
            throw new MappingException("Cannot set '{$alias}' as alias for '{$type}', '{$alias}' is already defined as a type");
        }
        if ($this->hasAlias($alias)) {
            $unaliased_types = $this->getAliasTypes($alias);
            if (!empty($unaliased_types) && $unaliased_types[0] !== $type) {
                throw new MappingException("Cannot set '{$alias}' as alias for '{$type}', it is an alias of '{$unaliased_types[0]}' already");
            }
            return $this;
        }

        $this->addMapSubEntry('t', $type, 'a', $alias);
        $this->addMapSubEntry('a', $alias, 't', $type);
        return $this;
    }

    public function addTypeExtensionMapping(string $type, string $extension): MimeMapInterface
    {
        $type = $this->normalizeType($type);
        $extension = strtolower($extension);

        // Consistency checks.
        if ($this->hasAlias($type)) {
            throw new MappingException("Cannot map '{$extension}' to '{$type}', '{$type}' is an alias");
        }

        // Add entry to 't'.
        $this->addMapSubEntry('t', $type, 'e', $extension);

        // Add entry to 'e'.
        $this->addMapSubEntry('e', $extension, 't', $type);

        return $this;
    }

    public function getTypeDescriptions(string $type): array
    {
        $descriptions = $this->getMapSubEntry('t', $this->normalizeType($type), 'desc') ?: [];
        return array_values($descriptions);
    }

    public function getTypeAliases(string $type): array
    {
        $aliases = $this->getMapSubEntry('t', $this->normalizeType($type), 'a') ?: [];
        return array_values($aliases);
    }

    public function getTypeExtensions(string $type): array
    {
        $extensions = $this->getMapSubEntry('t', $this->normalizeType($type), 'e') ?: [];
        return array_values($extensions);
    }

    public function setTypeDefaultExtension(string $type, string $extension): MimeMapInterface
    {
        $type = $this->normalizeType($type);
        $extension = strtolower($extension);
        return $this->setValueAsDefault('t', $type, 'e', $extension);
    }

    public function removeType(string $type): bool
    {
        $type = $this->normalizeType($type);

        // Return false if type is not found.
        if (!$this->hasType($type)) {
            return false;
        }

        // Loop through all the associated extensions and remove them.
        foreach ($this->getTypeExtensions($type) as $extension) {
            $this->removeTypeExtensionMapping($type, $extension);
        }

        // Loop through all the associated aliases and remove them.
        foreach ($this->getTypeAliases($type) as $alias) {
            $this->removeTypeAlias($type, $alias);
        }

        unset(static::$map['t'][$type]);

        return true;
    }

    public function removeTypeAlias(string $type, string $alias): bool
    {
        $type = $this->normalizeType($type);
        $alias = $this->normalizeType($alias);

        // Remove any extension mapped to the alias.
        if ($extensions = $this->getMapSubEntry('a', $alias, 'e')) {
            foreach ($extensions as $extension) {
                $this->removeMapSubEntry('a', $alias, 'e', $extension);
                $this->removeMapSubEntry('e', $extension, 't', $alias);
            }
        }

        $type_ret = $this->removeMapSubEntry('t', $type, 'a', $alias);
        $alias_ret = $this->removeMapSubEntry('a', $alias, 't', $type);

        return $type_ret && $alias_ret;
    }

    public function removeTypeExtensionMapping(string $type, string $extension): bool
    {
        $type = $this->normalizeType($type);
        $extension = strtolower($extension);

        if ($this->hasAlias($type)) {
            $alias = $type;
            $type_ret = $this->removeMapSubEntry('a', $alias, 'e', $extension);
            $extension_ret = $this->removeMapSubEntry('e', $extension, 't', $alias);
        } else {
            $this->removeAliasedTypesExtensionMapping($type, $extension);
            $type_ret = $this->removeMapSubEntry('t', $type, 'e', $extension);
            $extension_ret = $this->removeMapSubEntry('e', $extension, 't', $type);
        }

        return $type_ret && $extension_ret;
    }

    /**
     * Removes aliased types extension mapping.
     *
     * @param string $type
     *   A MIME type.
     * @param string $extension
     *   The file extension to be removed.
     */
    protected function removeAliasedTypesExtensionMapping(string $type, string $extension): void
    {
        $type = $this->normalizeType($type);
        $extension = strtolower($extension);
        foreach ($this->getExtensionTypes($extension) as $associated_type) {
            if ($this->hasAlias($associated_type) && $type === $this->getAliasTypes($associated_type)[0]) {
                $this->removeMapSubEntry('a', $associated_type, 'e', $extension);
                $this->removeMapSubEntry('e', $extension, 't', $associated_type);
            }
        }
    }

    public function getAliasTypes(string $alias): array
    {
        $types = $this->getMapSubEntry('a', $this->normalizeType($alias), 't') ?: [];
        return array_values($types);
    }

    public function getExtensionTypes(string $extension): array
    {
        $types = $this->getMapSubEntry('e', strtolower($extension), 't') ?: [];
        return array_values($types);
    }

    public function setExtensionDefaultType(string $extension, string $type): MimeMapInterface
    {
        $type = $this->normalizeType($type);
        $extension = strtolower($extension);

        if ($this->hasAlias($type)) {
            $alias = $type;
            $type = $this->getAliasTypes($alias)[0];
            // Check that the alias is referring to a type associated with that
            // extension.
            $associated_types = $this->getMapSubEntry('e', $extension, 't') ?: [];
            if (!in_array($type, $associated_types)) {
                throw new MappingException("Cannot set '{$alias}' as default type for extension '{$extension}', its unaliased type '{$type}' is not associated to '{$extension}'");
            }
            $this->addMapSubEntry('a', $alias, 'e', $extension);
            $this->addMapSubEntry('e', $extension, 't', $alias);
            return $this->setValueAsDefault('e', $extension, 't', $alias);
        } else {
            return $this->setValueAsDefault('e', $extension, 't', $type);
        }
    }

    protected function setValueAsDefault(string $entry, string $entryKey, string $subEntry, string $value): MimeMapInterface
    {
        parent::setValueAsDefault($entry, $entryKey, $subEntry, $value);
        return $this;
    }
}
