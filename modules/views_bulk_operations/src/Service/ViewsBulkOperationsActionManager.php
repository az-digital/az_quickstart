<?php

namespace Drupal\views_bulk_operations\Service;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\views_bulk_operations\ActionAlterDefinitionsEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines Views Bulk Operations action manager.
 *
 * Extends the core Action Manager to allow VBO actions
 * define additional configuration.
 */
class ViewsBulkOperationsActionManager extends ActionManager {

  public const ALTER_ACTIONS_EVENT = 'views_bulk_operations.action_definitions';

  /**
   * Additional parameters passed to alter event.
   *
   * @var array
   */
  protected array $alterParameters;

  /**
   * Service constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    #[Autowire(service: 'container.namespaces')]
    \Traversable $namespaces,
    #[Autowire(service: 'cache.discovery')]
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler,
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($namespaces, $cacheBackend, $moduleHandler);

    $this->setCacheBackend($cacheBackend, 'views_bulk_operations_action_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();

    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $plugin_id => &$definition) {
      // We only allow actions of existing entity type and empty
      // type meaning it's applicable to all entity types.
      if (
        empty($definition) ||
        (
          !empty($definition['type']) &&
          !isset($entity_type_definitions[$definition['type']])
        )
      ) {
        unset($definitions[$plugin_id]);
      }

      // Filter definitions that are incompatible due to applied core
      // configuration form workaround (using confirm_form_route for config
      // forms and using action execute() method for purposes other than
      // actual action execution). Also filter out actions that don't implement
      // ViewsBulkOperationsActionInterface and have empty type as this
      // shouldn't be the case in core. Luckily, core also has useful actions
      // without the workaround, like node_assign_owner_action or
      // comment_unpublish_by_keyword_action.
      if (!\in_array('Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionInterface', \class_implements($definition['class']))) {
        if (
          !empty($definition['confirm_form_route_name']) ||
          empty($definition['type'])
        ) {
          unset($definitions[$plugin_id]);
        }
      }

      $this->processDefinition($definition, $plugin_id);
    }
    foreach ($definitions as $plugin_id => $plugin_definition) {
      // If the plugin definition is an object, attempt to convert it to an
      // array, if that is not possible, skip further processing.
      if (\is_object($plugin_definition) && !($plugin_definition = (array) $plugin_definition)) {
        continue;
      }
      // If this plugin was provided by a module that does not exist, remove the
      // plugin definition.
      if (
        isset($plugin_definition['provider']) &&
        !\in_array($plugin_definition['provider'], ['core', 'component']) &&
        !$this->providerExists($plugin_definition['provider'])
      ) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $parameters
   *   Parameters of the method. Passed to alter event.
   */
  public function getDefinitions(array $parameters = []) {
    if (empty($parameters['nocache'])) {
      $definitions = $this->getCachedDefinitions();
    }
    if (!isset($definitions)) {
      $definitions = $this->findDefinitions($parameters);

      $this->setCachedDefinitions($definitions);
    }

    // Alter definitions after retrieving all from the cache for maximum
    // flexibility.
    $this->alterParameters = $parameters;
    $this->alterDefinitions($definitions);

    return $definitions;
  }

  /**
   * Gets a specific plugin definition.
   *
   * @param string $plugin_id
   *   A plugin id.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid plugin ID will throw an exception.
   * @param array $parameters
   *   Parameters of the method. Passed to alter event.
   *
   * @return mixed
   *   A plugin definition, or NULL if the plugin ID is invalid and
   *   $exception_on_invalid is FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if $plugin_id is invalid and $exception_on_invalid is TRUE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE, array $parameters = []) {
    // Loading all definitions here will not hurt much, as they're cached,
    // and we need the option to alter a definition.
    $definitions = $this->getDefinitions($parameters);
    if (isset($definitions[$plugin_id])) {
      return $definitions[$plugin_id];
    }
    elseif (!$exception_on_invalid) {
      return NULL;
    }

    throw new PluginNotFoundException($plugin_id, \sprintf('The "%s" plugin does not exist.', $plugin_id));
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Let other modules change definitions.
    // Main purpose: Action permissions bridge.
    $event = new ActionAlterDefinitionsEvent();
    $event->alterParameters = $this->alterParameters;
    $event->definitions = &$definitions;

    $this->eventDispatcher->dispatch($event, self::ALTER_ACTIONS_EVENT);

    // Include the expected behaviour (hook system) to avoid security issues.
    parent::alterDefinitions($definitions);
  }

}
