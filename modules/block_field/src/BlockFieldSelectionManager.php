<?php

namespace Drupal\block_field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Block field selection plugin manager.
 */
class BlockFieldSelectionManager extends DefaultPluginManager {

  /**
   * Constructs a new BlockFieldSelectionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/block_field/BlockFieldSelection', $namespaces, $module_handler, 'Drupal\block_field\BlockFieldSelectionInterface', 'Drupal\block_field\Annotation\BlockFieldSelection');

    $this->alterInfo('block_field_block_field_selection_info');
    $this->setCacheBackend($cache_backend, 'block_field_block_field_selection_plugins');
  }

  /**
   * Loads all definitions and returns key => value array.
   *
   * @return array
   *   Array of options from definitions.
   */
  public function getOptions() {
    $definitions = $this->getDefinitions();
    $options = [];
    foreach ($definitions as $plugin_id => $definition) {
      $options[$plugin_id] = $definition['label'];
    }
    return $options;
  }

  /**
   * Returns an instance of BlockFieldSelectionInterface from $field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The 'block_field' field definition.
   *
   * @return \Drupal\block_field\BlockFieldSelectionInterface
   *   The BlockFieldSelectionInterface instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSelectionHandler(FieldDefinitionInterface $field) {
    $settings = $field->getSetting('selection_settings') ? $field->getSetting('selection_settings') : [];
    return $this->createInstance($field->getSetting('selection'), $settings);
  }

  /**
   * Returns an key => value array based on allowed referenceable blocks.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The 'block_field' field definition.
   *
   * @return array
   *   Array of options from definitions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getWidgetOptions(FieldDefinitionInterface $field) {
    $handler = $this->getSelectionHandler($field);
    $options = [];
    foreach ($handler->getReferenceableBlockDefinitions() as $plugin_id => $definition) {
      $category = (string) $definition['category'];
      $options[$category][$plugin_id] = $definition['admin_label'];
    }
    return $options;
  }

}
