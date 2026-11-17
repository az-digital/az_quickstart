<?php

namespace Drupal\az_canvas_migrate\Plugin\Action;

use Drupal\az_canvas_migrate\EventSubscriber\CanvasPageMigrationSubscriber;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\node\NodeInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Runs the Flexible Page to Canvas migrations for selected nodes.
 *
 * First each paragraph migration is run for the selected nodes' paragraphs,
 * then the page migration for the nodes themselves. Both are passed to
 * migrate_tools as ID lists, the same as `drush migrate:import ... --idlist`,
 * so results land in the migrations' normal ID maps: already converted
 * nodes are not converted twice, and conversions can be undone with
 * `drush migrate:rollback`.
 */
#[Action(
  id: 'az_canvas_migrate_flexible_page_to_canvas',
  label: new TranslatableMarkup('Convert Flexible Page to Canvas page'),
  type: 'node',
)]
class FlexiblePageToCanvasAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected MigrationPluginManagerInterface $migrationPluginManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected KeyValueFactoryInterface $keyValue,
    protected TimeInterface $time,
    TranslationInterface $string_translation,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('datetime.time'),
      $container->get('string_translation'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $nodes = [];
    foreach ($entities as $entity) {
      if ($entity instanceof NodeInterface && $entity->bundle() === 'az_flexible_page') {
        // The migrations only include the default translation.
        $nodes[$entity->id()] = $entity->getUntranslated();
      }
    }
    if (!$nodes) {
      return [];
    }

    $page_migration = $this->migrationPluginManager->createInstance(CanvasPageMigrationSubscriber::MIGRATION_ID);
    assert($page_migration instanceof MigrationInterface);

    // Paragraph migrations, keyed by paragraph type, come from the page
    // migration's own process configuration so there is one list to update.
    $paragraph_migrations = $page_migration->getProcess()['components'][0]['migrations'] ?? [];
    $paragraph_ids = [];
    foreach ($nodes as $node) {
      foreach ($node->get('field_az_main_content')->referencedEntities() as $paragraph) {
        if (isset($paragraph_migrations[$paragraph->bundle()])) {
          $paragraph_ids[$paragraph_migrations[$paragraph->bundle()]][$node->id()][] = [
            $paragraph->id(),
            $paragraph->language()->getId(),
          ];
        }
      }
    }

    foreach ($paragraph_ids as $migration_id => $ids_by_node) {
      $migration = $this->migrationPluginManager->createInstance($migration_id);
      assert($migration instanceof MigrationInterface);
      // Always re-convert paragraphs, so a page converted again after a
      // rollback reflects the paragraphs' current content.
      $this->runMigration($migration, array_merge(...array_values($ids_by_node)), TRUE);
      foreach ($ids_by_node as $nid => $ids) {
        foreach ($ids as $source_ids) {
          $this->reportMessages($migration, $nodes[$nid], $source_ids);
        }
      }
    }

    // Source IDs are nid and langcode, since translations are not included.
    $node_ids = array_map(fn (NodeInterface $node) => [$node->id(), $node->language()->getId()], array_values($nodes));
    $this->runMigration($page_migration, $node_ids, FALSE);

    return array_map(function (NodeInterface $node) use ($page_migration) {
      $source_ids = [$node->id(), $node->language()->getId()];
      $this->reportMessages($page_migration, $node, $source_ids);
      $row = $page_migration->getIdMap()->getRowBySource(['nid' => $source_ids[0], 'langcode' => $source_ids[1]]);
      return $row && (int) $row['source_row_status'] === MigrateIdMapInterface::STATUS_IMPORTED && !empty($row['destid1'])
        ? $this->t('Converted to Canvas page')
        : $this->t('Conversion failed');
    }, array_values($nodes));
  }

  /**
   * Imports the given source rows of a migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param array<int, array<int, int|string>> $source_ids
   *   Source ID values for each row, in the source plugin's ID order.
   * @param bool $update
   *   Whether rows imported before should be imported again.
   */
  protected function runMigration(MigrationInterface $migration, array $source_ids, bool $update): void {
    // Allow a run interrupted by a fatal error to be retried from here
    // rather than requiring `drush migrate:reset-status`.
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }
    $id_map = $migration->getIdMap();
    $id_keys = array_keys($migration->getSourcePlugin()->getIds());
    if ($update) {
      foreach ($source_ids as $ids) {
        if ($id_map->getRowBySource(array_combine($id_keys, $ids))) {
          $id_map->setUpdate(array_combine($id_keys, $ids));
        }
      }
    }
    $idlist = implode(',', array_map(fn (array $ids) => implode(':', $ids), $source_ids));
    (new MigrateExecutable($migration, new MigrateMessage(), $this->keyValue, $this->time, $this->getStringTranslation(), ['idlist' => $idlist]))->import();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    return $this->executeMultiple([$entity])[0] ?? $this->t('Skipped: not a Flexible Page');
  }

  /**
   * Shows the migrate messages saved for one source row.
   */
  protected function reportMessages(MigrationInterface $migration, NodeInterface $node, array $source_ids): void {
    $id_keys = array_keys($migration->getSourcePlugin()->getIds());
    foreach ($migration->getIdMap()->getMessages(array_combine($id_keys, $source_ids)) as $message) {
      $args = ['%title' => $node->label(), '@message' => $message->message];
      if ((int) $message->level === MigrationInterface::MESSAGE_ERROR) {
        $this->messenger()->addError($this->t('%title: @message', $args));
      }
      else {
        $this->messenger()->addWarning($this->t('%title: @message', $args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::forbidden('Only Flexible Pages can be converted.');
    if ($object instanceof NodeInterface && $object->bundle() === 'az_flexible_page') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($this->entityTypeManager->getAccessControlHandler('canvas_page')->createAccess(NULL, $account, [], TRUE));
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

}
