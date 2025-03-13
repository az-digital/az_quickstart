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
 * @todo Add class description.
 */
final class MigrationRemoteTools {
  use StringTranslationTrait;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * Constructs a RemoteMediaQueueTools object.
   */
  public function __construct(MigrationPluginManagerInterface $pluginManagerMigration) {
    $this->pluginManagerMigration = $pluginManagerMigration;
  }

  /**
   * @todo Add method description.
   */
  public function batch(array $migrations): void {
    // Create a batch for our queue items.
    $batch_builder = (new BatchBuilder())->setTitle(t('Migrating Remote Media'))
      ->setFinishCallback([MigrateBatchExecutable::class, 'batchFinishedImport']);

    // Create an array of batch operations.
    foreach ($migrations as $migration_id => $options) {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->pluginManagerMigration->createInstance($migration_id, $options['configuration'] ?? []);

      if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
        $migration->setStatus(MigrationInterface::STATUS_IDLE);
      }

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

      $batch_builder->addOperation(sprintf('%s::%s', MigrateBatchExecutable::class, 'batchProcessImport'),
        [$migration_id, $options],
      );
    }

    batch_set($batch_builder->toArray());
  }

}
