<?php

declare(strict_types=1);

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Migrate process plugin for comparing two values.
 *
 * Using this plugin in combination of the 'skip_on_empty' process plugin may
 * allow you to skip evaluation of process pipelines if a specific condition is
 * or is not met.
 *
 * Configuration options:
 * - operator: The comparison operator to use. Defaults to '===' (identical).
 *   For available options check PHP documentation.
 * - return_if: The array of the return values. By default, the process plugin
 *   returns boolean FALSE if the comparison fails, or TRUE if it passes. Except
 *   in case of the spaceship operator ('<=>'), which returns integer 0,
 *   a negative integer, or a positive integer. If we need a NULL return
 *   value if the comparison evaluates to FALSE, then we should specify this
 *   configuration as ['false' => 'foo_bar_baz']. Optional.
 * - multiple: whether the plugin should handle multiple values or not.
 *   Optional, defaults to FALSE.
 *
 * Examples:
 *
 * @code
 * destination_property:
 *   plugin: migmag_compare
 *   source:
 *     - property_1
 *     - property_2
 * @endcode
 *
 * This configuration sets 'destination_property' to boolean TRUE if
 * 'property_1' is identical to 'property_2', and will set it to
 * boolean FALSE if 'property_1' and 'property_2' have different values.
 *
 * @code
 * destination_property:
 *   plugin: migmag_compare
 *   source:
 *     - property_1
 *     - property_2
 *   operator: '>='
 *   return_if:
 *     false: 'false'
 *     true: 'true'
 * @endcode
 *
 * This configuration sets 'destination_property' to string 'true' if
 * 'property_1' is greater than or equals to 'property_2'; or it sets it to
 * string 'false' if 'property_1' is less than 'property_2'.
 *
 * @code
 * destination_property:
 *   plugin: migmag_compare
 *   source:
 *     - property_1
 *     - property_2
 *   operator: '<=>'
 *   return_if:
 *     '-1': '1st less than 2nd'
 *     '0': 'equal'
 *     '1': '1st greater than 2nd'
 * @endcode
 *
 * With this configuration, 'destination_property' will be set to:
 * - '1st less than 2nd' if 'property_1' is less than 'property_2',
 * - 'equal' if 'property_1' equals to 'property_2',
 * - '1st greater than 2nd' if 'property_1' is greater than 'property_2'.
 *
 * @code
 * destination_property:
 *   -
 *     plugin: migmag_compare
 *     source:
 *       - property_1
 *       - property_2
 *   -
 *     plugin: skip_on_empty
 *     method: process
 *   -
 *     plugin: get
 *     source: property_3
 *   [...]
 * @endcode
 *
 * - If the value of 'property_1' isn't identical to 'property_2', then
 *   'migmag_compare' returns boolean FALSE. Since this is an empty value,
 *   'skip_on_empty' will stop the execution of the process plugin pipeline.
 * - If the value of 'property_1' and 'property_2' are identical,
 *   'migmag_compare' returns boolean TRUE. 'skip_on_empty' won't do anything,
 *   and the pipeline continues with the next process plugin (which returns
 *   the value of 'property_3').
 *
 * @see https://www.php.net/manual/en/language.operators.comparison.php
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_compare"
 * )
 */
class MigMagCompare extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException(
        sprintf(
          "'%s' migrate process plugin's processed value must be an array, got '%s'.",
          $this->pluginId,
          gettype($value)
        )
      );
    }
    if (count($value) < 2) {
      throw new MigrateException(
        sprintf(
          "'%s' migrate process plugin's processed array value must have at least two values.",
          $this->pluginId
        )
      );
    }
    [$variable_1, $variable_2] = array_values($value);
    $operator = $this->configuration['operator'] ?? '===';
    if (!is_string($operator)) {
      throw new MigrateException(
        sprintf(
          "'%s' migrate process plugin's operator must be a string, got '%s'.",
          $this->pluginId,
          gettype($operator)
        )
      );
    }

    try {
      $comparison_result = $this->doCompare($variable_1, $variable_2, $operator);
    }
    catch (\Throwable $t) {
      throw new MigrateException(
        sprintf(
          "Comparison failed in '%s' migrate process plugin with message: %s.",
          $this->pluginId,
          $t->getMessage()
        )
      );
    }

    if (!isset($comparison_result)) {
      throw new MigrateException(
        sprintf(
          "'%s' migrate process plugin does not support operator '%s'.",
          $this->pluginId,
          $operator
        )
      );
    }

    return $this->deliverReturnValue($comparison_result);
  }

  /**
   * Evaluated the configured comparison.
   *
   * @param mixed $value_1
   *   The first value of the comparison.
   * @param mixed $value2
   *   The second value of the comparison.
   * @param string $operator
   *   The operator to use.
   *
   * @return bool|int|null
   *   The return value of the comparison, ot NULL if the operator is
   *   unsupported.
   */
  protected function doCompare($value_1, $value2, string $operator) {
    switch ($operator) {
      case '==':
        return $value_1 == $value2;

      case '===':
        return $value_1 === $value2;

      case '!=':
      case '<>':
        return $value_1 <> $value2;

      case '!==':
        return $value_1 !== $value2;

      case '<':
        return $value_1 < $value2;

      case '<=':
        return $value_1 <= $value2;

      case '>':
        return $value_1 > $value2;

      case '>=':
        return $value_1 >= $value2;

      case '<=>':
        return $value_1 <=> $value2;
    }

    return NULL;
  }

  /**
   * Returns the appropriate configured value.
   *
   * @param bool|int $comparison_result
   *   The result of an evaluated comparison.
   *
   * @return bool|int|mixed
   *   The returned value.
   */
  protected function deliverReturnValue($comparison_result) {
    // Spaceship returns integer.
    if (is_bool($comparison_result)) {
      return $comparison_result === FALSE
        ? $this->configuration['return_if']['false'] ?? $comparison_result
        : $this->configuration['return_if']['true'] ?? $comparison_result;
    }

    if (!is_int($comparison_result)) {
      throw new \BadMethodCallException(
        sprintf(__METHOD__ . ": argument must be a boolean or an integer")
      );
    }

    // This must be an integer since only spaceship returns non-boolean values.
    if ($comparison_result > 0) {
      return $this->configuration['return_if']['1'] ?? $comparison_result;
    }
    if ($comparison_result < 0) {
      return $this->configuration['return_if']['-1'] ?? $comparison_result;
    }

    return $this->configuration['return_if']['0'] ?? $comparison_result;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->configuration['multiple'] ?? FALSE;
  }

}
