<?php

namespace Drupal\upgrade_status;

use Drupal\Core\Site\Settings;

/**
 * Filters a RecursiveDirectoryIterator to discover Drupal twig template files.
 */
class TwigRecursiveIterator extends \RecursiveIteratorIterator {

  /**
   * TwigRecursiveIteratorIterator constructor.
   *
   * @param string $directory
   *   Directory to search files.
   */
  public function __construct(string $directory) {
    $exclude = Settings::get('file_scan_ignore_directories', []);
    parent::__construct(new \RecursiveCallbackFilterIterator(
      new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
      function ($current) use ($exclude) {
        $name = $current->getFilename();
        // RecursiveDirectoryIterator::SKIP_DOTS only skips '.' and '..', but
        // not hidden directories (like '.git').
        return $name[0] !== '.' &&
          (($current->isDir() && !in_array($name, $exclude, TRUE)) ||
            ($current->isFile() && substr($name, -10) === '.html.twig'));
      }
    ), \RecursiveIteratorIterator::LEAVES_ONLY);
  }

}
