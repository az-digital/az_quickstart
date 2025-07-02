<?php

namespace Drupal\config_distro_filter;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageTransformEvent;
use Drupal\config_filter\ConfigFilterStorageFactory;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides event subscriber for ConfigDistroFilter.
 */
class ConfigDistroFilterEventSubscriber implements EventSubscriberInterface {

  use StorageCopyTrait;

  /**
   * The filter storage factory.
   *
   * @var \Drupal\config_filter\ConfigFilterStorageFactory
   */
  protected $filterStorageFactory;

  /**
   * ConfigFilterEventSubscriber constructor.
   *
   * @param \Drupal\config_filter\ConfigFilterStorageFactory $filterStorageFactory
   *   The filter storage factory.
   */
  public function __construct(ConfigFilterStorageFactory $filterStorageFactory) {
    $this->filterStorageFactory = $filterStorageFactory;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\config_distro\Event\DistroStorageTransformEvent $event
   *   The DistroStorageTransformEvent.
   */
  public function onDistroTransform(DistroStorageTransformEvent $event) {
    $storage = $event->getStorage();
    // The temporary storage representing the active storage.
    $temp = new MemoryStorage();
    // Get the filtered storage based on the event storage.
    $filtered = $this->filterStorageFactory->getFilteredStorage($storage, ['config_distro.storage.distro']);
    // Simulate the importing of configuration.
    self::replaceStorageContents($filtered, $temp);
    // Set the event storage to the one of the simulated import.
    self::replaceStorageContents($temp, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigDistroEvents::TRANSFORM][] = ['onDistroTransform'];
    return $events;
  }

}
