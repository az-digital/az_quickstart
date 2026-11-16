<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\az_canvas_migrate\CanvasComponentStore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Assembles a Canvas component tree from migrated paragraph subtrees.
 *
 * Each referenced paragraph is looked up in the migration configured for
 * its type, and the subtree that migration stored is appended, preserving
 * paragraph order. Paragraph types without a migration are skipped and
 * recorded as migrate messages, as are paragraphs their migration ignored
 * (for example, an empty Rankings paragraph). A supported paragraph that has
 * not been migrated yet skips the row without recording it in the ID map, so
 * a page is never created with missing content and the row is retried on the
 * next import.
 *
 * Available configuration keys:
 * - migrations: Paragraph migration IDs, keyed by paragraph type.
 *
 * @code
 * components:
 *   plugin: az_canvas_component_tree
 *   source: field_az_main_content
 *   migrations:
 *     az_rankings: az_paragraph_rankings_to_canvas
 * @endcode
 */
#[MigrateProcess(
  id: 'az_canvas_component_tree',
  handle_multiples: TRUE,
)]
class CanvasComponentTree extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MigrationPluginManagerInterface $migrationPluginManager,
    protected CanvasComponentStore $store,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration'),
      $container->get('az_canvas_migrate.component_store'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $migrations = $this->configuration['migrations'] ?? [];
    $storage = $this->entityTypeManager->getStorage('paragraph');
    $tree = [];

    foreach ((array) $value as $reference) {
      $paragraph = $storage->loadRevision($reference['target_revision_id'] ?? 0);
      if (!$paragraph instanceof ParagraphInterface) {
        continue;
      }
      $migration_id = $migrations[$paragraph->bundle()] ?? NULL;
      if ($migration_id === NULL) {
        $migrate_executable->saveMessage(sprintf('Skipped unsupported "%s" paragraph.', $paragraph->getParagraphType()?->label() ?? $paragraph->bundle()), MigrationInterface::MESSAGE_NOTICE);
        continue;
      }

      $map_row = $this->getMapRow($migration_id, $paragraph);
      $status = $map_row ? (int) $map_row['source_row_status'] : NULL;
      if ($status === MigrateIdMapInterface::STATUS_IGNORED) {
        $migrate_executable->saveMessage(sprintf('Skipped paragraph %d, which the %s migration ignored.', $paragraph->id(), $migration_id), MigrationInterface::MESSAGE_NOTICE);
        continue;
      }
      $subtree = in_array($status, [MigrateIdMapInterface::STATUS_IMPORTED, MigrateIdMapInterface::STATUS_NEEDS_UPDATE], TRUE) && isset($map_row['destid1'])
        ? $this->store->load((string) $map_row['destid1'])
        : NULL;
      if ($subtree === NULL) {
        throw new MigrateSkipRowException(sprintf('Paragraph %d has not been migrated yet. Run the %s migration first.', $paragraph->id(), $migration_id), FALSE);
      }
      array_push($tree, ...$subtree);
    }

    return $tree;
  }

  /**
   * Returns the ID map row for a paragraph in a paragraph migration.
   */
  protected function getMapRow(string $migration_id, ParagraphInterface $paragraph): ?array {
    $migration = $this->migrationPluginManager->createInstance($migration_id);
    if (!$migration instanceof MigrationInterface) {
      throw new MigrateSkipRowException(sprintf('Migration %s does not exist.', $migration_id));
    }
    $source_ids = array_combine(
      array_keys($migration->getSourcePlugin()->getIds()),
      [$paragraph->id(), $paragraph->language()->getId()],
    );
    return $migration->getIdMap()->getRowBySource($source_ids) ?: NULL;
  }

}
