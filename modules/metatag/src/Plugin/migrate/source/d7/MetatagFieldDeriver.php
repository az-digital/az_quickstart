<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates field plugins for each entity type.
 */
class MetatagFieldDeriver extends DeriverBase implements ContainerDeriverInterface {

  use MigrationDeriverTrait;
  use MigrationConfigurationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MetatagFieldDeriver instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = $this->getSourcePlugin('d7_metatag_field');
    assert($source instanceof DrupalSqlBase);

    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the source plugin requirements failed, that means we do not have a
      // Drupal source database configured - return nothing.
      return $this->derivatives;
    }

    $entity_type_ids = $source->getDatabase()->select('metatag', 'm')
      ->fields('m', ['entity_type'])
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);
    foreach ($entity_type_ids as $entity_type_id) {
      // Skip if the entity type is missing.
      if (!$this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
        continue;
      }

      $this->derivatives[$entity_type_id] = $base_plugin_definition;
      $this->derivatives[$entity_type_id]['source']['entity_type'] = $entity_type_id;
      $this->derivatives[$entity_type_id]['source']['entity_type_id'] = $entity_type_id;
      $this->derivatives[$entity_type_id]['label'] = $this->t('@label of @type', [
        '@label' => $base_plugin_definition['label'],
        '@type' => $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel(),
      ]);
    }

    return $this->derivatives;
  }

}
