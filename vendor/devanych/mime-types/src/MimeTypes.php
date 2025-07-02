<?php

declare(strict_types=1);

namespace Devanych\Mime;

use function array_unique;
use function array_merge;
use function strtolower;
use function trim;

final class MimeTypes implements MimeTypesInterface, MimeTypesMapsInterface
{
    use MimeTypesTrait;

    /**
     * @var array<string, string[]>
     */
    private array $extensions = [];

    /**
     * @var array<string, string[]>
     */
    private array $mimeTypes = [];

    /**
     * @param array<string, string[]> $map
     */
    public function __construct(array $map = [])
    {
        $this->addMap($map);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(string $mimeType): array
    {
        $lowerMime = strtolower(trim($mimeType));
        $extensions = self::EXTENSIONS[$lowerMime] ?? self::EXTENSIONS[$mimeType] ?? [];

        if ($this->extensions) {
            $customExtensions = $this->extensions[$lowerMime] ?? $this->extensions[$mimeType] ?? [];
            $extensions = $customExtensions ? array_unique(array_merge($customExtensions, $extensions)) : $extensions;
        }

        return $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeTypes(string $extension): array
    {
        $lowerExt = strtolower(trim($extension));
        $mimeTypes = self::MIME_TYPES[$lowerExt] ?? self::MIME_TYPES[$extension] ?? [];

        if ($this->mimeTypes) {
            $customMimeTypes = $this->mimeTypes[$lowerExt] ?? $this->mimeTypes[$extension] ?? [];
            $mimeTypes = $customMimeTypes ? array_unique(array_merge($customMimeTypes, $mimeTypes)) : $mimeTypes;
        }

        return $mimeTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function addMap(array $map): void
    {
        $this->addMapInternal($map);
    }
}
