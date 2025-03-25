<?php

declare(strict_types=1);

namespace Drupal\az_migration_remote;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\migrate_tools\MigrateTools;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * This class provides services for helpers for remote migrations.
 *
 * Chiefly it provides the ability to pass options to multiple
 * migrations for use with MigrationBatchExecutable, which does not
 * support passing parameters to the dependent migrations.
 *
 * This feature is often needed for specifying options to media and
 * file migrations that are running as part of a batch.
 *
 * @see \Drupal\migrate_tools\MigrateBatchExecutable
 */
final class MigrationRemoteTools {
  use StringTranslationTrait;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * Constructs a new MigrationRemoteTools object.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $pluginManagerMigration
   *   The migration plugin manager.
   */
  public function __construct(MigrationPluginManagerInterface $pluginManagerMigration) {
    $this->pluginManagerMigration = $pluginManagerMigration;
  }

  /**
   * Set a batch of multiple migrations for MigrateBatchExecutable.
   *
   * @param array $migrations
   *   An array of migration options, keyed by the migration_id to use.
   *
   * @see \Drupal\migrate_tools\MigrateBatchExecutable
   */
  public function batch(array $migrations): void {
    // Create a batch for our queue items.
    // ->setTitle($this->t('Migrating Remote Media'))
    $batch_builder = (new BatchBuilder())->setFinishCallback([MigrateBatchExecutable::class, 'batchFinishedImport']);

    $labels = [];
    // Create an array of batch operations.
    // Treat array key as migration id.
    foreach ($migrations as $migration_id => $options) {
      // Lookup the migration.
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->pluginManagerMigration->createInstance($migration_id, $options['configuration'] ?? []);
      $labels[] = $migration->label();

      // Reset the migration's status.
      if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
      }

      // Set update operations on migration.
      if (!empty($options['update'])) {
        if (empty($options['idlist'])) {
          $migration->getIdMap()->prepareUpdate();
        }
        else {
          $source_id_values_list = MigrateTools::buildIdList($options);
          $keys = array_keys($migration->getSourcePlugin()->getIds());
          foreach ($source_id_values_list as $source_id_values) {
            $migration->getIdMap()->setUpdate(array_combine($keys, $source_id_values));
          }
        }
      }

      // Add batch operation for this migration.
      $batch_builder->addOperation(sprintf('%s::%s', MigrateBatchExecutable::class, 'batchProcessImport'),
        [$migration_id, $options],
      );
    }

    // Set messages.
    $labels = implode(', ', $labels);
    $batch_builder->setTitle(t('Migrating %labels', ['%labels' => $labels]))
      ->setInitMessage(t('Start migrating %labels', ['%labels' => $labels]))
      ->setProgressMessage(t('Migrating %labels', ['%labels' => $labels]))
      ->setErrorMessage(t('An error occurred while migrating %labels', ['%labels' => $labels]));

    // Set the constructed batch as a batch operation.
    // Typically the batch is actually executed later by the Form API.
    batch_set($batch_builder->toArray());
  }

}
