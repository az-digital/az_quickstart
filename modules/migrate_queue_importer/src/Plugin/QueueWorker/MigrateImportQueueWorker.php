<?php

namespace Drupal\migrate_queue_importer\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker to import each queued migration or its dependencies.
 *
 * @QueueWorker(
 *   id = "migrations_importer",
 *   title = @Translation("Migrations importer"),
 *   cron = {
 *     "time" = 30,
 *   },
 * )
 */
class MigrateImportQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The migration manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationPluginManagerInterface $migration_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrationManager = $migration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Works on a single queue item.
   *
   * @param mixed $data
   *   The data that was passed to
   *   \Drupal\Core\Queue\QueueInterface::createItem() when the item was queued.
   *
   * @throws \Drupal\Core\Queue\RequeueException
   *   Processing is not yet finished. This will allow another process to claim
   *   the item immediately.
   * @throws \Exception
   *   A QueueWorker plugin may throw an exception to indicate there was a
   *   problem. The cron process will log the exception, and leave the item in
   *   the queue to be processed again later.
   * @throws \Drupal\Core\Queue\SuspendQueueException
   *   More specifically, a SuspendQueueException should be thrown when a
   *   QueueWorker plugin is aware that the problem will affect all subsequent
   *   workers of its queue. For example, a callback that makes HTTP requests
   *   may find that the remote server is not responding. The cron process will
   *   behave as with a normal Exception, and in addition will not attempt to
   *   process further items from the current item's queue during the current
   *   cron run.
   *
   * @see \Drupal\Core\Cron::processQueues()
   */
  public function processItem($data) {
    /** @var  \Drupal\migrate\Plugin\MigrationInterface $migration **/
    $migration = $data['migration'];

    // Make sure we have an idle status.
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }

    if ($data['ignore']) {
      $migration->set('requirements', []);
    }

    // Set sync state.
    if ($data['sync']) {
      $migration->set('syncSource', TRUE);
    }

    // Run a full update for the migration.
    if ($data['update']) {
      $migration->getIdMap()->prepareUpdate();
    }

    $message = new MigrateMessage();
    $executable = new MigrateExecutable($migration, $message);
    $executable->import();
  }

}
