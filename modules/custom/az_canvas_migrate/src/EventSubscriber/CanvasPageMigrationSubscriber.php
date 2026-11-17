<?php

namespace Drupal\az_canvas_migrate\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate\MigrateException;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hands a Flexible Page's URL over to the Canvas page migrated from it.
 *
 * After a row is imported, the source node is unpublished and its alias is
 * moved to the new Canvas page, so visitors land on the Canvas version. On
 * rollback, the alias and published state are handed back to the node, so
 * rolling back is a full undo. Updating a page keeps its published state.
 */
class CanvasPageMigrationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The migration this subscriber reacts to.
   */
  const MIGRATION_ID = 'az_flexible_page_to_canvas_page';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    TranslationInterface $string_translation,
  ) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::PRE_ROW_SAVE => 'onPreRowSave',
      MigrateEvents::POST_ROW_SAVE => 'onPostRowSave',
      MigrateEvents::PRE_ROW_DELETE => 'onPreRowDelete',
    ];
  }

  /**
   * Keeps an existing Canvas page's published state when it is updated.
   *
   * By the time a page is updated, converting it has unpublished the source
   * node, so the node's status no longer says whether the page should be
   * published. The overwrite_properties destination option can't be used to
   * leave status alone: on update it loses the ID map row, and the page is
   * then kept on rollback.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event): void {
    if ($event->getMigration()->id() !== self::MIGRATION_ID) {
      return;
    }
    $row = $event->getRow();
    $page_id = $row->getIdMap()['destid1'] ?? NULL;
    $page = $page_id ? $this->entityTypeManager->getStorage('canvas_page')->load($page_id) : NULL;
    if ($page instanceof ContentEntityInterface) {
      $row->setDestinationProperty('status', $page->get('status')->value);
    }
  }

  /**
   * Unpublishes the source node and moves its alias to the Canvas page.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event): void {
    if ($event->getMigration()->id() !== self::MIGRATION_ID) {
      return;
    }
    $node = $this->entityTypeManager->getStorage('node')->load($event->getRow()->getSourceProperty('nid'));
    $page = $this->entityTypeManager->getStorage('canvas_page')->load($event->getDestinationIdValues()[0] ?? 0);
    if (!$node instanceof NodeInterface || !$page instanceof ContentEntityInterface) {
      return;
    }

    $alias = (string) $node->get('path')->alias;
    // A node without an alias and already unpublished has been handed over
    // before, e.g. when re-running the migration with --update.
    if ($alias === '' && !$node->isPublished()) {
      return;
    }

    $transaction = $this->database->startTransaction();
    try {
      // The node has to release the alias before the page can claim it.
      // Editing the existing item, rather than replacing it, keeps the alias
      // ID that PathItem needs to delete the stored alias, and opting out of
      // Pathauto stops it from immediately generating a new one.
      if ($alias !== '') {
        $node->get('path')->alias = '';
        $node->get('path')->pathauto = 0;
      }
      $node->setUnpublished();
      $node->setNewRevision();
      $node->setRevisionLogMessage((string) $this->t('Converted to Canvas page @id.', ['@id' => $page->id()]));
      $node->save();

      if ($alias !== '') {
        $page->get('path')->alias = $alias;
        $page->get('path')->pathauto = 0;
        $page->save();
      }
    }
    catch (\Throwable $e) {
      $transaction->rollBack();
      // Core saves the ID map row after this event, so a failure here would
      // leave a new page untracked: delete it, and throw a MigrateException
      // so the row is recorded as failed with this message.
      if (empty($event->getRow()->getIdMap()['destid1'])) {
        $page->delete();
      }
      throw new MigrateException(sprintf('Handing node %d over to Canvas page failed: %s', $node->id(), $e->getMessage()), 0, $e);
    }
  }

  /**
   * Gives the alias and published state back to the source node.
   */
  public function onPreRowDelete(MigrateRowDeleteEvent $event): void {
    $migration = $event->getMigration();
    if ($migration->id() !== self::MIGRATION_ID) {
      return;
    }
    $destination_ids = $event->getDestinationIdValues();
    $source_ids = $migration->getIdMap()->lookupSourceId($destination_ids);
    $node = $this->entityTypeManager->getStorage('node')->load($source_ids['nid'] ?? 0);
    $page = $this->entityTypeManager->getStorage('canvas_page')->load(reset($destination_ids));
    if (!$node instanceof NodeInterface || !$page instanceof ContentEntityInterface) {
      return;
    }

    // The page's own alias is deleted along with the page, so the node can
    // take the same alias now without leaving a duplicate behind.
    $alias = (string) $page->get('path')->alias;
    if ($alias !== '') {
      $node->get('path')->alias = $alias;
      $node->get('path')->pathauto = 0;
    }
    if ($page->get('status')->value) {
      $node->setPublished();
    }
    else {
      $node->setUnpublished();
    }
    $node->setNewRevision();
    $node->setRevisionLogMessage((string) $this->t('Restored after rolling back Canvas page @id.', ['@id' => $page->id()]));
    $node->save();
  }

}
