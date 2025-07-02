<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

/**
 * The Search plugin.
 *
 * @GoogleTagEvent(
 *   id = "search",
 *   event_name = "search",
 *   label = @Translation("Search"),
 *   description = @Translation("Use this event to contextualize search operations. This event can help you identify the most popular content in your app."),
 *   context_definitions = {
 *      "search_term" = @ContextDefinition("string"),
 *   }
 * )
 */
final class Search extends EventBase {

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    return [
      'search_term' => $this->getContextValue('search_term'),
    ];
  }

}
