<?php

namespace Drupal\migmag\Traits;

use Drupal\Core\Database\Connection;
use Drupal\migrate_drupal\MigrationConfigurationTrait;

if (trait_exists(MigrationConfigurationTrait::class, FALSE)) {
  /**
   * Shim trait for determining source Drupal version.
   */
  trait MigMagMigrationConfigurationTrait {

    use MigrationConfigurationTrait {
      getLegacyDrupalVersion as private;
    }

    /**
     * Determines what version of Drupal the source database contains.
     *
     * @param \Drupal\Core\Database\Connection $connection
     *   The database connection object.
     *
     * @return string|false
     *   A string representing the major branch of Drupal core (e.g. '6' for
     *   Drupal 6.x), or FALSE if no valid version is matched.
     */
    private static function getSourceDrupalVersion(Connection $connection) {
      return static::getLegacyDrupalVersion($connection);
    }

  }
}
else {
  /**
   * Shim trait for determining source Drupal version.
   */
  trait MigMagMigrationConfigurationTrait {

    /**
     * Determines what version of Drupal the source database contains.
     *
     * @param \Drupal\Core\Database\Connection $connection
     *   The database connection object.
     *
     * @return string|false
     *   A string representing the major branch of Drupal core (e.g. '6' for
     *   Drupal 6.x), or FALSE if no valid version is matched.
     */
    private static function getSourceDrupalVersion(Connection $connection) {
      return FALSE;
    }

  }
}
