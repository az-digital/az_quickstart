<?php

namespace Drupal\workbench_access_filter_test\Plugin\AccessControlHierarchy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\filter\FilterPluginManager;
use Drupal\workbench_access\AccessControlHierarchyBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a test hierarchy for the sake of config entity access.
 *
 * @AccessControlHierarchy(
 *   id = "workbench_access_filter_test",
 *   module = "workbench_access_filter_test",
 *   entity = "filter_format",
 *   label = @Translation("Workbench access filter test"),
 *   description = @Translation("Test config entity access.")
 * )
 */
class FilterAccess extends AccessControlHierarchyBase {

  /**
   * Filter plugin manager.
   *
   * @var \Drupal\filter\FilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setFilterPluginManager($container->get('plugin.manager.filter'));
  }

  /**
   * {@inheritdoc}
   */
  public function getTree() {
    $tree = [];
    $tree['filter']['filters'] = [
      'label' => 'Filters',
      'depth' => 0,
      'parents' => [],
      'weight' => 0,
      'description' => 'Filters',
    ];
    $weight = 1;
    foreach ($this->filterPluginManager->getDefinitions() as $id => $definition) {
      $tree['filter'][$id] = [
        'label' => $definition['label'] ?? $id,
        'depth' => 1,
        'parents' => ['filters'],
        'weight' => $weight++,
        'description' => $definition['description'],
      ];
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    /** @var \Drupal\filter\FilterFormatInterface $entity */
    return array_keys(array_filter(iterator_to_array($entity->filters()), function ($filter) {
      return $filter->status;
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return $entity_type_id === 'filter_format';
  }

  /**
   * Sets filter plugin manager.
   *
   * @param \Drupal\filter\FilterPluginManager $manager
   *   Manager.
   *
   * @return $this
   */
  public function setFilterPluginManager(FilterPluginManager $manager) {
    $this->filterPluginManager = $manager;
    return $this;
  }

}
