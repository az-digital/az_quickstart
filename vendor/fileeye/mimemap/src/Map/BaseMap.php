<?php declare(strict_types=1);

namespace FileEye\MimeMap\Map;

use FileEye\MimeMap\MappingException;

/**
 * Abstract base class for managing MimeMap maps.
 *
 * This class cannot be instantiated.
 *
 * @template TMap of GenericMap
 * @implements MapInterface<TMap>
 */
abstract class BaseMap implements MapInterface
{
    /**
     * Singleton instance.
     *
     * @var MapInterface<TMap>|null
     */
    protected static $instance;

    /**
     * Mapping between file extensions and MIME types.
     *
     * @var TMap
     */
    protected static $map = [];

    /**
     * A backup of the mapping between file extensions and MIME types.
     *
     * Used during the map update process.
     *
     * @var TMap|null
     */
    protected static ?array $backupMap;

    public function __construct()
    {
    }

    public function backup(): void
    {
        static::$backupMap = static::$map;
    }

    public function reset(): void
    {
        if (isset(static::$backupMap)) {
            static::$map = static::$backupMap;
        }
        static::$backupMap = null;
    }

    public static function getInstance(): MapInterface
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getFileName(): string
    {
        throw new \LogicException(__METHOD__ . ' is not implemented in ' . get_called_class());
    }

    public function getMapArray(): array
    {
        return static::$map;
    }

    public function sort(): MapInterface
    {
        foreach (array_keys(static::$map) as $k) {
            ksort(static::$map[$k]);
            foreach (static::$map[$k] as &$sub) {
                ksort($sub);
            }
        }
        return $this;
    }

    /**
     * Gets a list of entries of the map.
     *
     * @param string $entry
     *   The main array entry.
     * @param string|null $match
     *   (Optional) a match wildcard to limit the list.
     *
     * @return list<int|string>
     *   The list of the entries.
     */
    protected function listEntries(string $entry, ?string $match = null): array
    {
        if (!isset(static::$map[$entry])) {
            return [];
        }

        $list = array_keys(static::$map[$entry]);

        if (is_null($match)) {
            return $list;
        } else {
            $re = strtr($match, ['/' => '\\/', '*' => '.*']);
            return array_values(array_filter($list, function (int|string $v) use ($re): bool {
                return preg_match("/$re/", (string) $v) === 1;
            }));
        }
    }

    /**
     * Gets the content of an entry of the map.
     *
     * @param string $entry
     *   The main array entry.
     * @param string $entryKey
     *   The main entry value.
     *
     * @return array<string,array<string>>
     *   The values of the entry, or empty array if missing.
     */
    protected function getMapEntry(string $entry, string $entryKey): array
    {
        return static::$map[$entry][$entryKey] ?? [];
    }

    /**
     * Gets the content of a subentry of the map.
     *
     * @param string $entry
     *   The main array entry.
     * @param string $entryKey
     *   The main entry value.
     * @param string $subEntry
     *   The sub entry.
     *
     * @return array<int<0,max>,string>
     *   The values of the subentry, or empty array if missing.
     */
    protected function getMapSubEntry(string $entry, string $entryKey, string $subEntry): array
    {
        return static::$map[$entry][$entryKey][$subEntry] ?? [];
    }

    /**
     * Adds an entry to the map.
     *
     * Checks that no duplicate entries are made.
     *
     * @param string $entry
     *   The main array entry.
     * @param string $entryKey
     *   The main entry value.
     * @param string $subEntry
     *   The sub entry.
     * @param string $value
     *   The value to add.
     *
     * @return MapInterface<TMap>
     */
    protected function addMapSubEntry(string $entry, string $entryKey, string $subEntry, string $value): MapInterface
    {
        if (!isset(static::$map[$entry][$entryKey][$subEntry])) {
            // @phpstan-ignore assign.propertyType
            static::$map[$entry][$entryKey][$subEntry] = [$value];
        } else {
            if (array_search($value, static::$map[$entry][$entryKey][$subEntry]) === false) {
                // @phpstan-ignore assign.propertyType
                static::$map[$entry][$entryKey][$subEntry][] = $value;
            }
        }
        return $this;
    }

    /**
     * Removes an entry from the map.
     *
     * @param string $entry
     *   The main array entry.
     * @param string $entryKey
     *   The main entry value.
     * @param string $subEntry
     *   The sub entry.
     * @param string $value
     *   The value to remove.
     *
     * @return bool
     *   true if the entry was removed, false if the entry was not present.
     */
    protected function removeMapSubEntry(string $entry, string $entryKey, string $subEntry, string $value): bool
    {
        // Return false if no entry.
        if (!isset(static::$map[$entry][$entryKey][$subEntry])) {
            return false;
        }

        // Return false if no value.
        $k = array_search($value, static::$map[$entry][$entryKey][$subEntry]);
        if ($k === false) {
            return false;
        }

        // Remove the map sub entry key.
        // @phpstan-ignore assign.propertyType
        unset(static::$map[$entry][$entryKey][$subEntry][$k]);

        // Remove the sub entry if no more values.
        if (empty(static::$map[$entry][$entryKey][$subEntry])) {
            // @phpstan-ignore assign.propertyType
            unset(static::$map[$entry][$entryKey][$subEntry]);
        } else {
            // Resequence the remaining values.
            $tmp = [];
            foreach (static::$map[$entry][$entryKey][$subEntry] as $v) {
                $tmp[] = $v;
            }
            // @phpstan-ignore assign.propertyType
            static::$map[$entry][$entryKey][$subEntry] = $tmp;
        }

        // Remove the entry if no more values.
        if (empty(static::$map[$entry][$entryKey])) {
            // @phpstan-ignore assign.propertyType
            unset(static::$map[$entry][$entryKey]);
        }

        return true;
    }

    /**
     * Sets a value as the default for an entry.
     *
     * @param string $entry
     *   The main array entry.
     * @param string $entryKey
     *   The main entry value.
     * @param string $subEntry
     *   The sub entry.
     * @param string $value
     *   The value to add.
     *
     * @throws MappingException if no mapping found.
     *
     * @return MapInterface<TMap>
     */
    protected function setValueAsDefault(string $entry, string $entryKey, string $subEntry, string $value): MapInterface
    {
        // Throw exception if no entry.
        if (!isset(static::$map[$entry][$entryKey][$subEntry])) {
            throw new MappingException("Cannot set '{$value}' as default for '{$entryKey}', '{$entryKey}' not defined");
        }

        // Throw exception if no entry-value pair.
        $k = array_search($value, static::$map[$entry][$entryKey][$subEntry]);
        if ($k === false) {
            throw new MappingException("Cannot set '{$value}' as default for '{$entryKey}', '{$value}' not associated to '{$entryKey}'");
        }

        // Move value to top of array and resequence the rest.
        $tmp = [$value];
        foreach (static::$map[$entry][$entryKey][$subEntry] as $kk => $v) {
            if ($kk === $k) {
                continue;
            }
            $tmp[] = $v;
        }
        // @phpstan-ignore assign.propertyType
        static::$map[$entry][$entryKey][$subEntry] = $tmp;

        return $this;
    }
}
