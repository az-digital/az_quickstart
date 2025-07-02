<?php

declare(strict_types=1);

namespace Drupal\google_tag;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Utility\Token;
use Drupal\google_tag\Annotation\GoogleTagEvent;
use Drupal\google_tag\Plugin\GoogleTag\Event\ConfigurableEventBase;
use Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface;

/**
 * Plugin manager for Google tag event plugins.
 */
final class GoogleTagEventManager extends DefaultPluginManager {

  /**
   * Token service.
   *
   * @var \Drupal\token\Token
   */
  protected $token;

  /**
   * Constructs Google Tag Event Manager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct(
      'Plugin/GoogleTag/Event',
      $namespaces,
      $module_handler,
      GoogleTagEventInterface::class,
      GoogleTagEvent::class,
    );
    $this->alterInfo('google_tag_event_info');
    $this->setCacheBackend($cache_backend, 'google_tag_event_plugins');
    $this->token = $token;
  }

  /**
   * {@inheritDoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The tag event %s must define the %s property.', $plugin_id, $required_property));
      }
    }
    if (empty($definition['event_name'])) {
      $definition['event_name'] = $definition['id'];
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function findDefinitions(): array {
    $definitions = parent::findDefinitions();
    foreach ($definitions as $plugin_id => $plugin_definition) {
      $dependency = $plugin_definition['dependency'] ?? '';
      if ($dependency === '') {
        continue;
      }
      if (!$this->moduleHandler->moduleExists($dependency)) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritDoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $event = parent::createInstance($plugin_id, $configuration);
    if ($event instanceof ConfigurableEventBase) {
      $event->setToken($this->token);
      $event->setModuleHandler($this->moduleHandler);
    }
    return $event;
  }

}
