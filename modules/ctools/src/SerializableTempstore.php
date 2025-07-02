<?php

namespace Drupal\ctools;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\TempStore\SharedTempStore;

/**
 * An extension of the SharedTempStore system for serialized data.
 *
 * @deprecated in ctools 8.x-3.10. Will be removed before ctools:4.0.0.
 *   Use \Drupal\Core\TempStore\SharedTempStore instead.
 */
class SerializableTempstore extends SharedTempStore {
  use DependencySerializationTrait;

}
