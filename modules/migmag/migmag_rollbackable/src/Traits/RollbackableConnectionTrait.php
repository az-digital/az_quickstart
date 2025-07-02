<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Traits;

/**
 * Trait for getting the DB connection where rollback data is stored.
 */
trait RollbackableConnectionTrait {

  /**
   * The database connection where rollback data and states are stored.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $rollbackDataConnection;

  /**
   * Returns the database connection where rollback data is stored.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection where rollback data is stored.
   */
  protected function getConnection() {
    if (!$this->rollbackDataConnection) {
      $this->rollbackDataConnection = \Drupal::database();
    }

    return $this->rollbackDataConnection;
  }

}
