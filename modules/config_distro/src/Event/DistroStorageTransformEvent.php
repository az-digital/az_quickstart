<?php

namespace Drupal\config_distro\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Config\StorageInterface;

/**
 * Class DistroStorageTransformEvent.
 *
 * This event allows subscribers to alter the configuration of the storage that
 * is being transformed.
 */
class DistroStorageTransformEvent extends Event {

  /**
   * The configuration storage which is transformed.
   *
   * This storage can be interacted with by event subscribers and will be
   * used instead of the original storage after all event subscribers have been
   * called.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * DistroStorageTransformEvent constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage with the configuration to transform.
   */
  public function __construct(StorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * Returns the mutable storage ready to be read from and written to.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  public function getStorage() {
    return $this->storage;
  }

}
