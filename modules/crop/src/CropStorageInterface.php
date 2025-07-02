<?php

namespace Drupal\crop;

use Drupal\Core\Entity\EntityBundleListenerInterface;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Provides an interface defining an crop storage controller.
 */
interface CropStorageInterface extends SqlEntityStorageInterface, DynamicallyFieldableEntityStorageSchemaInterface, EntityBundleListenerInterface {

  /**
   * Retrieve crop ID based on image URI and crop type.
   *
   * @param string $uri
   *   URI of the image.
   * @param string $type
   *   Crop type.
   *
   * @return \Drupal\crop\CropInterface|null
   *   A Crop object or NULL if nothing matches the search parameters.
   */
  public function getCrop($uri, $type);

}
