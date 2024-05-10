<?php

namespace Drupal\az_enterprise_attributes_import\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides API integration for Trellis Views.
 */
final class AZEnterpriseAttributesEventSubscriber implements EventSubscriberInterface, LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigratePlusEvents::MISSING_SOURCE_ITEM => 'onMissingRow',
    ];
  }

  /**
   * Constructs an AZEventTrellisDataSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Respond to events for missing source rows.
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The row delete event.
   */
  public function onMissingRow(MigrateRowDeleteEvent $event) {
    $migration = $event->getMigration()->getBaseId();
    if ($migration === 'az_enterprise_attributes_import') {
      $ids = $event->getDestinationIdValues();
      $tid = $ids['tid'] ?? '';
      if (!empty($tid)) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
        if (!empty($term)) {
          $this->logger->notice('Unpublishing %title, tid @tid.',
          [
            '%title' => $term->label(),
            '@tid' => $term->id(),
          ]);
          $term->setUnpublished();
          $term->save();
        }
      }
    }
  }

}
