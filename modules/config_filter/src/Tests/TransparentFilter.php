<?php

namespace Drupal\config_filter\Tests;

use Drupal\config_filter\Config\StorageFilterInterface;
use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * A transparent filter.
 */
class TransparentFilter extends ConfigFilterBase implements StorageFilterInterface {

  /**
   * TransparentFilter constructor.
   */
  public function __construct() {
    parent::__construct([], 'transparent_test', []);
  }

  /**
   * Get the read-only source Storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The source storage.
   */
  public function getPrivateSourceStorage() {
    return $this->getSourceStorage();
  }

  /**
   * Get the decorator storage which applies the filters.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The filtered decorator storage.
   */
  public function getPrivateFilteredStorage() {
    return $this->getFilteredStorage();
  }

}
