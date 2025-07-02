<?php

namespace Drupal\environment_indicator;

use Drupal\Core\Entity\BundlePermissionHandlerTrait;
use Drupal\environment_indicator\Entity\EnvironmentIndicator;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * EnvironmentIndicatorPermissions class.
 */
class EnvironmentIndicatorPermissions {

  use BundlePermissionHandlerTrait;
  use StringTranslationTrait;

  /**
   * Returns the dynamic permissions array.
   *
   * @return array
   *   The permissions configuration array.
   */
  public function permissions() {
    return $this->generatePermissions(EnvironmentIndicator::loadMultiple(), [$this, 'buildPermissions']);
  }

  /**
   * Returns a list of environment_indicator permissions for a given environment_indicator.
   *
   * @param \Drupal\environment_indicator\Entity\EnvironmentIndicator $environment
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EnvironmentIndicator $environment): array {
    $environment_id = $environment->id();
    $environment_params = ['%environment_name' => $environment->label()];

    return [
      'access environment indicator ' . $environment_id => [
        'title' => $this->t('See environment indicator for %environment_name', $environment_params)
      ]
    ];
  }
}
