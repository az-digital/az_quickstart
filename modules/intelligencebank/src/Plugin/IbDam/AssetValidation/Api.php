<?php

namespace Drupal\ib_dam\Plugin\IbDam\AssetValidation;

use Drupal\ib_dam\Asset\LocalAsset;
use Drupal\ib_dam\AssetValidation\AssetValidationBase;

/**
 * Validates an asset based on passed api validators.
 *
 * @IbDamAssetValidation(
 *   id = "api",
 *   label = @Translation("Api validator")
 * )
 *
 * @package Drupal\ib_dam\Plugin\ibDam\AssetValidation
 */
class Api extends AssetValidationBase {

  /**
   * API auth key validator.
   *
   * @param \Drupal\ib_dam\Asset\LocalAsset $asset
   *   The asset object to validate.
   *
   * @return array
   *   An array with validation messages.
   */
  public function validateApiAuthKey(LocalAsset $asset) {
    return [];
  }

}
