<?php

namespace Drupal\paragraphs\EventSubscriber;

use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that handles cloning through the Replicate module.
 */
class ReplicateFieldSubscriber implements EventSubscriberInterface {

  /**
   * The replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * ReplicateFieldSubscriber constructor.
   *
   * @param \Drupal\replicate\Replicator $replicator
   *   The replicator service.
   */
  public function __construct(Replicator $replicator) {
    $this->replicator = $replicator;
  }

  /**
   * Replicates paragraphs when the parent entity is being replicated.
   *
   * @param \Drupal\replicate\Events\ReplicateEntityFieldEvent $event
   */
  public function onClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();
    if ($field_item_list->getItemDefinition()->getSetting('target_type') == 'paragraph') {
      foreach ($field_item_list as $field_item) {
        if ($field_item->entity) {
          $paragraph = clone $field_item->entity;
          $parent = $field_item->getEntity();
          $original_langcodes = array_keys($paragraph->getTranslationLanguages());
          $langcodes = array_keys($parent->getTranslationLanguages());
          if ($removed_langcodes = array_diff($original_langcodes, $langcodes)) {
            foreach ($removed_langcodes as $removed_langcode) {
              if ($paragraph->hasTranslation($removed_langcode)  && $paragraph->getUntranslated()->language()->getId() != $removed_langcode) {
                $paragraph->removeTranslation($removed_langcode);
              }
            }
          }
          $field_item->entity = $this->replicator->replicateEntity($paragraph);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    // Only register the event on Drupal 11.1 and earlier.
    // @todo Remove this once paragraphs requires Drupal 11.2.
    if (version_compare(\Drupal::VERSION, '11.1.99', '<=')) {
      $events[ReplicatorEvents::replicateEntityField('entity_reference_revisions')][] = 'onClone';
    }
    return $events;
  }

}
