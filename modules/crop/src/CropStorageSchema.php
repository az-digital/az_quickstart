<?php

namespace Drupal\crop;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the crop schema handler.
 */
class CropStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    if ($data_table = $this->storage->getDataTable()) {
      $schema[$data_table]['indexes'] += [
        'crop__uri_type' => ['uri', 'type'],
      ];
    }

    return $schema;
  }

}
