<?php

namespace Drupal\config_split\Config;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\StorageInterface;

/**
 * Use all the overrides and logic from the factory but skip event dispatching.
 *
 * @internal This is not an API and may change without notice or BC concern.
 */
class EphemeralConfigFactory extends ConfigFactory {

  /**
   * Create an ephemeral factory from the factory service.
   *
   * @param \Drupal\Core\Config\ConfigFactory $service
   *   This is not the interface so that we can access its properties.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to base the ephemeral factory on.
   *
   * @return static
   */
  public static function fromService(ConfigFactory $service, StorageInterface $storage): self {
    // Construct the factory with a non-dispatching event dispatcher.
    $factory = new static($storage, $service->eventDispatcher, $service->typedConfigManager);
    // Steal the factory overrides. This only works because we get the service.
    $factory->configFactoryOverrides = $service->configFactoryOverrides;

    return $factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditable($name) {
    throw new \BadMethodCallException(sprintf("The method %s is not allowed", __METHOD__));
  }

  /**
   * The event dispatcher which doesn't do anything.
   *
   * @return \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   *   The event dispatcher.
   */
  protected static function eventDispatcher() {
    // We can use this class once Drupal 9.2 is the minimum supported version.
    return new class() implements EventDispatcherInterface {

      /**
       * {@inheritdoc}
       */
      public function dispatch(object $event, ?string $eventName = NULL): object {
        // Do nothing, just return the event.
        return $event;
      }

    };
  }

}
