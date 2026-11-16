<?php

namespace Drupal\az_canvas_migrate;

use Drupal\Core\Database\Connection;

/**
 * Holds Canvas component subtrees migrated from paragraphs.
 *
 * Canvas components are not entities - they only exist as items in a Canvas
 * page's component tree field. Paragraph migrations write their subtrees
 * here instead, and the page migration looks them up and assembles them.
 */
class CanvasComponentStore {

  /**
   * The holding table.
   */
  const TABLE = 'az_canvas_migrate_component';

  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * Loads a subtree.
   *
   * @return array<int, array<string, mixed>>|null
   *   The component tree items, or NULL if there is no such subtree.
   */
  public function load(string $id): ?array {
    $tree = $this->database->select(self::TABLE, 'c')
      ->fields('c', ['tree'])
      ->condition('id', $id)
      ->execute()
      ->fetchField();
    return $tree === FALSE ? NULL : json_decode($tree, TRUE);
  }

  /**
   * Saves a subtree.
   */
  public function save(string $id, array $tree): void {
    $this->database->merge(self::TABLE)
      ->key('id', $id)
      ->fields(['tree' => json_encode($tree)])
      ->execute();
  }

  /**
   * Deletes a subtree.
   */
  public function delete(string $id): void {
    $this->database->delete(self::TABLE)
      ->condition('id', $id)
      ->execute();
  }

}
