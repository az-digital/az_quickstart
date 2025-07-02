<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages results exporter plugins.
 *
 * @see hook_webform_exporter_info_alter()
 * @see \Drupal\webform\Annotation\WebformExporter
 * @see \Drupal\webform\Plugin\WebformExporterInterface
 * @see \Drupal\webform\Plugin\WebformExporterBase
 * @see plugin_api
 */
class WebformExporterManager extends DefaultPluginManager implements WebformExporterManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a WebformExporterManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_exporter
   *   The module exporter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_exporter, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/WebformExporter', $namespaces, $module_exporter, 'Drupal\webform\Plugin\WebformExporterInterface', 'Drupal\webform\Annotation\WebformExporter');
    $this->configFactory = $config_factory;

    $this->alterInfo('webform_exporter_info');
    $this->setCacheBackend($cache_backend, 'webform_exporter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label') {
    // Sort the plugins first by category, then by label.
    $definitions = $this->traitGetSortedDefinitions($definitions, $sort_by);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function removeExcludeDefinitions(array $definitions) {
    $definitions = $definitions ?? $this->getDefinitions();
    $excluded = $this->configFactory->get('webform.settings')->get('export.excluded_exporters');
    return $excluded ? array_diff_key($definitions, $excluded) : $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances(array $configuration = []) {
    $instances = [];
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);
    $plugin_definitions = $this->removeExcludeDefinitions($plugin_definitions);
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id, $configuration);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);
    $plugin_definitions = $this->removeExcludeDefinitions($plugin_definitions);

    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'delimited';
  }

}
