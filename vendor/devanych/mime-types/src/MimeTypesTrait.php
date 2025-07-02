<?php

declare(strict_types=1);

namespace Devanych\Mime;

use InvalidArgumentException;

use function is_string;
use function is_array;
use function sprintf;
use function gettype;

trait MimeTypesTrait
{
    /**
     * @param array<string, string[]> $map
     * @see MimeTypesInterface::addMap()
     * @psalm-suppress DocblockTypeContradiction
     */
    private function addMapInternal(array $map): void
    {
        foreach ($map as $mimeType => $extensions) {
            if (!is_string($mimeType)) {
                throw new InvalidArgumentException(sprintf(
                    'MIME type MUST be string, received `%s`',
                    gettype($mimeType)
                ));
            }

            if (!is_array($extensions)) {
                throw new InvalidArgumentException(sprintf(
                    'Extensions MUST be array, received `%s`',
                    gettype($extensions)
                ));
            }

            $this->extensions[$mimeType] = $extensions;

            foreach ($extensions as $extension) {
                if (!is_string($extension)) {
                    throw new InvalidArgumentException(sprintf(
                        'Extension MUST be string, received `%s`',
                        gettype($extension)
                    ));
                }

                $this->mimeTypes[$extension][] = $mimeType;
            }
        }
    }
}
