<?php

declare(strict_types = 1);

namespace Drupal\az_core\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
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
    EntityTypeManagerInterface $entity_type_manager
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
  #[CLI\Command(name: 'az-entity-list:list', aliases: ['ael'])]
  #[CLI\FieldLabels(['entity_type' => 'Entity type', 'bundle' => 'Bundle', 'count' => 'Count'])]
  #[CLI\DefaultFields(['entity_type', 'bundle', 'count'])]
  public function list(array $options = ['format' => 'table']): RowsOfFields {
    $all_results = [];
    $entity_types = array_keys($this->entityTypeManager->getDefinitions());
    $bundle_types = [];

    foreach ($entity_types as $entity_type) {
      try {
        $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      }
      catch (\Exception $e) {
        // The entity type does not exist.
        continue;
      }

      // Check if the entity type has bundles.
      if ($bundle = $entity_type_definition->getBundleEntityType()) {
        // Load all bundles for this entity type.
        $bundles = $this->entityTypeManager->getStorage($bundle)->loadMultiple();
        $bundle_types[$entity_type] = array_keys($bundles);
      }
    }

    foreach ($bundle_types as $entity_type => $bundles) {
      try {
        $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);
      }
      catch (\Exception $e) {
        // The entity type does not exist.
        continue;
      }

      foreach ($bundles as $bundle) {
        $bundle_field = $entity_type_definition->getKey('bundle');
        $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
        $query->accessCheck(FALSE);
        $query->condition($bundle_field, $bundle);
        $results = $query->count()->execute();
        array_push($all_results, [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'count' => $results,
        ]);
      }
    }
    return new RowsOfFields($all_results);
  }

}
