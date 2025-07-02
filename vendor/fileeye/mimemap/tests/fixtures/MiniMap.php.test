<?php declare(strict_types=1);

namespace FileEye\MimeMap\Map;

/**
 * Class for mapping file extensions to MIME types.
 *
 * This class has minimum mapping defined. It is used for testing purposes.
 */
class MiniMap extends AbstractMap
{
    protected static $instance;

    public function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Mapping between file extensions and MIME types.
     *
     * The array has three main keys, 't' that stores MIME types, 'e' that map
     * file extensions to MIME types, and 'a' that store MIME type aliases.
     *
     * The entire map is created automatically by running
     *  $ fileye-mimemap update [URL] [YAML] [FILE]
     * on the command line.
     * The utility application fetches the map from the Apache HTTPD
     * documentation website, and integrates its definitions with any further
     * specifications contained in the YAML file.
     *
     * DO NOT CHANGE THE MAPPING ARRAY MANUALLY.
     *
     * @internal
     *
     * @var array<string, array<int|string, array<string, array<int,string>>>>
     */
    // phpcs:disable
    protected static $map = array (
  't' =>
  array (
    'application/andrew-inset' =>
    array (
      'desc' =>
      array (
        0 => 'ATK inset',
        1 => 'ATK: Andrew Toolkit',
      ),
      'e' =>
      array (
        0 => 'ez',
      ),
    ),
  ),
  'e' =>
  array (
    'ez' =>
    array (
      't' =>
      array (
        0 => 'application/andrew-inset',
      ),
    ),
  ),
);
    // phpcs:enable
}
