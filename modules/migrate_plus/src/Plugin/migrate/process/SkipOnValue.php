<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * If the source evaluates to a configured value, skip processing or whole row.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_value"
 * )
 *
 * Available configuration keys:
 * - value: An single value or array of values against which the source value
 *   should be compared.
 * - not_equals: (optional) If set, skipping occurs when values are not equal.
 * - method: What to do if the input value equals to value given in
 *   configuration key value. Possible values:
 *   - row: Skips the entire row.
 *   - process: Prevents further processing of the input property
 * - message: (optional) A message to be logged in the {migrate_message_*} table
 *   for this row. Messages are only logged for the 'row' method. If not set,
 *   nothing is logged in the message table.
 *
 * @codingStandardsIgnoreStart
 *
 * Examples:
 *
 * Example usage with minimal configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     source: content_type
 *     method: process
 *     value: blog
 * @endcode
 * The above example will skip further processing of the input property if
 * the content_type source field equals "blog".
 *
 * Example usage with a FieldAPI value:
 * @code
 *   field_fruit:
 *     plugin: skip_on_value
 *     source: field_fruit/0/value
 *     method: row
 *     value: apple
 * @endcode
 * The above example will skip the entire row if the "fruit" field is set to
 * "apple". When attempting to access values from a simple Field API-based value
 * the "0/value" suffix must be used, otherwise it will fail with an "Array to
 * string conversion" error.
 *
 * Example usage with full configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     not_equals: true
 *     source: content_type
 *     method: row
 *     value:
 *       - article
 *       - testimonial
 *     message: 'Not an article nor a testimonial content type'
 * @endcode
 * The above example will skip processing any row for which the source row's
 * content type field is not "article" or "testimonial", and log the message 'Not
 * an article nor a testimonial content type' to the message table.
 *
 * @codingStandardsIgnoreEnd
 */
class SkipOnValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (empty($configuration['value']) && !array_key_exists('value', $configuration)) {
      throw new \InvalidArgumentException('Skip on value plugin is missing value configuration.');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Skips the current row when input value evaluates to a configured value.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it doesn't evaluate to a configured value.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if the source property evaluates to a configured value and the
   *   row should be skipped, records with STATUS_IGNORED status in the map.
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property) {
    $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';

    if (is_array($this->configuration['value'])) {
      $value_in_array = FALSE;
      $not_equals = isset($this->configuration['not_equals']);

      foreach ($this->configuration['value'] as $skipValue) {
        $value_in_array |= $this->compareValue($value, $skipValue);
      }

      if (($not_equals && !$value_in_array) || (!$not_equals && $value_in_array)) {
        throw new MigrateSkipRowException($message);
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipRowException($message);
    }

    return $value;
  }

  /**
   * Stops processing the current property.
   *
   * Stop when input value evaluates to a configured value.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it doesn't evaluate to a configured value.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *   Thrown if the source property evaluates to a configured value and rest
   *   of the process should be skipped.
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property) {
    if (is_array($this->configuration['value'])) {
      $value_in_array = FALSE;
      $not_equals = isset($this->configuration['not_equals']);

      foreach ($this->configuration['value'] as $skipValue) {
        $value_in_array |= $this->compareValue($value, $skipValue);
      }

      if (($not_equals && !$value_in_array) || (!$not_equals && $value_in_array)) {
        throw new MigrateSkipProcessException();
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipProcessException();
    }

    return $value;
  }

  /**
   * Compare values to see if they are equal.
   *
   * @param mixed $value
   *   Actual value.
   * @param mixed $skipValue
   *   Value to compare against.
   * @param bool $equal
   *   Compare as equal or not equal.
   *
   *   True if the compare successfully, FALSE otherwise.
   */
  protected function compareValue($value, $skipValue, bool $equal = TRUE): bool {
    if ($equal) {
      return (string) $value == (string) $skipValue;
    }

    return (string) $value != (string) $skipValue;

  }

}
