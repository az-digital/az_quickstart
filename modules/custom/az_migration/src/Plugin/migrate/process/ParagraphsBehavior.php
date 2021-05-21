<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure Behavior for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_behavior_settings"
 * )
 */
class ParagraphsBehavior extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Setting the behavior to the paragraph.
    $value['behavior'] = serialize(['az_display_settings' => ['bottom_spacing' => $row->getSourceProperty('bottom_spacing')]]);
    return $value['behavior'];
  }

}
