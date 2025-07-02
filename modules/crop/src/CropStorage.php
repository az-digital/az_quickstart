<?php

namespace Drupal\crop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for image crop storage.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for crop entities.
 */
class CropStorage extends SqlContentEntityStorage implements CropStorageInterface {

  /**
   * Statically cached crops, keyed by URI first and type second.
   *
   * @var int[][]
   */
  protected $cropsByUri = [];

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    // Saving a crop might affect the cached information, reset it.
    $this->cropsByUri = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCrop($uri, $type) {
    if (!isset($this->cropsByUri[$uri])) {
      $query = $this->database->select('crop_field_data', 'cfd');
      $query->fields('cfd', ['type', 'cid']);
      $query->condition('cfd.uri', $uri);
      $this->cropsByUri[$uri] = $query->execute()->fetchAllKeyed();
    }

    if (isset($this->cropsByUri[$uri][$type])) {
      return $this->load($this->cropsByUri[$uri][$type]);
    }
    return NULL;
  }

}
