<?php

namespace Drupal\config_snapshot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Config snapshot entity.
 *
 * @ConfigEntityType(
 *   id = "config_snapshot",
 *   label = @Translation("Config snapshot"),
 *   label_singular = @Translation("Config snapshot item"),
 *   label_plural = @Translation("Config snapshot items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count config snapshot item",
 *     plural = "@count config snapshot items",
 *   ),
 *   config_prefix = "snapshot",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "snapshotSet",
 *     "extensionType",
 *     "extensionName",
 *     "items"
 *   }
 * )
 */
class ConfigSnapshot extends ConfigEntityBase implements ConfigSnapshotInterface {

  /**
   * The snapshot ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The snapshot set.
   *
   * A set is a group of snapshots used for a particular purpose. A set should
   * be namespaced for the module introducing it.
   *
   * @var string
   */
  protected $snapshotSet;

  /**
   * The snapshot type.
   *
   * @var string
   */
  protected $extensionType;

  /**
   * The snapshot name.
   *
   * @var string
   */
  protected $extensionName;

  /**
   * The snapshot items.
   *
   * @var array[]
   */
  protected $items = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return "{$this->snapshotSet}.{$this->extensionType}.{$this->extensionName}";
  }

  /**
   * {@inheritdoc}
   */
  public function getSnapshotSet() {
    return $this->snapshotSet;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionType() {
    return $this->extensionType;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionName() {
    return $this->extensionName;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $items) {
    $this->items = $items;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($collection, $name) {
    if (($key = $this->getItemKey($collection, $name)) !== FALSE) {
      return $this->items[$key];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setItem($collection, $name, array $data) {
    $item = [
      'collection' => $collection,
      'name' => $name,
      'data' => $data,
    ];
    if (($key = $this->getItemKey($collection, $name)) !== FALSE) {
      $this->items[$key] = $item;
    }
    else {
      $this->items[] = $item;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearItem($collection, $name) {
    if (($key = $this->getItemKey($collection, $name)) !== FALSE) {
      unset($this->items[$key]);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $collections = array_column($this->items, 'collection');
    $names = array_column($this->items, 'name');
    // Sort the items by collection then by name.
    array_multisort($collections, SORT_ASC, $names, SORT_ASC, $this->items);

    return parent::save();
  }

  /**
   * Returns the key of a given item.
   *
   * @return int|false
   *   The key of the item or FALSE if not found.
   */
  protected function getItemKey($collection, $name) {
    $items = array_filter($this->items, function ($item) use ($collection, $name) {
      return ($item['collection'] === $collection) && ($item['name'] === $name);
    });
    if ($items) {
      return key($items);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Create a dependency on the extension.
    $this->addDependency($this->extensionType, $this->extensionName);

    return $this;
  }

}
