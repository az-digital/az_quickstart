<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts a user-entered link string into a Canvas link prop value.
 *
 * Follows the same rules as core's link widget for user-entered strings, so
 * '/about' becomes 'internal:/about' and '<front>' becomes 'internal:/'. A
 * bare '#' becomes 'internal:#top', because a fragment with no name is not a
 * valid uri-reference. Returns NULL for an empty value.
 *
 * @code
 * inputs/link_url:
 *   plugin: az_canvas_link
 *   source: link_uri
 * @endcode
 *
 * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget::getUserEnteredStringAsUri()
 */
#[MigrateProcess('az_canvas_link')]
class CanvasLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $link = trim((string) $value);
    if ($link === '') {
      return NULL;
    }
    if ($link === '<front>') {
      $uri = 'internal:/';
    }
    elseif ($link === '#') {
      $uri = 'internal:#top';
    }
    elseif (parse_url($link, PHP_URL_SCHEME) !== NULL) {
      $uri = $link;
    }
    else {
      $uri = 'internal:' . (in_array($link[0], ['/', '?', '#'], TRUE) ? $link : '/' . $link);
    }
    return ['uri' => $uri, 'options' => []];
  }

}
