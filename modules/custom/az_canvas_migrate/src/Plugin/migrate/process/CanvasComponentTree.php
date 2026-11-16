<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\az_canvas_migrate\CanvasComponentStore;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
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
 * recorded as migrate messages. A supported paragraph that has not been
 * migrated yet skips the row without recording it in the ID map, so a page is
 * never created with missing content and the row is retried on the next
 * import.
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
    protected MigrateLookupInterface $migrateLookup,
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
      $container->get('migrate.lookup'),
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

      $destination_ids = $this->migrateLookup->lookup($migration_id, [$paragraph->id(), $paragraph->language()->getId()]);
      $subtree = $destination_ids ? $this->store->load((string) reset($destination_ids[0])) : NULL;
      if ($subtree === NULL) {
        throw new MigrateSkipRowException(sprintf('Paragraph %d has not been migrated yet. Run the %s migration first.', $paragraph->id(), $migration_id), FALSE);
      }
      array_push($tree, ...$subtree);
    }

    return $tree;
  }

}
