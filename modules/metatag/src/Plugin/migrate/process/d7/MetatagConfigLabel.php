<?php

namespace Drupal\metatag\Plugin\migrate\process\d7;

use Drupal\Component\Serialization\Json;
use Drupal\metatag\Plugin\migrate\MigrateMetatagD7Trait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Convert the label of a Metatag config definition from the D7 syntax.
 *
 * Config labels on D7 config entities were based on entity definitions, whereas
 * in D8+ they need a label.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_metatag_config_label",
 *   handle_multiples = TRUE
 * )
 */
class MetatagConfigLabel extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If there's no data, there's no need to store anything.
    if (empty($value)) {
      return NULL;
    }

    $value = str_replace(':', ': ', $value);
    $value = str_replace('_', ' ', $value);

    return ucwords($value);
  }

}
