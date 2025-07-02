<?php

namespace Drupal\Tests\webform\Traits;

use Drupal\Component\Serialization\Json;

/**
 * Provides convenience methods for webform assertions in browser tests.
 */
trait WebformWebDriverTestTrait {

  /**
   * Execute jQuery event.
   *
   * @param string $selector
   *   Selector to trigger the event on.
   * @param string $event_type
   *   The event type.
   * @param array $event_options
   *   The event options.
   */
  public function executeJqueryEvent($selector, $event_type, array $event_options = []) {
    $event_options = Json::encode($event_options);
    $script = "jQuery('$selector').trigger(jQuery.Event('$event_type', $event_options));";
    $this->getSession()->executeScript($script);
  }

}
