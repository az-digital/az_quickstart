<?php

namespace Drupal\Tests\migmag\Traits;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\MigrationConfigurationTrait;

/**
 * Trait for improving developer experience with migration kernel tests.
 */
trait MigMagKernelTestDxTrait {

  use MigrationConfigurationTrait;

  /**
   * Checks migration messages & shows dev friendly output if there are errors.
   *
   * @deprecated in migmag:1.7.0 and is removed from migmag:2.0.0. Use
   *   MigMagKernelTestDxTrait::assertExpectedMigrationMessages() instead.
   *
   * @see https://www.drupal.org/node/3264723
   */
  public function assertNoMigrationMessages() {
    @trigger_error(
      'MigMagKernelTestDxTrait::assertNoMigrationMessages() is deprecated in migmag:1.7.0 and is removed from migmag:2.0.0. Use MigMagKernelTestDxTrait::assertExpectedMigrationMessages() instead. See https://www.drupal.org/node/3264723',
      E_USER_DEPRECATED
    );

    $messages_as_strings = [];
    $dummies = [];
    foreach ($this->migrateMessages as $type => $messages) {
      foreach ($messages as $message) {
        $messages_as_strings[$type][] = (string) $message;
      }

      $dummies[$type] = array_fill(0, count($messages), '…');
    }

    $this->assertEquals($dummies, $messages_as_strings, 'Unexpected migrate messages were logged.');
  }

  /**
   * Checks migration messages & shows dev friendly output if there are errors.
   *
   * @param array[] $expected_messages
   *   The list of the expected messages, grouped by the message type (error,
   *   status, warning, etc).
   */
  public function assertExpectedMigrationMessages(array $expected_messages = []) {
    $unexpected_messages_as_strings = [];
    $all_messages = [];
    foreach ($this->migrateMessages ?? [] as $type => $messages) {
      foreach ($messages as $message) {
        $actual_message = (string) $message;
        $all_messages[$type][] = $actual_message;
        if (!in_array($actual_message, $expected_messages[$type] ?? [], TRUE)) {
          $unexpected_messages_as_strings[$type][] = $actual_message;
        }
      }
    }

    $this->assertEmpty(
      $unexpected_messages_as_strings,
      sprintf(
        "Unexpected migrate messages are present:\n%s",
        Variable::export($unexpected_messages_as_strings)
      )
    );
  }

  /**
   * Returns IDs of Drupal to Drupal migrations for the discovered core version.
   *
   * This helper method returns an ordered list of the migrations which should
   * be executed for upgrading an old Drupal instance to the actual one.
   *
   * 1. It gets the discovered migrations built for the discovered Drupal core
   *   source version (which are tagged with 'Drupal <major_version>').
   * 2. It determines the node migration type and removes those node migrations
   *   which shouldn't be executed.
   * 3. It removes those discovered migrations (excluding the follow-up
   *   migrations) whose source or destination requirements aren't met.
   * 4. And finally, it builds the optimal, sorted  order of the remaining
   *   migrations, respecting the discovered required and optional migration
   *   dependencies.
   *
   * Basically, this will (should) return the same sorted migration ID list
   * that Migrate Drupal UI executes.
   *
   * @param \Drupal\Core\Database\Connection|null $source_connection
   *   Database connection to the source database. Optional. If this argument
   *   isn't specified, then the method will use '$this->sourceDatabase'.
   *
   * @return string[]
   *   The same sorted migration ID list that Migrate Drupal UI executes.
   */
  protected function getOrderedDrupalMigrationIds(?Connection $source_connection = NULL) {
    if (
      $source_connection === NULL &&
      !$this->sourceDatabase instanceof Connection
    ) {
      throw new \LogicException(
        "The current test is not a migration test and it neither specifies a source database connection."
      );
    }

    if (!\Drupal::moduleHandler()->moduleExists('migrate')) {
      throw new \LogicException(
        "The migrate module has to be enabled."
      );
    }

    $drupal_core_major_version = (int) static::getLegacyDrupalVersion($source_connection ?? $this->sourceDatabase);

    $available_migrations = array_reduce(
      $this->getMigrations('unused', $drupal_core_major_version),
      function (array $carry, MigrationInterface $migration) {
        $carry[$migration->id()] = $migration;
        return $carry;
      },
      []
    );

    $available_migrations_ids = array_keys(
      $this->getMigrationPluginManager()->buildDependencyMigration($available_migrations, [])
    );

    return $available_migrations_ids;
  }

}
