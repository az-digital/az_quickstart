<?php

declare(strict_types=1);

namespace Devanych\Mime;

interface MimeTypesInterface
{
    /**
     * Gets the file extensions for the given MIME type.
     *
     * @param string $mimeType
     * @return string[] an array of extensions or an empty array if no match is found
     */
    public function getExtensions(string $mimeType): array;

    /**
     * Gets the MIME types for the given file extension.
     *
     * @param string $extension
     * @return string[] an array of MIME types or an empty array if no match is found
     */
    public function getMimeTypes(string $extension): array;

    /**
     * Adds a custom map of MIME types and file extensions.
     *
     * The key is a MIME type and the value is an array of extensions.
     * Example code:
     * ```php
     * $map = [
     *  'image/ico' => ['ico'],
     *  'image/icon' => ['ico'],
     *  'image/jp2' => ['jp2', 'jpg2'],
     *  'image/jpeg' => ['jpeg', 'jpg', 'jpe'],
     *  'image/jpeg2000' => ['jp2', 'jpg2'],
     * ];
     * ```
     * If the map format is invalid, an `\InvalidArgumentException` will be thrown when the map is added.
     *
     * @param array<string, string[]> $map
     */
    public function addMap(array $map): void;
}
