<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * GoogleTagEvent interface.
 */
interface GoogleTagEventInterface extends ContextAwarePluginInterface, ConfigurableInterface {

  /**
   * Returns name of the event.
   *
   * @return string
   *   Event name.
   */
  public function getName(): string;

  /**
   * Returns data associated with this event.
   *
   * @phpstan-return array<string, mixed>
   *   Data.
   */
  public function getData(): array;

}
