<?php

namespace Drupal\metatag\Plugin\migrate\process\d7;

use Drupal\Component\Serialization\Json;
use Drupal\metatag\Plugin\migrate\MigrateMetatagD7Trait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Convert the ID of a Metatag config definition from the D7 syntax.
 *
 * Config IDs in D7 had the format "ENTITY:BUNDLE". In D8+ they have the
 * format "ENTITY__BUNDLE".
 *
 * @MigrateProcessPlugin(
 *   id = "d7_metatag_config_id",
 *   handle_multiples = TRUE
 * )
 */
class MetatagConfigId extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If there's no data, there's no need to store anything.
    if (empty($value)) {
      return NULL;
    }

    return str_replace(':', '__', $value);
  }

}
