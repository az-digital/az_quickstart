<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\destination;

use Drupal\az_canvas_migrate\CanvasComponentStore;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stores the Canvas components migrated from one paragraph.
 *
 * Canvas components are not entities, so there is no entity destination for
 * a paragraph migration to write to. Components are held in
 * CanvasComponentStore until the page migration looks them up and assembles
 * them into a Canvas page.
 *
 * Available configuration keys:
 * - component: (optional) The root SDC plugin ID, e.g.
 *   az_quickstart:ranking-deck.
 *
 * The migration's process describes one root single-directory component
 * (SDC):
 * - component: (optional) The SDC plugin ID. Overrides the configured
 *   component for a row.
 * - inputs: Prop values, keyed by prop name.
 * - slots: Child components, keyed by slot name. Each child has the same
 *   component/inputs/slots structure, e.g. as built by sub_process.
 *
 * This destination flattens that into Canvas component tree items: it
 * assigns UUIDs and parent/slot references, and converts SDC plugin IDs to
 * Canvas component IDs. Inputs the component does not define are dropped,
 * which lets one sub_process build inputs for several component types, and
 * so are empty values, so Canvas falls back to the component's defaults.
 *
 * @code
 * process:
 *   inputs/columns_desktop: ...
 *   slots/rankings:
 *     plugin: sub_process
 *     source: field_az_rankings
 *     process:
 *       component: ...
 *       inputs/heading: ranking_heading
 * destination:
 *   plugin: az_canvas_component
 *   component: az_quickstart:ranking-deck
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
    protected ComponentPluginManager $componentManager,
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
      $container->get('plugin.manager.sdc'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $tree = [];
    $this->flatten([
      'component' => $row->getDestinationProperty('component') ?? $this->configuration['component'] ?? NULL,
      'inputs' => $row->getDestinationProperty('inputs'),
      'slots' => $row->getDestinationProperty('slots'),
    ], NULL, NULL, $tree);
    // Keep the ID stable on update, so page migration lookups still match.
    $id = $old_destination_id_values[0] ?? $this->uuid->generate();
    $this->store->save($id, $tree);
    return [$id];
  }

  /**
   * Appends a component and its descendants as Canvas component tree items.
   *
   * @param array $definition
   *   The component/inputs/slots structure for one component.
   * @param string|null $parent_uuid
   *   The parent component's UUID, or NULL for the root.
   * @param string|null $slot
   *   The parent slot this component is placed in, or NULL for the root.
   * @param array<int, array<string, mixed>> $tree
   *   The tree items collected so far, parents before their children.
   */
  protected function flatten(array $definition, ?string $parent_uuid, ?string $slot, array &$tree): void {
    $component_id = $definition['component'] ?? NULL;
    if (!is_string($component_id) || $component_id === '') {
      throw new MigrateException('Each component needs a "component" SDC plugin ID.');
    }
    try {
      $metadata = $this->componentManager->find($component_id)->metadata;
    }
    catch (ComponentNotFoundException $e) {
      throw new MigrateException(sprintf('Unknown component "%s".', $component_id), 0, $e);
    }

    $inputs = array_intersect_key((array) ($definition['inputs'] ?? []), $metadata->schema['properties'] ?? []);
    $item = [
      'uuid' => $this->uuid->generate(),
      // Canvas names the config entity for an SDC "sdc.PROVIDER.NAME".
      'component_id' => 'sdc.' . str_replace(':', '.', $component_id),
      'inputs' => $this->removeEmptyValues($inputs),
    ];
    if ($parent_uuid !== NULL) {
      $item['parent_uuid'] = $parent_uuid;
      $item['slot'] = $slot;
    }
    $tree[] = $item;

    foreach ((array) ($definition['slots'] ?? []) as $slot_name => $children) {
      if (!isset($metadata->slots[$slot_name])) {
        throw new MigrateException(sprintf('Component "%s" has no "%s" slot.', $component_id, $slot_name));
      }
      foreach ((array) $children as $child) {
        $this->flatten((array) $child, $item['uuid'], $slot_name, $tree);
      }
    }
  }

  /**
   * Recursively drops NULL and '' values, and arrays emptied by doing so.
   *
   * Arrays that were empty to begin with, such as a link's options, are
   * kept. Lists are re-indexed so they stay lists.
   */
  protected function removeEmptyValues(array $values): array {
    $result = [];
    foreach ($values as $key => $value) {
      if (is_array($value) && $value !== []) {
        $value = $this->removeEmptyValues($value);
        if ($value === []) {
          continue;
        }
      }
      if ($value !== NULL && $value !== '') {
        $result[$key] = $value;
      }
    }
    return array_is_list($values) ? array_values($result) : $result;
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
    return [
      'component' => 'The root SDC plugin ID',
      'inputs' => 'The root component prop values',
      'slots' => 'Child components, keyed by slot name',
    ];
  }

}
