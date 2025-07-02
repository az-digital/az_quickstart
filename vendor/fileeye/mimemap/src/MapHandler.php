<?php declare(strict_types=1);

namespace FileEye\MimeMap;

use FileEye\MimeMap\Map\DefaultMap;
use FileEye\MimeMap\Map\MimeMapInterface;

/**
 * Class for managing map singletons.
 */
abstract class MapHandler
{
    /**
     * The default map PHP class.
     */
    const DEFAULT_MAP_CLASS = DefaultMap::class;

    /**
     * The default map class to use.
     *
     * It can be overridden by ::setDefaultMapClass.
     *
     * @var class-string<MimeMapInterface>
     */
    protected static string $defaultMapClass = self::DEFAULT_MAP_CLASS;

    /**
     * Sets a map class as default for new instances.
     *
     * @param class-string<MimeMapInterface> $mapClass A FQCN.
     */
    public static function setDefaultMapClass(string $mapClass): void
    {
        static::$defaultMapClass = $mapClass;
    }

    /**
     * Returns the map instance.
     *
     * @param class-string<MimeMapInterface>|null $mapClass
     *   (Optional) The map FQCN to be used. If null, the default map will be
     *   used.
     */
    public static function map(?string $mapClass = null): MimeMapInterface
    {
        if ($mapClass === null) {
            $mapClass = static::$defaultMapClass;
        }
        $instance = $mapClass::getInstance();
        assert($instance instanceof MimeMapInterface);
        return $instance;
    }
}
