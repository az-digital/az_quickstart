<?php declare(strict_types=1);

namespace FileEye\MimeMap;

use FileEye\MimeMap\Map\MimeMapInterface;

/**
 * Interface for Extension objects.
 */
interface ExtensionInterface
{
    /**
     * Constructor.
     *
     * @param string $extension
     *   A file extension.
     * @param class-string<MimeMapInterface>|null $mapClass
     *   (Optional) The FQCN of the map class to use.
     *
     * @api
     */
    public function __construct(string $extension, ?string $mapClass = null);

    /**
     * Returns the file extension's preferred MIME type.
     *
     * @throws MappingException if no mapping found.
     *
     * @api
     */
    public function getDefaultType(): string;

    /**
     * Returns all the MIME types related to the file extension.
     *
     * @throws MappingException if no mapping found.
     *
     * @return list<string>
     *
     * @api
     */
    public function getTypes(): array;
}
