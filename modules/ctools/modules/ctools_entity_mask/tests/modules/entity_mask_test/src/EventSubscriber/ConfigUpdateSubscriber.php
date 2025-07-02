<?php

namespace Drupal\entity_mask_test\EventSubscriber;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates the configuration data at Runtime.
 */
class ConfigUpdateSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an object.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if (!class_exists(DeprecationHelper::class)) {
      return;
    }
    $saved_config = $event->getConfig();

    // The revision field type of `block_content.type.*` is updated to boolean
    // from Drupal Core 10.3.x. So, we are updating the configuration
    // `block_content.type.basic` at runtime, so that `ConfigSchemaChecker`
    // event doesn't throw exceptions in Drupal 10.3.x and above.
    // @see https://www.drupal.org/i/3397493
    if ($saved_config->getName() == "block_content.type.basic") {
      if (\version_compare(\Drupal::VERSION, '10.3', '>=')) {
        $saved_config->set("revision", (bool) $saved_config->get("revision"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The priority is set as 256, so that this event is called before the
    // `ConfigSchemaChecker` event.
    // @see \Drupal\Core\Config\Development\ConfigSchemaChecker
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 256];
    return $events;
  }

}
