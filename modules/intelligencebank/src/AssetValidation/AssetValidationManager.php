<?php

namespace Drupal\ib_dam\AssetValidation;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ib_dam\Exceptions\AssetValidationBadPluginId;

/**
 * Class AssetValidationManager.
 *
 * Validation manager for an asset validations.
 *
 * @package Drupal\ib_dam\AssetValidation
 */
class AssetValidationManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * List of already instantiated validation plugins.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructs a new AssetValidationManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/IbDam/AssetValidation', $namespaces, $module_handler, 'Drupal\ib_dam\AssetValidation\AssetValidationInterface', 'Drupal\ib_dam\Annotation\IbDamAssetValidation');

    $this->alterInfo('ib_dam_asset_validation_info');
    $this->setCacheBackend($cache_backend, 'ib_dam_asset_validation_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugin_id = $options['id'];
    $instance = NULL;

    try {
      $instance = $this->createInstance($plugin_id, $options);
    }
    catch (PluginException $e) {
      throw new AssetValidationBadPluginId($e->getMessage());
    }

    return $instance;
  }

}
