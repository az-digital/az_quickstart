<?php

namespace Drupal\metatag\Plugin\migrate\process\d7;

use Drupal\Component\Serialization\Json;
use Drupal\metatag\Plugin\migrate\MigrateMetatagD7Trait;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Migrate entity data from Metatag on D7.
 *
 * Can also be used as follows to migrate the defaults from Metatag-D7, instead
 * of using the separate defaults migration plugin; this is purely academic but
 * might be useful for educational purposes.
 * @code
 * process:
 *   tags:
 *     -
 *       source: config
 *       plugin: d7_metatag_entities
 *     -
 *       plugin: callback
 *       callable:
 *         - '\Drupal\Component\Serialization\Json'
 *         - 'decode'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "d7_metatag_entities",
 *   handle_multiples = TRUE
 * )
 */
class MetatagEntities extends ProcessPluginBase {

  use MigrateMetatagD7Trait;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If there's no data, there's no need to store anything.
    if (empty($value)) {
      return NULL;
    }

    // Re-shape D7 entries into for D8 entries.
    $old_tags = $this->decodeValue($value);

    // Offload the transformation logic.
    $new_tags = $this->transformTags($old_tags, FALSE);

    // For entity data this needs to be in a JSON encoded string.
    return Json::encode($new_tags);
  }

}
