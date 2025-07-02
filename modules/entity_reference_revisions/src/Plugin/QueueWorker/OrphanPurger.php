<?php

namespace Drupal\entity_reference_revisions\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes composite revisions that are no longer used.
 *
 * @QueueWorker(
 *   id = "entity_reference_revisions_orphan_purger",
 *   title = @Translation("Entity Reference Revisions Orphan Purger"),
 *   cron = {"time" = 60}
 * )
 */
class OrphanPurger extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The purger.
   *
   * @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger
   */
  protected $purger;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new OrphanPurger instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsOrphanPurger $purger
   *   The purger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityReferenceRevisionsOrphanPurger $purger, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->purger = $purger;
    $this->database = $database;
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
      $container->get('entity_reference_revisions.orphan_purger'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $entity_type_id = $data['entity_type_id'];
    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      return;
    }

    // Check the usage of data item and remove if not used.
    $composite_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $composite_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $composite_revision_key = $composite_type->getKey('revision');

    // Load all revisions of the composite type.
    // @todo Replace with an entity query on all revisions with a revision ID
    //   condition after https://www.drupal.org/project/drupal/issues/2766135.
    $entity_revision_ids = $this->database->select($composite_type->getRevisionTable(), 'r')
      ->fields('r', [$composite_revision_key])
      ->condition($composite_type->getKey('id'), $data['entity_id'])
      ->orderBy($composite_revision_key)
      ->execute()
      ->fetchCol();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $composite_revision */
    foreach ($composite_storage->loadMultipleRevisions($entity_revision_ids) as $composite_revision) {
      if (!$this->purger->isUsed($composite_revision)) {
        $this->purger->deleteUnusedRevision($composite_revision);
      }
    }
  }

}
