<?php

declare(strict_types=1);

namespace Drupal\config_inspector;

/**
 * Value object for collecting validatability of config schema types.
 *
 * @internal
 */
final class ConfigSchemaValidatability {

  /**
   * The raw config schema definitions; these are not accessible later.
   *
   * @var array
   * @see ::__construct()
   *
   * @todo Fix this in Drupal core.
   * @see https://www.drupal.org/project/drupal/issues/3324984
   */
  public static $rawConfigSchemaDefinitions = [];

  /**
   * List of constraint assignments, keyed by absolute property path and level.
   *
   * @var array
   *  - Top level: absolute property path.
   *  - Second level: "local" or "inherited"
   *  - Third level: the constraint assignments as they appear in the config
   *    schema, so keys are constraint plugin IDs, values are the constraint
   *    options.
   */
  private $constraints = [];

  /**
   * List of validatability results.
   *
   * @var bool[]
   *  - Keys: absolute property path.
   *  - Values: TRUE if validatable, FALSE if not
   */
  private $results = [];

  /**
   * Array of absolute property paths to defining types.
   *
   * @var array
   */
  public $types = [];

  /**
   * Array of absolute property paths to config object names.
   *
   * @var array
   */
  public $objects = [];

  /**
   * Constructs a new instance of the class.
   *
   * @param string $property_path
   *   The absolute property path for which to record validatability metadata.
   * @param array $constraints
   *   The constraints for this property path.
   * @param string $defining_config_schema_type
   *   The type in which this property path is originally defined (and hence
   *   where validation constraints should be added.)
   * @param string $containing_config_object
   *   The config object this is in.
   */
  public function __construct(string $property_path, array $constraints, string $defining_config_schema_type, string $containing_config_object) {
    assert(array_key_exists('local', $constraints) && array_key_exists('inherited', $constraints));
    $this->constraints[$property_path] = $constraints;
    $this->results[$property_path] = !empty($constraints['local']) || !empty($constraints['inherited']);
    $this->types[$property_path] = $defining_config_schema_type;
    assert(str_starts_with($property_path, $containing_config_object));
    $this->objects[$property_path] = $containing_config_object;
  }

  /**
   * Merges other validatability; not necessarily of same root property path.
   *
   * @param self $other
   *   Another validatability.
   *
   * @return self
   *   A new ConfigSchemaValidatability object, with the merged data.
   */
  public function add(self $other): self {
    $this->results = array_merge($this->results, $other->results);
    ksort($this->results);
    $this->constraints = array_merge($this->constraints, $other->constraints);
    ksort($this->constraints);
    $this->types = array_merge($this->types, $other->types);
    ksort($this->types);
    $this->objects = array_merge($this->objects, $other->objects);
    ksort($this->objects);
    return $this;
  }

  /**
   * Gets all unvalidatable property paths.
   *
   * @param string|null $strip_parent_property_path
   *   (optional) The parent property path to strip from the result.
   *
   * @return string[]
   *   A list of unvalidatable property paths.
   */
  public function getUnvalidatablePropertyPaths(?string $strip_parent_property_path = NULL): array {
    $unvalidatable_subset = array_filter($this->results, function (bool $is_validatable): bool {
      return !$is_validatable;
    });
    $unvalidatable_property_paths = array_keys($unvalidatable_subset);
    if ($strip_parent_property_path) {
      $unvalidatable_property_paths = str_replace($strip_parent_property_path, '', $unvalidatable_property_paths);
    }
    return $unvalidatable_property_paths;
  }

  /**
   * Computes whether the collected results show this is completely validatable.
   *
   * @return bool
   *   TRUE if completely validatable, FALSE otherwise.
   */
  public function isComplete(): bool {
    return $this->computePercentage() == 1.0;
  }

  /**
   * Computes the percentage of validatable property paths.
   *
   * @return float
   *   A percentage.
   */
  public function computePercentage(): float {
    return round(count(array_filter($this->results)) / count($this->results), 2);
  }

  /**
   * Gets the validatability of each property path.
   *
   * @return bool[]
   *   Keys: absolute property paths, values: TRUE if validatable, FALSE if not.
   */
  public function getValidatabilityPerPropertyPath(): array {
    return $this->results;
  }

  /**
   * Gets the constraints at the given property path.
   *
   * @return array
   *   - Top level: "local" or "inherited"
   *   - Second level: the constraint assignments as they appear in the config
   *     schema, so keys are constraint plugin IDs, values are the constraint
   *     options.
   */
  public function getConstraints(string $property_path): array {
    assert(array_key_exists($property_path, $this->constraints), "$property_path does not exist, available: " . print_r(array_keys($this->constraints), TRUE));
    return $this->constraints[$property_path];
  }

  /**
   * Retrieves the defining config schema type for a property path.
   *
   * @param string $property_path
   *   A property path.
   *
   * @return string
   *   The type in which this property path is originally defined (and hence
   *   where validation constraints should be added.)
   */
  public function getDefiningConfigSchemaType(string $property_path): string {
    return $this->types[$property_path];
  }

  /**
   * Computes a "validatability todo" message.
   *
   * @param string $property_path
   *   An unvalidatable property path to compute this message for.
   *
   * @return string
   *   An appropriate "todo"-style message.
   *
   * @throws \LogicException
   *   When no constraints are missing.
   */
  public function computeValidatabilityTodo(string $property_path): string {
    [
      'local' => $local_constraints,
      'inherited' => $inherited_constraints,
    ] = $this->getConstraints($property_path);
    if (!empty($local_constraints) || !empty($inherited_constraints)) {
      throw new \LogicException();
    }

    $defining_config_schema_type = $this->getDefiningConfigSchemaType($property_path);
    $here_or_there = str_starts_with($property_path, $defining_config_schema_type);
    return $here_or_there
      ? "⚠️  @todo Add validation constraints here"
      : (
      self::$rawConfigSchemaDefinitions[$defining_config_schema_type]['type'] === 'config_entity'
        ? "⚠️  @todo Add validation constraints to config entity type: $defining_config_schema_type"
        : "❌ @todo Add validation constraints to ancestor type: $defining_config_schema_type"
      );
  }

  /**
   * Finds high-impact validatability todos.
   *
   * @return array
   *   A list of high-impact todos, with the keys names of config objects and
   *   the values detailed statistics for each.
   */
  public function findHighImpactValidatabilityTodos(): array {
    $count_total = count($this->results);

    $todo_property_paths = array_diff_key($this->results, array_filter($this->results));
    $count_todos = count($todo_property_paths);

    $r = array_intersect_key($this->types, $todo_property_paths);
    $count_config_names = count(array_flip($r));

    $r2 = array_count_values($r);
    array_walk($r2, function (&$v, string $k) use ($count_total, $count_todos, $count_config_names, $r) {
      $affected_config_names = array_filter($r, fn (string $origin) => $origin === $k);
      $count_affected_config_names = count($affected_config_names);
      $v = [
        'type' => $k,
        'count-absolute-property-paths' => $v,
        'count-relative-todos' => round($v / $count_todos, 2) * 100,
        'count-relative-total' => round($v / $count_total, 2) * 100,
        'count-absolute-config-names' => $count_affected_config_names,
        'count-relative-config-names' => round($count_affected_config_names / $count_config_names),
        'affected-property-paths' => $affected_config_names,
        // @todo How many unique config objects were affected?
        // @todo How many config objects would get to 100% validatability?
      ];
    });
    usort($r2, fn (array $a, array $b) => $a['count-absolute-property-paths'] < $b['count-absolute-property-paths']);
    return $r2;
  }

  /**
   * Retrieves the validatability results per property path.
   *
   * @return array
   *   An array containing the validatability results per property path.
   */
  public function __toString(): string {
    $representation = '';

    $representation .= sprintf(
      "ℹ️ %0.2f%% validatable property paths (%d of %d property paths — this excludes property paths for base types)\n",
      100 * $this->computePercentage(),
      count(array_filter($this->results)),
      count($this->results)
    );

    $indentation_parents = [];
    foreach ($this->results as $property_path => $is_validatable) {
      $relative_property_path = $property_path;
      while (!empty($indentation_parents)) {
        if (str_starts_with($property_path, implode('.', $indentation_parents) . '.')) {
          $relative_property_path = str_replace(implode('.', $indentation_parents) . '.', '', $property_path);
          $indentation_parents[] = $relative_property_path;
          break;
        }
        else {
          array_pop($indentation_parents);
        }
      }

      $representation .= sprintf("%s%s %s\n",
        str_repeat('  ', count($indentation_parents)),
        $is_validatable ? '✅' : '❌',
        $relative_property_path,
      );

      // Prepare for the next iteration.
      if ($relative_property_path == $property_path) {
        $indentation_parents[] = $relative_property_path;
      }
    }

    return $representation;
  }

}
