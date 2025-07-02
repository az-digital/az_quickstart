<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

/**
 * Defines an interface for data parsers.
 *
 * @see \Drupal\migrate_plus\Annotation\DataParser
 * @see \Drupal\migrate_plus\DataParserPluginBase
 * @see \Drupal\migrate_plus\DataParserPluginManager
 * @see plugin_api
 */
interface DataParserPluginInterface extends \Iterator, \Countable {

  /**
   * Returns current source URL.
   *
   * @return string|null
   *   The URL currently parsed on success, otherwise NULL.
   */
  public function currentUrl(): ?string;

}
