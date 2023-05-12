<?php

namespace Drupal\az_publication_bibtex\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\Core\Messenger\Messenger;

/**
 * Reponse to BibTeX migration import events.
 */
class AZPublicationBibtexEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs an AZPublicationBibtexEventSubscriber.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Database connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(Messenger $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    return $events;
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
    if ($migration === 'az_publication_bibtex_import') {
      $publication = $this->nodeStorage->load($id);
      $url = $publication->toUrl()->toString();
      if (!empty($publication)) {
        $this->messenger->addMessage(t('Imported <a href="@publink">@pubtitle</a>.', [
          '@publink' => $url,
          '@pubtitle' => $publication->getTitle(),
        ]));
      }
    }
  }

}
