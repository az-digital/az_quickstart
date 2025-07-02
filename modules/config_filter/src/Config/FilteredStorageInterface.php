<?php

namespace Drupal\config_filter\Config;

use Drupal\Core\Config\StorageInterface;

/**
 * Essentially the StorageInterface, but knowing that config_filter is used.
 */
interface FilteredStorageInterface extends StorageInterface {

}
