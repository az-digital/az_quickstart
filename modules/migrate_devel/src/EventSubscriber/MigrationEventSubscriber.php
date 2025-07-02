<?php

namespace Drupal\migrate_devel\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drush\Drush;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MigrationEventSubscriber for Debugging Migrations.
 *
 * @class MigrationEventSubscriber
 */
class MigrationEventSubscriber implements EventSubscriberInterface {

  /**
   * Pre Row Save Function for --migrate-debug-pre.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   Pre-Row-Save Migrate Event.
   */
  public function debugRowPreSave(MigratePreRowSaveEvent $event) {
    if (PHP_SAPI !== 'cli') {
      return;
    }

    $row = $event->getRow();

    if (in_array('migrate-debug-pre', Drush::config()->get('runtime.options'))) {
      // Start with capital letter for variables since this is actually a label.
      $source = $row->getSource();
      $destination = $row->getDestination();

      // Uses Symfony VarDumper.
      // @todo Explore advanced usage of CLI dumper class for nicer output.
      // https://www.drupal.org/project/migrate_devel/issues/3151276
      dump(
        '---------------------------------------------------------------------',
        '|                             $Source                               |',
        '---------------------------------------------------------------------',
        $source,
        '---------------------------------------------------------------------',
        '|                           $Destination                            |',
        '---------------------------------------------------------------------',
        $destination
      );
    }
  }

  /**
   * Post Row Save Function for --migrate-debug.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   Post-Row-Save Migrate Event.
   */
  public function debugRowPostSave(MigratePostRowSaveEvent $event) {
    if (PHP_SAPI !== 'cli') {
      return;
    }

    $row = $event->getRow();

    if (in_array('migrate-debug', Drush::config()->get('runtime.options'))) {

      // Start with capital letter for variables since this is actually a label.
      $source = $row->getSource();
      $destination = $row->getDestination();
      $destinationIDValues = $event->getDestinationIdValues();

      // Uses Symfony VarDumper.
      // @todo Explore advanced usage of CLI dumper class for nicer output.
      // https://www.drupal.org/project/migrate_devel/issues/3151276
      dump(
        '---------------------------------------------------------------------',
        '|                             $Source                               |',
        '---------------------------------------------------------------------',
        $source,
        '---------------------------------------------------------------------',
        '|                           $Destination                            |',
        '---------------------------------------------------------------------',
        $destination,
        '---------------------------------------------------------------------',
        '|                       $DestinationIdValues                        |',
        '---------------------------------------------------------------------',
        $destinationIDValues
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_SAVE][] = ['debugRowPreSave'];
    $events[MigrateEvents::POST_ROW_SAVE][] = ['debugRowPostSave'];
    return $events;
  }

}
