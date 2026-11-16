<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\destination;

use Drupal\az_canvas_migrate\CanvasComponentStore;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stores a Canvas component subtree migrated from one paragraph.
 *
 * Canvas components are not entities, so there is no entity destination for
 * a paragraph migration to write to. Subtrees are held in
 * CanvasComponentStore until the page migration looks them up with
 * migration_lookup semantics and assembles them into a Canvas page.
 *
 * Expects a `tree` destination property holding component tree items.
 *
 * @code
 * destination:
 *   plugin: az_canvas_component
 * @endcode
 */
#[MigrateDestination('az_canvas_component')]
class AzCanvasComponent extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    protected CanvasComponentStore $store,
    protected UuidInterface $uuid,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('az_canvas_migrate.component_store'),
      $container->get('uuid'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $tree = $row->getDestinationProperty('tree');
    if (!is_array($tree)) {
      throw new MigrateException('The "tree" destination property must be an array of component tree items.');
    }
    // Keep the ID stable on update, so page migration lookups still match.
    $id = $old_destination_id_values[0] ?? $this->uuid->generate();
    $this->store->save($id, $tree);
    return [$id];
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $this->store->delete($destination_identifier['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['tree' => 'Canvas component tree items'];
  }

}
