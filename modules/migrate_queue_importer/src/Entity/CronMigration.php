<?php

namespace Drupal\migrate_queue_importer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * The cron_migration configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "cron_migration",
 *   label = @Translation("Cron migration"),
 *   handlers = {
 *     "list_builder" = "Drupal\migrate_queue_importer\Controller\CronMigrationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\migrate_queue_importer\Form\CronMigrationForm",
 *       "edit" = "Drupal\migrate_queue_importer\Form\CronMigrationForm",
 *       "delete" = "Drupal\migrate_queue_importer\Form\CronMigrationDeleteForm",
 *     }
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/migrate_queue_importer/cron_migration/{cron_migration}",
 *     "delete-form" = "/admin/config/migrate_queue_importer/cron_migration/{cron_migration}/delete",
 *     "enable" = "/admin/config/migrate_queue_importer/cron_migration/{cron_migration}/enable",
 *     "disable" = "/admin/config/migrate_queue_importer/cron_migration/{cron_migration}/disable"
 *   },
 *   admin_permission = "administer cron migrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "migration",
 *     "time",
 *     "update",
 *     "sync",
 *     "ignore_dependencies"
 *   }
 * )
 */
class CronMigration extends ConfigEntityBase {

  /**
   * The ID of the cron migration.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the cron migration.
   *
   * @var string
   */
  public $label;

  /**
   * The machine name of the migration.
   *
   * @var string
   */
  public $migration;

  /**
   * The interval of migration import.
   *
   * @var int
   */
  public $time;

  /**
   * Flag to force migration update.
   *
   * @var bool
   */
  public $update;

  /**
   * Flag to sync the content.
   *
   * @var bool
   */
  public $sync;

  /**
   * Flag to ignore migration dependencies while importing.
   *
   * @var bool
   */
  public $ignore_dependencies;

}
