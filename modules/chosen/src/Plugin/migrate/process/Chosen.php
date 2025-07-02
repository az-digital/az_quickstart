<?php

namespace Drupal\chosen\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Changes widget type based on source field's widget type.
 *
 * @MigrateProcessPlugin(
 *   id = "chosen",
 *   handle_multiples = TRUE
 * )
 */
class Chosen extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value !== 'options_select' || empty($row->getSourceProperty('widget/settings/apply_chosen'))) {
      return $value;
    }
    return 'chosen_select';
  }

}
