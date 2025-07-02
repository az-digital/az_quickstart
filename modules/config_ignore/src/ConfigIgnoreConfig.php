<?php

declare(strict_types=1);

namespace Drupal\config_ignore;

use Drupal\Core\Config\Config;

/**
 * This is a representation of the config of config ignore.
 */
final class ConfigIgnoreConfig {

  /**
   * The patterns, keyed by the operation and direction.
   *
   * @var string[][][]
   */
  private array $data;

  /**
   * The constructor.
   *
   * @param string $mode
   *   The mode: simple, intermediate or advanced.
   * @param string[]|string[][]|string[][][] $data
   *   The data depending on the mode.
   */
  public function __construct(string $mode, array $data) {
    // Cascade transforming the data to the advanced case.
    switch ($mode) {
      case 'simple':
        $data = [
          'import' => $data,
          'export' => $data,
        ];
      case 'intermediate':
        $data = [
          'create' => $data,
          'update' => $data,
          'delete' => $data,
        ];
      case 'advanced':
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Invalid mode for config ignore "%s"', $mode));
    }
    // Sort and validate the data.
    $sorted = [];
    foreach (['create', 'update', 'delete'] as $op) {
      foreach (['import', 'export'] as $dir) {
        if (!isset($data[$op][$dir]) || !is_array($data[$op][$dir])) {
          throw new \InvalidArgumentException(sprintf('Invalid data for config ignore %s %s list', $op, $dir));
        }
        // Sort the list.
        $sorted[$op][$dir] = self::cleanList($data[$op][$dir]);
      }
    }
    $this->data = $sorted;
  }

  /**
   * Create the object based on the configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration for config ignore.
   *
   * @return self
   *   The object.
   */
  public static function fromConfig(Config $config): self {
    return new self($config->get('mode') ?? 'simple', $config->get('ignored_config_entities') ?? []);
  }

  /**
   * Merge the config with another object.
   *
   * @param \Drupal\config_ignore\ConfigIgnoreConfig $other
   *   The other object.
   *
   * @return self
   *   The object
   */
  public function mergeWith(ConfigIgnoreConfig $other): self {
    foreach (['create', 'update', 'delete'] as $op) {
      foreach (['import', 'export'] as $dir) {
        $this->data[$op][$dir] = self::cleanList(array_merge($this->data[$op][$dir], $other->data[$op][$dir]));
      }
    }

    return $this;
  }

  /**
   * Get the list of patterns.
   *
   * @param string $direction
   *   The direction: import or export.
   * @param string $operation
   *   The operation: create, update or delete.
   * @param bool $sorted
   *   Whether to sort the list.
   *
   * @return string[]
   *   The patterns to ignore.
   */
  public function getList(string $direction, string $operation, bool $sorted = TRUE): array {
    assert(in_array($direction, ['import', 'export']));
    assert(in_array($operation, ['create', 'update', 'delete']));

    $list = $this->data[$operation][$direction];
    if ($sorted) {
      sort($list);
    }

    return $list;
  }

  /**
   * Set a list of patterns.
   *
   * @param string $direction
   *   The direction: import or export.
   * @param string $operation
   *   The operation: create, update or delete.
   * @param string[] $list
   *   The list of patterns.
   *
   * @return self
   *   The object for chaining.
   */
  public function setList(string $direction, string $operation, array $list): self {
    assert(in_array($direction, ['import', 'export']));
    assert(in_array($operation, ['create', 'update', 'delete']));

    $this->data[$operation][$direction] = self::cleanList($list);

    return $this;
  }

  /**
   * Get the data for a given mode.
   *
   * @param string $mode
   *   The mode.
   *
   * @return string[]|string[][]|string[][][]
   *   The data formatted according to the mode.
   */
  public function getFormated(string $mode): array {
    switch ($mode) {
      case 'simple':
        return $this->getList('import', 'update');

      case 'intermediate':
        return [
          'import' => $this->getList('import', 'update'),
          'export' => $this->getList('export', 'update'),
        ];

      case 'advanced':
        // We return the list sorted.
        return [
          'create' => [
            'import' => $this->getList('import', 'create'),
            'export' => $this->getList('export', 'create'),
          ],
          'update' => [
            'import' => $this->getList('import', 'update'),
            'export' => $this->getList('export', 'update'),
          ],
          'delete' => [
            'import' => $this->getList('import', 'delete'),
            'export' => $this->getList('export', 'delete'),
          ],
        ];
    }
    throw new \InvalidArgumentException(sprintf('Invalid mode "%s"', $mode));
  }

  /**
   * Check if config is ignored.
   *
   * @param string $collection
   *   The collection.
   * @param string $name
   *   The config name.
   * @param string $direction
   *   The direction: import or export.
   * @param string $operation
   *   The operation: create, update or delete.
   *
   * @return string[]|bool
   *   Boolean if the whole config is to be ignored, or an array of parts.
   */
  public function isIgnored(string $collection, string $name, string $direction, string $operation) {
    $parts = [];
    foreach ($this->data[$operation][$direction] as $pattern) {
      // Get collection from the pattern.
      $collection_pattern = '*';
      if (strpos($pattern, '|') !== FALSE) {
        // Divide the pattern into collection and pattern.
        [$collection_pattern, $pattern] = explode('|', $pattern, 2);
        $pattern = trim($pattern);

        // If collection pattern includes a wildcard then move it to the
        // config name pattern.
        if ($collection_pattern[0] === '~') {
          $collection_pattern = ltrim($collection_pattern, '~');
          $pattern = '~' . ltrim($pattern, '~');
        }
      }

      // Skip if the collection pattern does not match.
      if (!self::wildcardMatch($collection_pattern, $collection)) {
        continue;
      }

      // Early return when the line matches.
      if ($pattern === '~' . $name) {
        return FALSE;
      }
      if ($pattern === $name && empty($parts)) {
        // The exceptions are sorted first so if we check a pattern that matches
        // and there is no parts which are not ignored, the whole config is.
        return TRUE;
      }

      $ignore = $pattern[0] !== '~';
      $pattern = ltrim($pattern, '~');

      // Check for patterns with keys to ignore.
      $config_name_pattern = $pattern;
      $key = '*';
      if (strpos($pattern, ':') !== FALSE) {
        // Some patterns are defining also a key.
        [$config_name_pattern, $key] = explode(':', $pattern, 2);
        $key = trim($key);
      }

      // Check for wildcard matches.
      if (self::wildcardMatch($config_name_pattern, $name)) {
        if (!$ignore) {
          // Add the tilde to make the key excluded.
          $key = '~' . ltrim($key, '~');
        }
        $parts[] = $key;
      }
    }

    if (!empty($parts)) {
      return self::simplifyParts($parts);
    }
    return FALSE;
  }

  /**
   * Check for wild cards.
   *
   * @param string $pattern
   *   The pattern to match.
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   Whether the pattern matches.
   */
  protected static function wildcardMatch(string $pattern, string $string): bool {
    $pattern = '/^' . preg_quote($pattern, '/') . '$/';
    $pattern = str_replace('\*', '.*', $pattern);
    return (bool) preg_match($pattern, $string);
  }

  /**
   * Simplify the parts.
   *
   * @param array $parts
   *   The patterns for a given config.
   *
   * @return string[]|bool
   *   The result.
   */
  protected static function simplifyParts(array $parts) {
    if (in_array('~*', $parts, TRUE)) {
      // If everything is excluded then other patterns parts don't matter.
      return FALSE;
    }
    if (in_array('*', $parts, TRUE)) {
      // Filter out all parts that are not exceptions.
      $parts = array_filter($parts, fn($i) => /* str_starts_with($i, '~') */ 0 === strncmp($i, '~', \strlen('~')));
      if (empty($parts)) {
        return TRUE;
      }
      $parts[] = '*';
    }
    // Sort the tilde to the top.
    $parts = self::cleanList($parts);

    return $parts;
  }

  /**
   * Sort and filter out empty or bogus lines.
   *
   * @param string[] $list
   *   The list of patterns.
   *
   * @return string[]
   *   The sorted list.
   */
  protected static function cleanList(array $list): array {
    usort($list, fn ($a, $b) => self::sortConfigList($a, $b));
    return array_filter($list, fn($i) => !in_array($i, ['', '~']));
  }

  /**
   * Sorting function which puts exception patterns first.
   *
   * @param string $a
   *   The first element.
   * @param string $b
   *   The second element.
   *
   * @return int
   *   The result.
   */
  protected static function sortConfigList(string $a, string $b): int {
    if ($a === '' || $b === '') {
      return strlen($a) <=> strlen($b);
    }
    if ($a === '~' || $b === '~') {
      return strlen($a) <=> strlen($b);
    }
    if ($a[0] == '~' && $b[0] == '~') {
      return self::sortConfigList(substr($a, 1), substr($b, 1));
    }
    if ($a[0] == '~') {
      return -1;
    }
    if ($b[0] == '~') {
      return 1;
    }
    return strcmp($a, $b);
  }

}
