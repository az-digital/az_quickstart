<?php

namespace Drupal\config_distro;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageTransformEvent;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageManagerInterface;
use Drupal\Core\Config\StorageTransformerException;
use Drupal\Core\Lock\LockBackendInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The distro storage manager dispatches an event for the distro storage.
 *
 * This class is not meant to be extended and is final to make sure the
 * constructor and the getStorage method are both changed when this pattern is
 * used in other circumstances.
 */
final class DistroStorageManager implements StorageManagerInterface {

  use StorageCopyTrait;

  /**
   * The name used to identify the lock.
   */
  const LOCK_NAME = 'distro_storage_manager';

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * The database storage.
   *
   * @var \Drupal\Core\Config\DatabaseStorage
   */
  protected $storage;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * DistroStorageManager constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active config storage to prime the export storage.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The used lock backend instance.
   */
  public function __construct(StorageInterface $active, EventDispatcherInterface $event_dispatcher, LockBackendInterface $lock) {
    $this->active = $active;
    $this->eventDispatcher = $event_dispatcher;
    $this->lock = $lock;
    // The point of this service is to provide the storage and dispatch the
    // event when needed, so the storage itself can not be a service.
    $this->storage = new MemoryStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    // Acquire a lock for the request to assert that the storage does not change
    // when a concurrent request transforms the storage.
    if (!$this->lock->acquire(self::LOCK_NAME)) {
      $this->lock->wait(self::LOCK_NAME);
      if (!$this->lock->acquire(self::LOCK_NAME)) {
        throw new StorageTransformerException("Cannot acquire config distro transformer lock.");
      }
    }

    self::replaceStorageContents($this->active, $this->storage);
    $this->eventDispatcher->dispatch(new DistroStorageTransformEvent($this->storage), ConfigDistroEvents::TRANSFORM);

    return new ReadOnlyStorage($this->storage);
  }

}
