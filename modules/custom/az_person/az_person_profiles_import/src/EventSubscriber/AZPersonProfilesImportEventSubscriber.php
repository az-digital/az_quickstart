<?php

namespace Drupal\az_person_profiles_import\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Repond to import of persons from the profiles API.
 */
class AZPersonProfilesImportEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs an AZPersonProfilesImportEventSubscriber.
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
    // Only emit warnings for the profiles import.
    if ($migration === 'az_person_profiles_import') {
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
    if ($migration === 'az_person_profiles_import') {
      $person = $this->entityTypeManager->getStorage('node')->load($id);
      if (!empty($person)) {
        $image = $event->getRow()->get('image_url');
        // See if we have an image url.
        // @todo move this functionality to process plugin.
        if (!empty($image) && UrlHelper::isValid($image)) {
          $fileStorage = $this->entityTypeManager->getStorage('file');
          // Check if we already have a managed file for this Url.
          $files = $fileStorage->loadByProperties(['uri' => $image]);
          $file = reset($files);
          if (!$file) {
            $file = $fileStorage->create([
              'uri' => $image,
            ]);
            $file->save();
          }
          // Hook up file to media field or create new media entity.
          if ($person->hasField('field_az_media_image')) {
            /** @var \Drupal\media\MediaInterface $media */
            $media = $person->field_az_media_image->entity;
            // Media entity needs to be updated.
            if ($media) {
              \Drupal::logger('my_module')->notice("we are updating");
              // @todo cleanup this field access do be configured by process plugin.
              // @phpstan-ignore-next-line
              $media->field_media_az_image->target_id = $file->id();
              $media->save();
            }
            else {
              // Media doesn't exist yet, need to create it.
              $media = $this->entityTypeManager->getStorage('media')->create([
                'bundle' => 'az_image',
                'field_media_az_image' => [
                  'target_id' => $file->id(),
                ],
              ]);
              $media->save();
              $person->field_az_media_image->target_id = $media->id();
              $person->save();
            }
          }
        }
        $url = $person->toUrl()->toString();
        $this->messenger->addMessage(t('Imported <a href="@link">@name</a>.', [
          '@link' => $url,
          '@name' => $person->getTitle(),
        ]));
      }
    }
  }

}
