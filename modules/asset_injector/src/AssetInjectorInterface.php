<?php

namespace Drupal\asset_injector;

use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Asset Injector entities.
 */
interface AssetInjectorInterface extends ConfigEntityInterface {

  /**
   * Gets the library array used in library_info_build.
   *
   * @return array
   *   Library info array for this asset.
   */
  public function libraryInfo();

  /**
   * Get the library name suffix to append to module name.
   *
   * @return bool|string
   *   Library name suffix for use in page attachments.
   *
   * @see asset_injector_page_attachments()
   * @see asset_injector_library_info_build()
   */
  public function libraryNameSuffix();

  /**
   * Get internal file uri.
   *
   * @return string
   *   Internal file uri like public://asset_injector/...
   */
  public function internalFileUri();

  /**
   * Get file path relative to drupal root to use in library info.
   *
   * @return string
   *   File path relative to drupal root, with leading slash.
   */
  public function filePathRelativeToDrupalRoot();

  /**
   * Get file extension.
   *
   * @return string
   *   File extension, like 'css' or 'js'.
   */
  public function extension();

  /**
   * Get the asset's code.
   *
   * @return string
   *   The code of the asset.
   */
  public function getCode();

  /**
   * Returns an array of condition configurations.
   *
   * @return array
   *   An array of condition configuration keyed by the condition ID.
   */
  public function getConditions();

  /**
   * Gets conditions for this asset.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  public function getConditionsCollection();

  /**
   * Set new conditions on the asset.
   *
   * @param \Drupal\Core\Condition\ConditionPluginCollection $conditions
   *   Conditions to set.
   */
  public function setConditionsCollection(ConditionPluginCollection $conditions);

  /**
   * Gets a conditions condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getConditionsInstance($instance_id);

  /**
   * Sets the conditions condition configuration.
   *
   * @param string $instance_id
   *   The condition instance ID.
   * @param array $configuration
   *   The condition configuration.
   *
   * @return $this
   */
  public function setConditionsConfig($instance_id, array $configuration);

}
