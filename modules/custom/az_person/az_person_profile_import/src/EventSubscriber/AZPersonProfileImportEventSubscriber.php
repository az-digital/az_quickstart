<?php

namespace Drupal\az_person_profile_import\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Repond to import of persons from the profiles API.
 */
class AZPersonProfileImportEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs an AZPersonProfileImportEventSubscriber.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(Messenger $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    $events[MigrateEvents::IDMAP_MESSAGE] = ['onMapMessage'];
    return $events;
  }

  /**
   * Respond to events on migration message.
   *
   * @param \Drupal\migrate\Event\MigrateIdMapMessageEvent $event
   *   The map message event object.
   */
  public function onMapMessage(MigrateIdMapMessageEvent $event) {
    $migration = $event->getMigration()->getBaseId();
    // Only emit warnings for the profile import.
    if ($migration === 'az_person_profile_import') {
      $sourceIds = $event->getSourceIdValues();
      $netid = $sourceIds['netid'] ?? '';
      $message = $event->getMessage();
      // Consume name of migration and field that prepends message.
      $message = preg_replace('/^.*:.*: /', '', $message);
      // Output the migration message.
      $this->messenger->addWarning(t('NetID %netid @message.', ['%netid' => $netid, '@message' => $message]));
    }
  }

  /**
   * Respond to events on migration import for relevant migrations.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post save event object.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration()->getBaseId();
    $ids = $event->getDestinationIdValues();
    $id = reset($ids);
    if ($migration === 'az_person_profile_import') {
      $person = $this->entityTypeManager->getStorage('node')->load($id);
      if (!empty($person)) {
        $url = $person->toUrl()->toString();
        $this->messenger->addMessage(t('Imported <a href="@link">@name</a>.', [
          '@link' => $url,
          '@name' => $person->getTitle(),
        ]));
      }
    }
  }

}
