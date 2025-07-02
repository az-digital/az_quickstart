<?php

namespace Drupal\metatag_views;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\ViewsPluginManager;

/**
 * Custom cache plugin system for Views.
 */
class MetatagViewsCachePluginManager implements PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\views\Plugin\ViewsPluginManager
   */
  protected $viewsPluginManager;

  /**
   * MetatagViewsCachePluginManager constructor.
   *
   * @param \Drupal\views\Plugin\ViewsPluginManager $views_plugin_manager
   *   The ViewsPluginManager as argument.
   */
  public function __construct(ViewsPluginManager $views_plugin_manager) {
    $this->viewsPluginManager = $views_plugin_manager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\views\Plugin\views\cache\CachePluginBase $plugin
   *   The CachePluginBase as argument.
   *
   * @return \Drupal\metatag_views\MetatagViewsCacheWrapper
   *   Return new MetatagViewsCacheWrapper
   */
  protected function wrap(CachePluginBase $plugin) {
    return new MetatagViewsCacheWrapper($plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin = $this->viewsPluginManager->createInstance($plugin_id, $configuration);
    return $plugin_id === 'none' ? $plugin : $this->wrap($plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    /** @var \Drupal\views\Plugin\views\cache\CachePluginBase $plugin */
    $plugin = $this->viewsPluginManager->getInstance($options);
    return $plugin->getPluginId() === 'none' ? $plugin : $this->wrap($plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->viewsPluginManager->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->viewsPluginManager->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->viewsPluginManager->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->viewsPluginManager->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->viewsPluginManager->useCaches($use_caches);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->viewsPluginManager->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->viewsPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    return $this->viewsPluginManager->hasDefinition($plugin_id);
  }

}
