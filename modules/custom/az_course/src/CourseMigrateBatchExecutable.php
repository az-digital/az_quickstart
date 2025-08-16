<?php

namespace Drupal\az_course;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateBatchExecutable;

/**
 * Defines a migrate executable class for multi-URL batches.
 */
class CourseMigrateBatchExecutable extends MigrateBatchExecutable {

  /**
   * {@inheritdoc}
   */
  protected function batchOperations(array $migrations, $operation, array $options = []): array {
    $operations = [];
    foreach ($migrations as $migration) {

      if (!empty($options['update'])) {
        $migration->getIdMap()->prepareUpdate();
      }

      $dependencies = $migration->getMigrationDependencies();
      if (!empty($dependencies['required'])) {
        $required_migrations = $this->migrationPluginManager->createInstances($dependencies['required']);
        // For dependent migrations will need to be migrate all items.
        $operations = array_merge($operations, $this->batchOperations($required_migrations, $operation, [
          'limit' => 0,
          'update' => $options['update'],
          'force' => $options['force'],
          'sync' => $options['sync'],
        ]));
      }

      // Take expected source urls and run them one per batch. This is the
      // minimum we can do, as we have one class per URL.
      $urls = [];
      if (!empty($options['configuration']['source']['urls'])) {
        $urls = $options['configuration']['source']['urls'];
      }
      foreach ($urls as $url) {
        $operations[] = [
          '\Drupal\az_course\CourseMigrateBatchExecutable::batchProcessImport',
          [$migration->id(),
            [
              'configuration' => ['source' => ['urls' => [$url]]],
            ],
          ],
        ];
      }
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * @phpstan-ignore-next-line */
  public static function batchProcessImport($migration_id, array $options, &$context): void {
    if (empty($context['sandbox'])) {
      $context['finished'] = 0;
      $context['sandbox'] = [];
      $context['sandbox']['total'] = 0;
      $context['sandbox']['counter'] = 0;
      $context['sandbox']['batch_limit'] = 0;
      $context['sandbox']['operation'] = MigrateBatchExecutable::BATCH_IMPORT;
    }

    // Prepare the migration executable.
    $message = new MigrateMessage();
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = \Drupal::getContainer()->get('plugin.manager.migration')->createInstance($migration_id, $options['configuration'] ?? []);
    unset($options['configuration']);

    // Each batch run we need to reinitialize the counter for the migration.
    if (!empty($options['limit']) && isset($context['results'][$migration->id()]['@numitems'])) {
      $options['limit'] = $options['limit'] - $context['results'][$migration->id()]['@numitems'];
    }

    $executable = new CourseMigrateBatchExecutable($migration, $message, $options);

    if (empty($context['results'][$migration->id()])) {
      $context['sandbox']['total'] = $executable->getSource()->count();
      $context['sandbox']['batch_limit'] = $executable->calculateBatchLimit($context);
      $context['results'][$migration->id()] = [
        '@numitems' => 0,
        '@created' => 0,
        '@updated' => 0,
        '@failures' => 0,
        '@ignored' => 0,
        '@name' => $migration->id(),
      ];
    }

    // Every iteration, we reset our batch counter.
    $context['sandbox']['batch_counter'] = 0;

    // Make sure we know our batch context.
    $executable->setBatchContext($context);

    // Do the import.
    $executable->import();

    // Store the result; will need to combine the results of all our iterations.
    $context['results'][$migration->id()] = [
      '@numitems' => $context['results'][$migration->id()]['@numitems'] + $executable->getProcessedCount(),
      '@created' => $context['results'][$migration->id()]['@created'] + $executable->getCreatedCount(),
      '@updated' => $context['results'][$migration->id()]['@updated'] + $executable->getUpdatedCount(),
      '@failures' => $context['results'][$migration->id()]['@failures'] + $executable->getFailedCount(),
      '@ignored' => $context['results'][$migration->id()]['@ignored'] + $executable->getIgnoredCount(),
      '@name' => $migration->id(),
    ];

    // Do some housekeeping.
    if ($executable->getProcessedCount() > 0) {
      // Batch finished if we got one class from the URL.
      // Normal MigrateBatchExecutable doesn't handle the URL_based aspect.
      $context['finished'] = 1;
    }
    else {
      $context['sandbox']['counter'] = $context['results'][$migration->id()]['@numitems'];
      if ($context['sandbox']['counter'] <= $context['sandbox']['total']) {
        $context['finished'] = ((float) $context['sandbox']['counter'] / (float) $context['sandbox']['total']);
        $context['message'] = t('Importing %migration (@percent%).', [
          '%migration' => $migration->label(),
          '@percent' => (int) ($context['finished'] * 100),
        ]);
      }
    }
  }

}
