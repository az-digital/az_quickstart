<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Records a notice for a non-empty source value that is not migrated.
 *
 * Use it for content that has no Canvas equivalent, so editors can see what
 * to recreate by hand. The notice is saved as a migrate message for the row;
 * the plugin always returns NULL.
 *
 * Available configuration keys:
 * - message: The notice. '@value' is replaced with the source value.
 *
 * @code
 * _title_notice:
 *   plugin: az_canvas_notice
 *   source: field_az_title/0/value
 *   message: 'The title "@value" was not converted.'
 * @endcode
 */
#[MigrateProcess('az_canvas_notice')]
class CanvasNotice extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = is_scalar($value) ? trim(strip_tags((string) $value)) : '';
    if ($value !== '') {
      // Plain text: the message is escaped wherever it is displayed.
      $message = strtr($this->configuration['message'] ?? 'The value "@value" was not converted.', ['@value' => $value]);
      $migrate_executable->saveMessage($message, MigrationInterface::MESSAGE_NOTICE);
    }
    return NULL;
  }

}
