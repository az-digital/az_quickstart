<?php

namespace Drupal\blazy\Skin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\blazy\BlazyInterface;
use Drupal\blazy\Plugin\SkinPluginInterface;

/**
 * Provides skin manager base service.
 */
abstract class SkinManagerBase extends DefaultPluginManager implements SkinManagerBaseInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The blazy service.
   *
   * @var \Drupal\blazy\BlazyInterface
   */
  protected $manager;

  /**
   * The library info definition.
   *
   * @var array|null
   */
  protected $libraryInfoBuild;

  /**
   * Static cache for the skin definition.
   *
   * @var array|null
   */
  protected $skinDefinition;

  /**
   * The main module namespace.
   *
   * @var string
   * @see https://www.php.net/manual/en/reserved.keywords.php
   */
  protected static $namespace = 'blazy';

  /**
   * The plugin path.
   *
   * @var string
   */
  protected static $path = 'Plugin/blazy';

  /**
   * The plugin interface.
   *
   * @var string
   */
  protected static $interface = 'Drupal\blazy\Plugin\SkinPluginInterface';

  /**
   * The plugin annotation.
   *
   * @var string
   */
  protected static $annotation = 'Drupal\blazy\Annotation\BlazySkin';

  /**
   * The plugin key.
   *
   * @var string
   */
  protected static $key = 'blazy_skin';

  /**
   * The skin methods.
   *
   * @var array
   */
  protected static $methods = [
    'skins',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    BlazyInterface $manager,
  ) {
    parent::__construct(static::$path, $namespaces, $module_handler, static::$interface, static::$annotation);

    $this->manager = $manager;
    $this->root = $manager->root();
    $this->config = $manager->configFactory();

    $this->alterInfo(static::$key . '_info');
    $this->setCacheBackend($cache_backend, static::$key . '_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function config($key = '', $group = NULL) {
    $group = $group ?: static::$namespace . '.settings';
    return $this->manager->config($key, $group);
  }

  /**
   * {@inheritdoc}
   */
  public function load($plugin_id): SkinPluginInterface {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(): array {
    $skins = [];
    foreach ($this->getDefinitions() as $definition) {
      array_push($skins, $this->createInstance($definition['id']));
    }
    return $skins;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins(): array {
    if (!isset($this->skinDefinition)) {
      $cid   = static::$key . 's_data';
      $skins = $this->getAvailableSkins();

      $info['key'] = 'skins';

      $this->skinDefinition = $this->manager->getCachedData(
        $cid,
        $skins,
        $info
      );
    }
    return $this->skinDefinition ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function libraryInfoBuild(): array {
    if (!isset($this->libraryInfoBuild)) {
      $libraries = $this->getSkinLibraries();

      if ($extras = $this->getAdditionalLibraries()) {
        $libraries = $this->manager->merge($extras, $libraries);
      }

      $this->libraryInfoBuild = $libraries;
    }
    return $this->libraryInfoBuild;
  }

  /**
   * Returns additional libraries.
   */
  protected function getAdditionalLibraries(): array {
    return [];
  }

  /**
   * Returns available skins.
   */
  protected function getAvailableSkins(): array {
    $skins = $items = [];
    foreach ($this->loadMultiple() as $skin) {
      foreach (static::$methods as $method) {
        $items[$method] = $skin->{$method}();
      }
      $skins = NestedArray::mergeDeep($skins, $items);
    }

    return $skins;
  }

  /**
   * Returns skin libraries.
   */
  protected function getSkinLibraries(): array {
    $libraries = [];
    if ($skins = $this->getSkins()) {
      foreach ($skins as $key => $skin) {
        $provider = $skin['provider'] ?? static::$namespace;
        $id = $provider . '.' . $key;

        $libraries[$id]['dependencies'] = [];
        foreach (['css', 'js', 'dependencies'] as $property) {
          if (isset($skin[$property]) && is_array($skin[$property])) {
            $libraries[$id][$property] = $skin[$property];
          }
        }

        $libraries[$id]['version'] = 'VERSION';

        if ($dependencies = $this->getDependencies()) {
          $libraries[$id]['dependencies'] = array_merge(
            $libraries[$id]['dependencies'],
            $dependencies
          );
        }
      }
    }
    return $libraries;
  }

  /**
   * Returns available dependencies.
   */
  abstract protected function getDependencies(): array;

}
