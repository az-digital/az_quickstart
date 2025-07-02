<?php

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\migmag\Utility\MigMagMigrationUtility;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Wraps the specified process pipeline into a try catch clause.
 *
 * Configuration options:
 * - process: the process pipeline to process.
 * - multiple: whether the plugin should handle multiple values or not.
 *   Optional, defaults to FALSE.
 * - catch: an array of the values to return if a specific exception is thrown.
 *   Return values must be keyed by the exception FQCN. Optional, defaults to
 *   ['Exception' => NULL].
 * - saveMessage: The plugin can be configured to catch MigrateException or
 *   MigrateSkipRowException. When these are caught, MigrateExecutable won't
 *   know about them, so it won't save anything into the corresponding migration
 *   message table. If saveMessage isn't set to FALSE, this plugin will save the
 *   messages the same way how MigrateExecutable would do. Defaults to TRUE.
 *
 * Example:
 * @code
 * process:
 *   link/uri:
 *     plugin: migmag_try
 *     catch:
 *       Exception: 'route:<current>'
 *     process:
 *       plugin: link_uri
 *       source: link_path
 *     saveMessage: false
 * @endcode
 *
 * @see https://drupal.org/node/3253230
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_try"
 * )
 */
class MigMagTry extends ProcessPluginBase {

  /**
   * The migration being processed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Constructs a new MigMagTry instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The actually processed migration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $configuration += ['catch' => ['Exception' => NULL], 'saveMessage' => TRUE];
    $configuration['process'] = MigMagMigrationUtility::getAssociativeMigrationProcess($configuration['process']);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      $row_clone = clone $row;
      $migrate_executable->processRow(
        $row_clone,
        [$destination_property => $this->configuration['process']]
      );
      return $row_clone->getDestinationProperty($destination_property);
    }
    catch (\Exception $exception) {
    }
    catch (\Throwable $throwable) {
    }

    $return_value_set = FALSE;
    $e_or_t = $exception ?? $throwable;
    $e_or_t_class_and_parents = array_merge(
      [get_class($e_or_t)],
      array_values(
        class_parents($exception ?? $throwable, FALSE)
      )
    );
    foreach ($e_or_t_class_and_parents as $e_or_t_class) {
      if (array_key_exists($e_or_t_class, $this->configuration['catch'])) {
        $return_value = $this->configuration['catch'][$e_or_t_class];
        $return_value_set = TRUE;
        break 1;
      }

      if (array_key_exists('\\' . $e_or_t_class, $this->configuration['catch'])) {
        $return_value = $this->configuration['catch']['\\' . $e_or_t_class];
        $return_value_set = TRUE;
        break 1;
      }
    }

    if (!$return_value_set) {
      throw $exception ?? $throwable;
    }

    // Save the corresponding migration message.
    // @see MigrateExecutableInterface::import().
    if (
      $this->configuration['saveMessage'] &&
      (
        $e_or_t instanceof MigrateException ||
        $e_or_t instanceof MigrateSkipRowException
      )
    ) {
      $level = $e_or_t instanceof MigrateException
        ? $e_or_t->getLevel()
        : MigrationInterface::MESSAGE_INFORMATIONAL;
      if (
        ($message = trim($e_or_t->getMessage())) ||
        $e_or_t instanceof MigrateException
      ) {
        $migrate_executable->saveMessage(
          sprintf(
            "%s:%s: %s",
            $this->migration->getPluginId(),
            $destination_property,
            $message
          ),
          $level
        );
      }
    }

    return $return_value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->configuration['multiple'] ?? FALSE;
  }

}
