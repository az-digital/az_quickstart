<?php

namespace Drupal\linkit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for the substitution plugins.
 */
class SubstitutionManager extends DefaultPluginManager implements SubstitutionManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the SubstitutionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/Linkit/Substitution', $namespaces, $module_handler, 'Drupal\linkit\SubstitutionInterface', 'Drupal\linkit\Annotation\Substitution');
    $this->alterInfo('linkit_substitution');
    $this->setCacheBackend($cache_backend, 'linkit_substitution');

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicablePluginsOptionList($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $options = [];
    foreach ($this->filterPlugins($this->getDefinitions(), $entity_type) as $id => $definition) {
      $options[$id] = $definition['label'];
    }
    return $options;
  }

  /**
   * Filter the list of plugins by their applicability.
   *
   * @param array $definitions
   *   An array of plugin definitions.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to get applicable plugins for.
   *
   * @return array
   *   The definitions appropriate for the given entity type.
   *
   * @see SubstitutionInterface::isApplicable()
   */
  protected function filterPlugins(array $definitions, EntityTypeInterface $entity_type) {
    return array_filter($definitions, function ($definition) use ($entity_type) {
      /** @var \Drupal\linkit\SubstitutionInterface $class */
      $class = $definition['class'];
      return $class::isApplicable($entity_type);
    });
  }

}
