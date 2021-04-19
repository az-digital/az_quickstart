<?php

namespace Drupal\az_migration\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateRollbackEvent;

/**
 * Class MigrationSubscriber.
 *
 * Handles various migrations tasks outside of normal flow.
 *
 * @package Drupal\az_migration
 */
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Constructs a new MigrationSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManager $entity_field_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROLLBACK][] = ['onMigratePreRollback'];
    return $events;
  }

  /**
   * React to rollback start.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The map event.
   */
  public function onMigratePreRollback(MigrateRollbackEvent $event) {
    $dest = $event->getMigration()->getDestinationConfiguration();
    if (!isset($dest['plugin']) && !isset($dest['bundle'])) {
      return;
    }
    // Grab our type and make the magiz happen.
    $this->entityType = ltrim(strstr($dest['plugin'], ':'), ':');
    $this->bundle = $dest['bundle'];
    $this->checkFieldsforMediaEntities();
  }

  /**
   * Checks the nodes fields for media entities.
   */
  private function checkFieldsforMediaEntities() {
    // Grab all our fields for this entity type.
    $fields = $this->entityFieldManager
      ->getFieldDefinitions($this->entityType, $this->bundle);

    foreach ($fields as $field_name => $field_definition) {
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      if ($field_definition->getTargetBundle() !== NULL) {
        if ($field_definition->getType() === 'entity_reference'
            && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'media') {
          $this->removeMediaEntities($field_name);
        }
      }
    }
  }

  /**
   * Remove the media entities for that field and type.
   *
   * @param string $field_name
   *   The field name we are checking.
   */
  private function removeMediaEntities($field_name) {
    // Grab all our nodes to get the media ids.
    $entities = $this->entityTypeManager
      ->getStorage($this->entityType)
      ->loadByProperties([
        'type' => [$this->bundle],
      ]);

    // Go through and load up the target entity ids.
    foreach ($entities as $entity) {
      $media = [];
      $ids = $entity->get($field_name)->getValue();
      foreach ($ids as $id) {
        if (isset($id['target_id'])) {
          $media_check = $this->entityTypeManager
            ->getStorage('media')->load($id['target_id']);
          if ($media_check !== NULL) {
            $media[] = $media_check;
          }
        }
      }
      // Remove the media entites associated with that type.
      if (!empty($media)) {
        // Delete all the medias.
        $this->entityTypeManager
          ->getStorage('media')->delete($media);
      }
    }
  }

}
