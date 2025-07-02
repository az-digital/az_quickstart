<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for event plugins.
 */
abstract class EventBase extends PluginBase implements GoogleTagEventInterface {

  use ContextAwarePluginTrait;

  /**
   * Event base constructor for all event plugins.
   *
   * @param array $configuration
   *   Plugin config.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function getName(): string {
    return $this->pluginDefinition['event_name'];
  }

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    return array_filter($this->configuration);
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritDoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

}
