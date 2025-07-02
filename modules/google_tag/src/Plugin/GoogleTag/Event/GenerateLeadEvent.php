<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

/**
 * Generate lead event plugin.
 *
 * @GoogleTagEvent(
 *   id = "generate_lead",
 *   event_name = "generate_lead",
 *   label = @Translation("Generate lead"),
 *   description = @Translation("Log this event when a lead has been generated to understand the efficacy of your re-engagement campaigns."),
 *   context_definitions = {
 *      "value" = @ContextDefinition("string", required = FALSE),
 *      "currency" = @ContextDefinition("string", required = FALSE),
 *   }
 * )
 */
final class GenerateLeadEvent extends EventBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [
      'currency' => '',
      'value' => '',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    $configuration = array_filter($this->configuration);
    $data = array_filter([
      'currency' => $this->getContextValue('currency'),
      'value' => $this->getContextValue('value'),
    ]);

    return $data + $configuration;
  }

}
