<?php

declare(strict_types=1);

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase as CoreProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin for logging the actual values of migrate process pipelines.
 *
 * Example:
 * @code
 * process:
 *   destination:
 *     plugin: migmag_logger_log
 *     message: 'Sprintf compatible message %s'
 *     source: foo
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_logger_log",
 *   handle_multiples = TRUE
 * )
 */
class MigMagLoggerLog extends CoreProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The actual migration plugin instance.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new LoggerLog plugin instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The actual migration plugin instance.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger_channel
   *   The logger channel.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, LoggerChannelInterface $logger_channel) {
    $configuration += [
      'message' => NULL,
      'log_level' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger_channel;
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('logger.factory')->get($configuration['logger_channel'] ?? 'default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value_array = is_array($value)
      ? array_map(
          [get_class($this), 'getHumanFriendlyValue'],
          (array) $value
        )
      : [static::getHumanFriendlyValue($value)];
    $value_is_single = count($value_array) < 2;
    $log_level = $this->configuration['log_level'] ?? RfcLogLevel::INFO;

    if ($value_is_single) {
      $message_fallback = reset($value_array);
    }
    else {
      $value_array_is_indexed = array_keys($value_array) === range(0, count($value_array) - 1);
      $message_fallback = implode(
        ', ',
        array_map(
          function ($value, $key) use ($value_array_is_indexed) {
            return $value_array_is_indexed
              ? $value
              : "$key => $value";
          },
          $value_array,
          array_keys($value_array)
        )
      );
      $message_fallback = version_compare(self::normalizedVersion(\Drupal::VERSION), '11.1', 'le')
        ? "(array) array($message_fallback)"
        : "(array) [$message_fallback]";
    }
    $message = $this->configuration['message'] ?? $message_fallback;
    $values_without_keys = array_values($value_array);

    if (!is_null($this->configuration['message'])) {
      // Prevent sprintf() too few arguments error.
      $placeholder_count = (int) preg_match_all("/(?<!\\\)%s/", $message);
      if ($placeholder_count > count($values_without_keys)) {
        $values_without_keys = array_merge(
          $values_without_keys,
          array_fill(count($values_without_keys), $placeholder_count - count($values_without_keys), '%s')
        );
      }
    }

    $row_source_id_values = $row->getSourceIdValues();
    $this->logger->log(
      $log_level,
      sprintf($message, ...$values_without_keys),
      [
        'migration_plugin_id' => $this->migration->id(),
        'source_id_values' => empty($row_source_id_values) ? 'No source IDs (maybe a subprocess?)' : $row_source_id_values,
      ]
    );

    // Return the value we received.
    return $value;
  }

  /**
   * Returns a developer-friendly representation of the passed variable.
   *
   * @param mixed $variable
   *   A variable to parse.
   *
   * @return string
   *   The human-friendly representation of the variable.
   */
  protected static function getHumanFriendlyValue($variable) {
    $type = gettype($variable);
    $var_export = Variable::export($variable);
    $var_human = preg_replace(
      [
        "/,\\n\s+/",
        "/,\s+\)/",
        "/array\s?\(\\n?\s*/",
        "/ =>\s*\\n*\s*/",
        '/\[\\n\s*/',
        '/,\s+\]/',
      ],
      [
        ', ',
        ')',
        'array(',
        ' => ',
        '[',
        ']',
      ],
      $var_export
    );

    switch ($type) {
      case 'object':
        return $var_human;

      default:
        return is_null($variable) ? 'NULL' : "($type) $var_human";
    }
  }

  /**
   * Normalized version, just like how it is calculated in DeprecationHelper.
   *
   * @param string $version
   *   A version string.
   *
   * @return string
   *   Normalized version, without any stability suffix; containing three
   *   digits.
   *
   * @see \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall()
   */
  protected static function normalizedVersion($version): string {
    return str_ends_with($version, '-dev')
      ? str_replace(['.x-dev', '-dev'], '.0', $version)
      : $version;
  }

}
