<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

/**
 * Custom event plugin.
 *
 * @todo SHOULD NOT SHOW IN THE UI, CODE ONLY.
 *
 * @GoogleTagEvent(
 *   id = "custom",
 *   label = @Translation("Custom event"),
 *   context_definitions = {
 *      "name" = @ContextDefinition("string"),
 *      "data" = @ContextDefinition("any"),
 *   }
 * )
 */
final class CustomEvent extends EventBase {

  /**
   * {@inheritDoc}
   */
  public function getName(): string {
    return $this->getContextValue('name');
  }

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    return $this->getContextValue('data');
  }

}
