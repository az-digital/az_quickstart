<?php

declare(strict_types=1);

namespace Drupal\az_core\Drush\Commands;

use CLI\Usage;
use CLI\FieldLabels;
use CLI\DefaultFields;
use CLI\Command;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class AZEntityListCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AzEntityListDrushCommands object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * List entities enabled on an Arizona Quickstart site.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A table with a row for each asset type and count.
   */
  #[Command(name: 'az-entity-list:list', aliases: ['ael'])]
  #[DefaultFields(fields: [
    'entity_type',
    'bundle',
    'count',
    'entity_type_provider',
  ])]
  #[FieldLabels(labels: [
    'entity_type' => 'Entity type',
    'bundle' => 'Bundle',
    'count' => 'Count',
    'entity_type_provider' => 'Entity Type Provider',
  ])]
  #[Usage(name: 'drush az-entity-list:list', description: "List entities enabled on an Arizona Quickstart site.")]

  public function list(array $options = ['format' => 'table']): RowsOfFields {
    $all_results = [];
    $entity_types = array_keys($this->entityTypeManager->getDefinitions());

    foreach ($entity_types as $entity_type) {
      // If the entity has bundle types add them to the list. Otherwise, just
      // use the entity type as the bundle.
      $bundle_types = $this->getBundleTypes($entity_type);
      $entity_type_provider = $this->entityTypeManager->getDefinition($entity_type)->getProvider();
      foreach ($bundle_types as $bundle) {
        $count = $this->getEntityCount($entity_type, $bundle);
        if ($count === 0) {
          continue;
        }
        $all_results[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'count' => $count,
          'entity_type_provider' => $entity_type_provider,
        ];
      }
    }

    return new RowsOfFields($all_results);
  }

  /**
   * List entity bundles enabled on an Arizona Quickstart site.
   */
  private function getBundleTypes(string $entity_type): array {
    try {
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      if ($bundle = $entity_type_definition->getBundleEntityType()) {
        $bundles = $this->entityTypeManager->getStorage($bundle)->loadMultiple();
        return array_keys($bundles);
      }
    }
    catch (\Exception $e) {
    }
    return [$entity_type];

  }

  /**
   * Get the count of entities for a given entity type and bundle.
   */
  private function getEntityCount(string $entity_type, string $bundle): int {
    try {
      $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      $bundle_field = $entity_type_definition->getKey('bundle');
      $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
      $query->accessCheck(FALSE);
      if ($bundle_field) {
        $query->condition($bundle_field, $bundle);
      }
      return $query->count()->execute();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

}
