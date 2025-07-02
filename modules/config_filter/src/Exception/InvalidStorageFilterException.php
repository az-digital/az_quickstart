<?php

namespace Drupal\config_filter\Exception;

use Drupal\config_filter\Config\StorageFilterInterface;

/**
 * Thrown when a StorageFilterInterface is expected but not present.
 */
class InvalidStorageFilterException extends \InvalidArgumentException {

  /**
   * InvalidStorageFilterException constructor.
   */
  public function __construct() {
    parent::__construct("An argument does not implement " . StorageFilterInterface::class);
  }

}
