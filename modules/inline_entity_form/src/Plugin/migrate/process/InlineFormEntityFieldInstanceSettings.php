<?php

namespace Drupal\inline_entity_form\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for inline_form_entity.
 *
 * @MigrateProcessPlugin(
 *   id = "inline_entity_form_field_instance_settings"
 * )
 */
class InlineFormEntityFieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') === 'entityreference') {
      $widget = $row->get('widget/type');
      if ($widget === 'inline_entity_form_single' || $widget === 'inline_entity_form') {
        $target_bundles = $row->get('target_bundles');
        // The previous process,
        // \Drupal\Tests\field\Unit\Plugin\migrate\process\d7\FieldInstanceSettingsTest
        // sets target_bundles to NULL when the source target_bundles is an
        // empty array.
        if (is_null($value['handler_settings']['target_bundles'])) {
          $value['handler_settings']['target_bundles'] = $target_bundles;
        }
      }
    }
    return $value;
  }

}
