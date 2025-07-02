<?php

namespace Drupal\ib_dam\Plugin\IbDam\AssetValidation;

use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\AssetValidation\AssetValidationBase;

/**
 * Validates an asset based on passed resource validators.
 *
 * @IbDamAssetValidation(
 *   id = "resource",
 *   label = @Translation("Resource validator")
 * )
 *
 * @package Drupal\ib_dam\Plugin\ibDam\AssetValidation
 */
class Resource extends AssetValidationBase {

  /**
   * Resource type validator.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object to validate.
   * @param array $options
   *   Validator options with such options:
   *   - 'type': resource type,
   *   - 'allowed': is allowed resource type.
   *
   * @return array
   *   An array with validation messages.
   */
  public function validateIsAllowedResourceType(AssetInterface $asset, array $options) {
    $errors = [];
    if ($asset->getSourceType() === $options['type'] && !$options['allowed']) {
      $errors[] = $this->t('%source_type source type is not available or restricted by configuration.', [
        '%source_type' => ucwords($asset->getSourceType()),
      ]);
    }
    return $errors;
  }

}
