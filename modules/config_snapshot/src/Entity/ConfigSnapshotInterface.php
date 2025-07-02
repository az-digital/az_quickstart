<?php

namespace Drupal\config_snapshot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining config snapshot entities.
 */
interface ConfigSnapshotInterface extends ConfigEntityInterface {

  /**
   * Returns the snapshot set.
   *
   * @return string
   *   The set this snapshot belongs to.
   */
  public function getSnapshotSet();

  /**
   * Returns the extension type for this snapshot.
   *
   * @return string
   *   The type of the extension that this snapshot is for.
   */
  public function getExtensionType();

  /**
   * Returns the extension name for this snapshot.
   *
   * @return string
   *   The name of the extension that this snapshot is for.
   */
  public function getExtensionName();

  /**
   * Returns the items in this snapshot.
   *
   * @return array[]
   *   An array of all items.
   */
  public function getItems();

  /**
   * Sets the items for this snapshot.
   *
   * @param array[] $items
   *   Items to set for the snapshot.
   *
   * @return \Drupal\config_snapshot\Entity\ConfigSnapshotInterface
   *   A config snapshot object for chaining.
   */
  public function setItems(array $items);

  /**
   * Returns an item from the snapshot for a given collection.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $name
   *   The name of a configuration object.
   *
   * @return array|null
   *   The configuration item or NULL if not found.
   */
  public function getItem($collection, $name);

  /**
   * Returns an item from the snapshot for a given collection.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $name
   *   The name of a configuration object.
   * @param array $data
   *   Data to set for the configuration object.
   *
   * @return \Drupal\config_snapshot\Entity\ConfigSnapshotInterface
   *   A config snapshot object for chaining.
   */
  public function setItem($collection, $name, array $data);

  /**
   * Clears an item from the snapshot for a given collection.
   *
   * @param string $collection
   *   The configuration collection.
   * @param string $name
   *   The name of a configuration object.
   *
   * @return \Drupal\config_snapshot\Entity\ConfigSnapshotInterface
   *   A config snapshot object for chaining.
   */
  public function clearItem($collection, $name);

}
