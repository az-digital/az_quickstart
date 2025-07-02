<?php

namespace Drupal\ib_dam\AssetStorage;

use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Interface AssetStorageInterface.
 *
 * Describes how asset should be processed/transformed in a form,
 * that is suitable to saving/manipulating somewhere.
 *
 * @package Drupal\ib_dam\AssetStorage
 */
interface AssetStorageInterface {

  /**
   * Build and return asset storage item before manually saving it.
   *
   * This method used to build data before saving it to db,
   * or processing it a next level.
   *
   * Typical example build media object before it will be saved to db.
   * Another one - build text item for the text filter.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset.
   *
   * @return mixed
   *   Ready asset item for handling in a form that fits for a current case.
   */
  public function createStorage(AssetInterface $asset);

}
