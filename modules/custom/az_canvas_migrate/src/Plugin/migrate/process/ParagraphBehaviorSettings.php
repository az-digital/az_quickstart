<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Unserializes a paragraph's stored behavior settings.
 *
 * Available configuration keys:
 * - behavior: (optional) A paragraph behavior plugin ID. If set, only that
 *   behavior's settings are returned.
 *
 * Returns an array, so nested settings can be read with '@' paths, e.g.
 * '@_behavior/az_display_settings/bottom_spacing'.
 *
 * @code
 * _behavior:
 *   plugin: az_paragraph_behavior_settings
 *   source: behavior_settings/0/value
 *   behavior: az_rankings_paragraph_behavior
 * @endcode
 */
#[MigrateProcess('az_paragraph_behavior_settings')]
class ParagraphBehaviorSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $settings = is_string($value) && $value !== '' ? unserialize($value, ['allowed_classes' => FALSE]) : [];
    $settings = is_array($settings) ? $settings : [];
    if (isset($this->configuration['behavior'])) {
      $settings = $settings[$this->configuration['behavior']] ?? [];
    }
    return is_array($settings) ? $settings : [];
  }

}
