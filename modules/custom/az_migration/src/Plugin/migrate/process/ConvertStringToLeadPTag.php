<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to wrap subhead with markup.
 *
 * This process plugin is meant to convert a plain text string to a Arizona
 * Bootstrap lead paragraph.  There is most likely no real use for this outside
 * of az_quickstart
 *
 * @code
 *   process:
 *     field_example:
 *       plugin: convert_string_to_lead_p_tag
 *       source: example
 * @endcode

 * @MigrateProcessPlugin(
 *   id = "convert_string_to_lead_p_tag"
 * )
 */
class ConvertStringToLeadPTag extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $value = '<p class="lead">' . $value . '</p>';

    return $value;
  }

}
