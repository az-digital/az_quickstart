<?php

namespace Drupal\config_filter\Config;

use Drupal\Core\Config\StorageInterface;

/**
 * Class GhostStorage.
 *
 * A GhostStorage acts like the normal Storage it wraps. All reading operations
 * return the values of the decorated storage but write operations are silently
 * ignored and the ghost pretends that the operation was successful.
 *
 * @package Drupal\config_filter\Config
 */
class GhostStorage extends ReadOnlyStorage implements StorageInterface {

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    return TRUE;
  }

}
