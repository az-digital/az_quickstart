<?php

namespace Drupal\asset_injector\Entity;

use Drupal\asset_injector\AssetFileStorage;
use Drupal\asset_injector\AssetInjectorInterface;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Class AssetInjectorBase: Base asset injector class.
 *
 * @package Drupal\asset_injector\AssetInjectorBase.
 */
abstract class AssetInjectorBase extends ConfigEntityBase implements AssetInjectorInterface, EntityWithPluginCollectionInterface {

  /**
   * The Asset Injector ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Injector label.
   *
   * @var string
   */
  public $label;

  /**
   * The code of the asset.
   *
   * @var string
   */
  public $code;

  /**
   * Require all conditions.
   *
   * @var bool
   */
  public $conditions_require_all = TRUE;

  /**
   * The conditions settings for this asset.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The available contexts for this asset and its conditions.
   *
   * @var array
   */
  protected $contexts = [];

  /**
   * The conditions collection.
   *
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $conditionsCollection;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * {@inheritdoc}
   */
  public function libraryNameSuffix() {
    $extension = $this->extension();
    return "$extension.$this->id";
  }

  /**
   * {@inheritdoc}
   */
  abstract public function libraryInfo();

  /**
   * {@inheritdoc}
   */
  abstract public function extension();

  /**
   * {@inheritdoc}
   */
  public function internalFileUri() {
    $storage = new AssetFileStorage($this);
    return $storage->createFile();
  }

  /**
   * {@inheritdoc}
   */
  public function filePathRelativeToDrupalRoot() {
    $path = \Drupal::service('file_url_generator')
      ->generateAbsoluteString($this->internalFileUri());
    return str_replace(base_path(), '/', parse_url($path, PHP_URL_PATH));
  }

  /**
   * {@inheritdoc}
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * On delete delete this asset's file(s).
   */
  public function delete() {
    $storage = new AssetFileStorage($this);
    $storage->deleteFiles();
    parent::delete();
  }

  /**
   * On update delete this asset's file(s), will be recreated later.
   */
  public function preSave(EntityStorageInterface $storage) {
    $original_id = $this->getOriginalId();
    if ($original_id) {
      $original = $storage->loadUnchanged($original_id);
      // This happens to fail on config import.
      if ($original instanceof AssetInjectorInterface) {
        $asset_file_storage = new AssetFileStorage($original);
        $asset_file_storage->deleteFiles();
      }
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['conditions' => $this->getConditionsCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getConditions() {
    return $this->getConditionsCollection()->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionsConfig($instance_id, array $configuration) {
    $conditions = $this->getConditionsCollection();
    if (!$conditions->has($instance_id)) {
      $configuration['id'] = $instance_id;
      $conditions->addInstanceId($instance_id, $configuration);
    }
    else {
      $conditions->setInstanceConfiguration($instance_id, $configuration);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionsCollection() {
    if (!isset($this->conditionsCollection)) {
      $this->conditionsCollection = new ConditionPluginCollection($this->conditionPluginManager(), $this->get('conditions'));
    }
    return $this->conditionsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function setConditionsCollection(ConditionPluginCollection $conditions) {
    $this->conditionsCollection = $conditions;
  }

  /**
   * {@inheritdoc}
   */
  public function getConditionsInstance($instance_id) {
    return $this->getConditionsCollection()->get($instance_id);
  }

  /**
   * Gets the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  protected function conditionPluginManager() {
    if (!isset($this->conditionPluginManager)) {
      $this->conditionPluginManager = \Drupal::service('plugin.manager.condition');
    }
    return $this->conditionPluginManager;
  }

}
