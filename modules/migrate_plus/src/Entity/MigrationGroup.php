<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Migration Group entity.
 *
 * The migration group entity is used to group active migrations, as well as to
 * store shared migration configuration.
 *
 * @ConfigEntityType(
 *   id = "migration_group",
 *   label = @Translation("Migration Group"),
 *   module = "migrate_plus",
 *   handlers = {
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "source_type",
 *     "module",
 *     "shared_configuration",
 *   },
 * )
 */
class MigrationGroup extends ConfigEntityBase implements MigrationGroupInterface {

  /**
   * The migration group ID (machine name).
   */
  protected ?string $id;

  /**
   * The human-readable label for the migration group.
   */
  protected ?string $label;

  /**
   * {@inheritdoc}
   */
  public function delete(): void {
    // Delete all migrations contained in this group.
    $query = \Drupal::entityQuery('migration')
      // Access check false because if the user has access to deleting
      // migration groups they should have access to deleting related migration.
      ->accessCheck(FALSE)
      ->condition('migration_group', $this->id());
    $names = $query->execute();

    // Order the migrations according to their dependencies.
    /** @var MigrationInterface[] $migrations */
    $migrations = \Drupal::entityTypeManager()->getStorage('migration')->loadMultiple($names);

    // Delete in reverse order, so dependencies are never violated.
    $migrations = array_reverse($migrations);

    foreach ($migrations as $migration) {
      $migration->delete();
    }

    // Finally, delete the group itself.
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    parent::calculateDependencies();
    // Make sure we save any explicit module dependencies.
    if ($provider = $this->get('module')) {
      $this->addDependency('module', $provider);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update): void {
    parent::invalidateTagsOnSave($update);
    Cache::invalidateTags(['migration_plugins']);
  }

}
