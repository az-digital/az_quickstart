<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Event;

use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Wraps a prepare-row event for event listeners.
 */
class MigratePrepareRowEvent extends Event {

  protected Row $row;
  protected MigrateSourceInterface $source;
  protected MigrationInterface $migration;

  /**
   * Constructs a prepare-row event object.
   *
   * @param \Drupal\migrate\Row $row
   *   Row of source data to be analyzed/manipulated.
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   Source plugin that is the source of the event.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration entity.
   */
  public function __construct(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    $this->row = $row;
    $this->source = $source;
    $this->migration = $migration;
  }

  /**
   * Gets the row object.
   */
  public function getRow(): Row {
    return $this->row;
  }

  /**
   * Gets the source plugin.
   */
  public function getSource(): MigrateSourceInterface {
    return $this->source;
  }

  /**
   * Gets the migration plugin.
   */
  public function getMigration(): MigrationInterface {
    return $this->migration;
  }

}
