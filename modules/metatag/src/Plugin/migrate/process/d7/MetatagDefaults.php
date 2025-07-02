<?php

namespace Drupal\metatag\Plugin\migrate\process\d7;

use Drupal\Component\Serialization\Json;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Migrate default configurations from Metatag on D7.
 *
 * @MigrateProcessPlugin(
 *   id = "d7_metatag_defaults",
 *   handle_multiples = TRUE
 * )
 */
class MetatagDefaults extends MetatagEntities {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If there's no data, there's no need to store anything.
    if (empty($value)) {
      return NULL;
    }

    // Leverage the entities migration logic.
    $new_tags = parent::transform($value, $migrate_executable, $row, $destination_property);

    // Data from the parent transformation will be in JSON format, so decode it.
    return Json::decode($new_tags);
  }

}
