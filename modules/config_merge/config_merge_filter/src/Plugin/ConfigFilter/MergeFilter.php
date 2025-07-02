<?php

namespace Drupal\config_merge_filter\Plugin\ConfigFilter;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\config_merge\ConfigMerger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a merge filter that reads partly from the active storage.
 *
 * @ConfigFilter(
 *   id = "config_merge",
 *   label = "Config Merge",
 *   weight = 1000
 * )
 */
class MergeFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * The snapshot configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $snapshot;

  /**
   * The snapshot configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configMerger;

  /**
   * Constructs a new SplitFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active configuration store with the configuration on the site.
   * @param \Drupal\Core\Config\StorageInterface $snapshot
   *   The snapshot configuration store.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $active, StorageInterface $snapshot) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->active = $active;
    $this->snapshot = $snapshot;
    $this->configMerger = new ConfigMerger();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage'),
      $container->get('config.storage.snapshot')
    );
  }

  /**
   * Merges in changes from the active configuration.
   *
   * This method will read the configuration from the active config store.
   * But rather than just straight up returning the value it will do a three-
   * way merge of the previous snapshot value, the new value, and the active
   * value.
   *
   * @param string $name
   *   The name of the configuration to read.
   * @param mixed $data
   *   The data to be filtered.
   *
   * @return mixed
   *   The data filtered or merged from the active storage.
   */
  protected function activeRead($name, $data) {
    // Only merge if we have incoming data and both a previous and an active
    // value.
    if (!$data || !($active = $this->active->read($name)) || (!$previous = $this->snapshot->read($name))) {
      return $data;
    }

    return $this->configMerger->mergeConfigItemStates($previous, $data, $active);
  }

  /**
   * Read multiple from the active storage.
   *
   * @param array $names
   *   The names of the configuration to read.
   * @param array $data
   *   The data to filter.
   *
   * @return array
   *   The new data.
   */
  protected function activeReadMultiple(array $names, array $data) {
    $filtered_data = [];

    foreach ($names as $name) {
      $filtered_data[$name] = $this->activeRead($name, $data[$name] ?? NULL);
    }

    return $filtered_data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    return $this->activeRead($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    return $exists || ($this->active->exists($name));
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    $active_data = $this->activeReadMultiple($names, $data);

    // Return the data with merged in active data.
    return array_merge($data, $active_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $active_names = $this->active->listAll($prefix);

    // Return the data with the active names which are ignored merged in.
    return array_unique(array_merge($data, $active_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->active->createCollection($collection), $this->snapshot->createCollection($collection));
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    // Add active collection names as there could be ignored config in them.
    return array_merge($collections, $this->active->getAllCollectionNames());
  }

}
