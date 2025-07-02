<?php declare(strict_types=1);

namespace FileEye\MimeMap;

use FileEye\MimeMap\Map\MimeMapInterface;

/**
 * Class for mapping file extensions to MIME types.
 */
class Extension implements ExtensionInterface
{
    /**
     * The file extension.
     */
    protected readonly string $extension;

    /**
     * The MIME types map.
     */
    protected readonly MimeMapInterface $map;

    public function __construct(string $extension, ?string $mapClass = null)
    {
        $this->extension = strtolower($extension);
        $this->map = MapHandler::map($mapClass);
    }

    public function getDefaultType(): string
    {
        return $this->getTypes()[0];
    }

    public function getTypes(): array
    {
        $types = $this->map->getExtensionTypes($this->extension);
        if (!empty($types)) {
            return $types;
        }
        throw new MappingException('No MIME type mapped to extension ' . $this->extension);
    }
}
