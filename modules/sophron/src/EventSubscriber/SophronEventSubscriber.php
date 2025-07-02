<?php

declare(strict_types=1);

namespace Drupal\sophron\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\sophron\Event\MapEvent;
use FileEye\MimeMap\MalformedTypeException;
use FileEye\MimeMap\MapHandler;
use FileEye\MimeMap\MappingException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sophron's module Event Subscriber.
 */
class SophronEventSubscriber implements EventSubscriberInterface {

  /**
   * The module configuration settings.
   */
  protected ImmutableConfig $sophronSettings;

  /**
   * Constructs a SophronEventSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->sophronSettings = $this->configFactory->get('sophron.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MapEvent::INIT => 'initializeMap',
    ];
  }

  /**
   * Reacts to a 'sophron.map.initialize' event.
   *
   * Alters the map's mappings at run-time with additional commands stored in
   * the module settings.
   *
   * @param \Drupal\sophron\Event\MapEvent $event
   *   Sophron's map event.
   */
  public function initializeMap(MapEvent $event): void {
    $map_commands = $this->sophronSettings->get('map_commands') ?? [];
    $map = MapHandler::map($event->getMapClass());
    foreach ($map_commands as $command) {
      $method = $command['method'] ?? '';
      $args = $command['arguments'] ?? [];
      try {
        if (!is_callable([$map, $method])) {
          throw new \InvalidArgumentException("Non-existing mapping method '{$method}'");
        }
        call_user_func_array([$map, $method], $args);
      }
      catch (MappingException $e) {
        $event->addError((string) $method, (array) $args, 'Mapping', $e->getMessage());
      }
      catch (MalformedTypeException $e) {
        $event->addError((string) $method, (array) $args, 'Invalid MIME type syntax', $e->getMessage());
      }
      catch (\Exception $e) {
        $event->addError((string) $method, (array) $args, 'Other', $e->getMessage());
      }
      catch (\Error $e) {
        $event->addError((string) $method, (array) $args, 'Error', $e->getMessage());
      }
    }
    $map->sort();
  }

}
