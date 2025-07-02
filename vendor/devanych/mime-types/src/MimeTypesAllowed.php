<?php

declare(strict_types=1);

namespace Devanych\Mime;

use InvalidArgumentException;
use LogicException;

use function array_unique;
use function strtolower;
use function trim;

final class MimeTypesAllowed implements MimeTypesInterface
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
    public function __construct(array $map)
    {
        if (empty($map)) {
            throw new InvalidArgumentException('Map with allowed mime types cannot be empty');
        }

        $this->addMap($map);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions(string $mimeType): array
    {
        $lowerMime = strtolower(trim($mimeType));
        return array_unique($this->extensions[$lowerMime] ?? $this->extensions[$mimeType] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeTypes(string $extension): array
    {
        $lowerExt = strtolower(trim($extension));
        return array_unique($this->mimeTypes[$lowerExt] ?? $this->mimeTypes[$extension] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function addMap(array $map): void
    {
        if ($this->extensions !== [] || $this->mimeTypes !== []) {
            throw new LogicException('Map with allowed mime types already added');
        }

        $this->addMapInternal($map);
    }
}
