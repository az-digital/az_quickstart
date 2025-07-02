<?php declare(strict_types=1);

namespace FileEye\MimeMap\Map;

/**
 * Interface for maps.
 *
 * @template TMap of GenericMap
 */
interface MapInterface
{
    /**
     * Constructor.
     */
    public function __construct();

    /**
     * Returns the singleton.
     *
     * @return MapInterface<TMap>
     */
    public static function getInstance(): MapInterface;

    /**
     * Returns the map's class fully qualified filename.
     */
    public function getFileName(): string;

    /**
     * Gets the map array.
     *
     * @return TMap
     */
    public function getMapArray(): array;

    /**
     * Sorts the map.
     *
     * @return MapInterface<TMap>
     */
    public function sort(): MapInterface;

    /**
     * Backs up the map array.
     */
    public function backup(): void;

    /**
     * Resets the map array to the backup.
     */
    public function reset(): void;
}
