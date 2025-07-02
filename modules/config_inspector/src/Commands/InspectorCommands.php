<?php

namespace Drupal\config_inspector\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\MetadataInterface;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\config_inspector\ConfigInspectorManager;
use Drupal\config_inspector\ConfigSchemaValidatability;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Serialization\Yaml;
use Drush\Commands\DrushCommands;

/**
 * Provides commands for config inspector.
 */
class InspectorCommands extends DrushCommands {

  /**
   * The configuration inspector manager.
   *
   * @var \Drupal\config_inspector\ConfigInspectorManager
   */
  protected $inspector;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * Constructs InspectorCommands object.
   *
   * @param \Drupal\config_inspector\ConfigInspectorManager $config_inspector_manager
   *   The configuration inspector manager.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The active configuration storage.
   */
  public function __construct(ConfigInspectorManager $config_inspector_manager, StorageInterface $storage) {
    parent::__construct();
    $this->inspector = $config_inspector_manager;
    $this->activeStorage = $storage;
  }

  /**
   * Inspect config for schema errors.
   *
   * @param string $key
   *   (Optional) Configuration key.
   * @param array $options
   *   (Optional) Options array.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   List of inspections.
   *
   * @option only-error
   *   Display only errors.
   * @option detail
   *   Show details.
   * @option skip-keys
   *   Configuration keys to skip. Cannot be used together with filter-keys.
   * @option filter-keys
   *   Configuration keys to filter. Cannot be used together with skip-keys.
   * @option strict-validation
   *   Treat <100% validatability as an error.
   * @option list-constraints
   *   List validation constraints. Requires --detail.
   * @option generate-baseline
   *   Generate a baseline file. Requires --only-error.
   * @option baseline
   *  Filter errors based on a baseline file. Requires --only-error.
   *
   * @usage drush config:inspect
   *   Inspect whole config for schema errors.
   * @usage drush config:inspect --only-error
   *   Inspect whole config for schema errors, but do not show valid config.
   * @usage drush config:inspect --detail
   *   Inspect whole config for schema errors but details errors.
   * @usage drush config:inspect --only-error --detail
   *   Inspect whole config for schema errors and display only errors if any.
   * @usage drush config:inspect --only-error --strict-validation
   *   Inspect whole config for schema errors and incomplete validatability.
   * @usage drush config:inspect --only-error --strict-validation --filter-keys=media.settings,system.theme.global
   *   Inspect only media.settings and system.theme.global config for schema and
   *   validatability errors.
   *
   * @field-labels
   *   key: Key
   *   status: Status
   *   validatability: Validatable
   *   data: Data
   *   constraints: Validation constraints
   * @default-fields key,status,validatability,data,constraints
   * @metadata-template <comment> Legend for Data:</comment> {legend}
   *
   * @command config:inspect
   * @aliases inspect_config
   */
  public function inspect(
    $key = '',
    array $options = [
      'only-error' => FALSE,
      'detail' => FALSE,
      'skip-keys' => self::OPT,
      'filter-keys' => self::OPT,
      'strict-validation' => FALSE,
      'list-constraints' => FALSE,
      'todo' => self::OPT,
      'statistics' => FALSE,
      'generate-baseline' => FALSE,
      'baseline' => '',
    ],
  ) {
    if ($options['skip-keys'] && $options['filter-keys']) {
      throw new \Exception('Cannot use both --skip-keys and --filter-keys. Use either or neither, not both.');
    }
    if ($options['list-constraints'] && !$options['detail']) {
      throw new \Exception('Cannot use --list-constraints without --detail.');
    }
    if ($options['generate-baseline'] && !$options['only-error']) {
      throw new \Exception('Cannot use --generate-baseline without --only-error.');
    }
    if ($options['baseline'] && !$options['only-error']) {
      throw new \Exception('Cannot use --baseline without --only-error.');
    }
    if ($options['todo'] && $options['detail']) {
      throw new \Exception('Cannot use --todo --detail.');
    }

    if (!$options['statistics']) {
      $this->say("🤖 Analyzing…\n");
    }

    $rows = [];
    $exitCode = self::EXIT_SUCCESS;
    $keys = empty($key) ? $this->activeStorage->listAll() : [$key];
    $onlyError = $options['only-error'];
    $detail = $options['detail'];
    $strict_validation = $options['strict-validation'];
    $skipKeys = !isset($options['skip-keys']) ? [] : array_fill_keys(explode(',', $options['skip-keys']), '1');
    $filterKeys = !isset($options['filter-keys']) ? [] : array_fill_keys(explode(',', $options['filter-keys']), '1');
    $listConstraints = $options['list-constraints'];

    $total_raw_validatability = NULL;

    // If --baseline option is set, skip keys that are in the baseline file.
    if ($options['baseline']) {
      $baseline = json_decode(file_get_contents($options['baseline']), TRUE);
      $skipKeys = array_merge($skipKeys, array_fill_keys($baseline, '1'));
    }

    foreach ($keys as $name) {
      if (isset($skipKeys[$name])) {
        continue;
      }
      if (!empty($filterKeys) && !isset($filterKeys[$name])) {
        continue;
      }
      $has_schema = $this->inspector->hasSchema($name);
      if (!$has_schema) {
        $status = dt('No schema');
        $validatability = NULL;
        $data = NULL;
      }
      else {
        $result = $this->inspector->checkValues($name);
        $has_valid_schema = !is_array($result);
        // 1. Schema status.
        if (!$has_valid_schema) {
          $exitCode = self::EXIT_FAILURE;
          if ($detail) {
            foreach ($result as $key => $error) {
              $rows[$key] = [
                'key' => $key,
                'status' => $error,
                'validatability' => NULL,
                'data' => NULL,
              ];
            }
            // Require the schema to be fixed before checking validatability.
            continue;
          }
          else {
            $status = dt('@count errors', ['@count' => count($result)]);
            $validatability = NULL;
            $data = NULL;
            if ($options['statistics']) {
              $this->yell(sprintf("%d errors in %s:\n%s",
                count($result),
                $name,
                implode("\n", array_map(fn ($k, $v) => "[$k] $v", array_keys($result), array_values($result)))
              ), 80, 'red');
              // It's still possible to analyze config validatability even when
              // encountering config not complying with config schema.
              $exitCode = self::EXIT_SUCCESS;
            }
          }
        }
        else {
          $status = dt('Correct');
          $validatability_detail = [];
          $data_detail = [];
          $all_property_paths = [];

          // 2. Schema validatability.
          $raw_validatability = $this->inspector->checkValidatabilityValues($name);
          $all_property_paths = array_keys($raw_validatability->getValidatabilityPerPropertyPath());
          if ($detail) {
            foreach ($raw_validatability->getValidatabilityPerPropertyPath() as $property_path => $is_validatable) {
              $relative_property_path = self::getRelativePropertyPath($name, $property_path);
              $key = "$name:$relative_property_path";
              $validatability_detail[$key] = $is_validatable;
            }
          }
          // Continue to validating the data: even with incomplete
          // validatability that is valuable to check.
          $validatability = dt('@validatability%', ['@validatability' => intval($raw_validatability->computePercentage() * 100)]);

          // 3. Schema validation constraint violations.
          $raw_violations = $this->inspector->validateValues($name);
          $has_valid_data = $raw_violations->count() === 0;
          if ($detail) {
            $violations = ConfigInspectorManager::violationsToArray($raw_violations);
            foreach ($all_property_paths as $property_path) {
              $relative_property_path = self::getRelativePropertyPath($name, $property_path);
              $key = "$name:$relative_property_path";
              $data_detail[$key] = !isset($violations[$property_path]) ? TRUE : $violations[$property_path];
            }
          }
          $data = $has_valid_data
            ? $raw_validatability->isComplete() ? '✅✅' : '✅❓'
            : dt('@count errors', ['@count' => $raw_violations->count()]);
        }
      }

      // Respect --only-error (failure on any of the 3 is considered an error).
      if ($onlyError && $has_schema && ($has_valid_schema && $has_valid_data && (!$strict_validation || $raw_validatability->isComplete()))) {
        continue;
      }
      $rows[$name] = [
        'key' => $name,
        'status' => $status,
        'validatability' => $validatability,
        'data' => $data,
      ];
      if ($listConstraints) {
        // @todo Remove once <= 10.0.x support is dropped.
        $property_path = $name;
        if (version_compare(\Drupal::VERSION, '10.1.0', 'lt')) {
          $property_path = '';
        }
        $rows[$name]['constraints'] = implode("\n", self::getPrintableConstraints($raw_validatability, $property_path));
      }

      // Show a detailed view if requested.
      if ($detail) {
        foreach ($all_property_paths as $property_path) {
          $relative_property_path = self::getRelativePropertyPath($name, $property_path);
          $key = "$name:$relative_property_path";

          // Again respect --only-error:
          if ($onlyError
            // - only show keys whose data is invalid
            && $data_detail[$key] === TRUE
            // - or, if --strict-validation is specified, also show keys whose
            // data is not validatable.
            && (!$strict_validation || $validatability_detail[$key] === TRUE)
          ) {
            continue;
          }

          $rows[$key] = [
            'key' => " $key",
            'status' => $status,
            'validatability' => $validatability_detail[$key] ? dt('Validatable') : dt('NOT'),
            'data' => $validatability_detail[$key]
              ? $data_detail[$key] === TRUE ? '✅✅' : $data_detail[$key]
              : '✅❓',
          ];

          if ($listConstraints) {
            // @todo Remove once <= 10.0.x support is dropped.
            if (version_compare(\Drupal::VERSION, '10.1.0', 'lt')) {
              $property_path = str_replace("$name.", '', $property_path);
            }
            $rows[$key]['constraints'] = implode("\n", self::getPrintableConstraints($raw_validatability, $property_path));
          }
        }
      }

      // Keep all raw validatability when doing a system-wide analysis.
      if ($options['todo'] || $options['statistics']) {
        if (!isset($total_raw_validatability)) {
          $total_raw_validatability = $raw_validatability;
        }
        else {
          $total_raw_validatability->add($raw_validatability);
        }
      }
    }

    // If --generate-baseline option is set, generate a baseline file.
    if ($options['generate-baseline']) {
      $baselineFilePath = getcwd() . DIRECTORY_SEPARATOR . 'config_inspector-baseline.json';
      file_put_contents($baselineFilePath, json_encode(array_keys($rows)));
      $this->say('📝 Baseline file generated at ' . $baselineFilePath . PHP_EOL);
    }

    if ($options['todo']) {
      // Default to 15 @todos of each type, but allow specifying a count.
      $low_count = $high_count = $options['todo'] === TRUE ? 15 : intval($options['todo']);
      // Keep only rows with <100% validatability.
      $rows = array_filter($rows, fn (array $row) => intval($row['validatability']) < 100);
      // Sort from highest to lowest validatability.
      uasort($rows, fn (array $a, array $b) => intval($b['validatability']) - intval($a['validatability']));
      $rows = array_slice($rows, 0, $low_count);

      // Also find those with the biggest impact on total validatability.
      $this->writeln('🍓🍓🍓🍓🍓🍓🍓🍓🍓🍓🍓🍓');
      $this->writeln('🍓 Low-hanging fruit 🍇');
      $this->writeln('🍇🍇🍇🍇🍇🍇🍇🍇🍇🍇🍇🍇');
      $this->writeln(" ↪ The $low_count unvalidatable config OBJECTS closest to 100% validatability:\n");
      $index = 0;
      foreach ($rows as $row) {
        $index++;
        $this->writeln(sprintf("%3d. %s: %s", $index, $row['validatability'], $row['key']));
      }
      $this->writeln('');
      $this->say('👩🏻‍💻 See details:');
      $this->say('drush config:inspect --filter-keys=[ONE OF THE ABOVE] --detail --list-constraints');
      $this->writeln("\n");

      $this->writeln('🍎🍎🍎🍎🍎🍎🍎🍎🍎🍎🍎🍎');
      $this->writeln('🍎 High-hanging fruit 🍏');
      $this->writeln('🍏🍏🍏🍏🍏🍏🍏🍏🍏🍏🍏🍏');
      $this->writeln(" ↪ The $high_count unvalidatable config TYPES with the biggest impact:\n");

      $high_impact_todos = $total_raw_validatability->findHighImpactValidatabilityTodos();
      $high_impact_todos = array_slice($high_impact_todos, 0, $high_count);
      $longest_type_name = max(array_map(
        fn (string $s) => strlen($s),
        array_column($high_impact_todos, 'type')
      ));
      for ($i = 0; $i < $high_count; $i++) {
        $index = $i + 1;
        $this->writeln(sprintf("%3d. %2d%%: %-{$longest_type_name}s affects %3d property paths, %2d%% of @todos or %2d%% of total",
          $index,
          $high_impact_todos[$i]['count-relative-todos'],
          $high_impact_todos[$i]['type'],
          $high_impact_todos[$i]['count-absolute-property-paths'],
          $high_impact_todos[$i]['count-relative-todos'],
          $high_impact_todos[$i]['count-relative-total'],
        ));
      }
      $this->writeln('');
      $this->writeln(sprintf("(Total property paths on this Drupal site: %d)", count($total_raw_validatability->getValidatabilityPerPropertyPath())));
      $this->writeln('');
      $this->say('🧑‍💻 List all affected property paths (make sure to escape the asterisk with a backslash!)');
      $this->say('drush config:inspect --detail --list-constraints --fields=key,constraints | grep "@todo" | grep [ONE OF THE ABOVE]');

      return NULL;
    }

    if ($options['statistics']) {
      $statistics = [
        'assessment' => [
          '_description' => 'Default assessment generated from these statistics.',
        ],
        'types' => [
          '_description' => 'Config types aka config schema definitions. When encapsulated in Typed Data, these would be called data types.',
        ],
        'typesInUse' => [
          '_description' => 'Config types actually used by config objects on this site.',
        ],
        'objects' => [
          '_description' => 'Concrete config objects: either simple configuration or config entities.',
        ],
      ];

      // Alias for legibility.
      $analysis = $total_raw_validatability;
      $prop_paths = $analysis->getValidatabilityPerPropertyPath();

      // First: types.
      $t_all = array_keys($analysis::$rawConfigSchemaDefinitions);
      sort($t_all);
      $t_fully_validatable = array_values(array_filter($t_all, fn (string $t): bool => array_key_exists('FullyValidatable', $analysis::$rawConfigSchemaDefinitions[$t]['constraints'] ?? [])));
      $statistics['types'] += [
        'all' => [
          'count' => count($t_all),
          'list' => $t_all,
        ],
        'validatable' => [
          'validatable' => '@todo copy stuff from https://www.drupal.org/project/drupal/issues/3324984',
        ],
        'fullyValidatable' => [
          'count' => count($t_fully_validatable),
          'list' => $t_fully_validatable,
        ],
        'perExtension' => [
          '_description' => '@todo: if the first part of the type name (before the period) matches an extension, then that is the provider. Otherwise, "core" is the provider.',
          'all' => [],
          'validatable' => [],
        ],
      ];
      $statistics['assessment']['typesFullyValidatable'] = count($t_fully_validatable) / count($t_all);

      // Second: types in use on this Drupal site.
      $tiu_all = array_count_values($total_raw_validatability->types);
      ksort($tiu_all);
      assert(empty(array_diff(array_keys($tiu_all), $t_all)), 'Types in use must be a subset of all types.');
      $tiu_validatable_property_paths = array_intersect_key($prop_paths, array_filter($prop_paths));
      $tiu_todo_property_paths = array_diff_key($prop_paths, array_filter($prop_paths));
      $r_validatable = array_intersect_key($total_raw_validatability->types, array_filter($prop_paths));
      ksort($r_validatable);
      $tiu_validatable = array_values(array_unique($r_validatable));
      $tiu_fully_validatable = array_values(array_filter($tiu_validatable, fn (string $t): bool => array_key_exists('FullyValidatable', $analysis::$rawConfigSchemaDefinitions[$t]['constraints'] ?? [])));
      $tiu_todo = array_diff(array_keys($tiu_all), $tiu_validatable);
      $r_todo = array_diff_key($total_raw_validatability->types, array_filter($prop_paths));
      $statistics['typesInUse'] += [
        'all' => [
          'count' => count($tiu_all),
          'list' => array_keys($tiu_all),
          'propertyPathCountTotal' => array_sum($tiu_all),
          'propertyPathCountPerType' => $tiu_all,
        ],
        'validatable' => [
          'count' => count($tiu_validatable),
          'list' => $tiu_validatable,
          'propertyPathCountTotal' => array_sum(array_intersect_key($tiu_all, array_fill_keys($tiu_validatable, TRUE))),
          'propertyPathCountPerType' => array_intersect_key($tiu_all, array_fill_keys($tiu_validatable, TRUE)),
        ],
        'implicitlyFullyValidatable' => [
          // @see below
        ],
        'fullyValidatable' => [
          'count' => count($tiu_fully_validatable),
          'list' => $tiu_fully_validatable,
          'propertyPathCountTotal' => array_sum(array_intersect_key($tiu_all, array_fill_keys($tiu_fully_validatable, TRUE))),
          'propertyPathCountPerType' => array_intersect_key($tiu_all, array_fill_keys($tiu_fully_validatable, TRUE)),
        ],
        'perPropertyPath' => [
          'all' => [
            // @todo Implement support for deduplicating the same property path across all objects using this type.
            'count' => -1,
          ],
          'validatable' => [
            // @todo Implement support for deduplicating the same property path across all objects using this type.
            'count' => -1,
          ],
          'fullyValidatable' => [
            // @todo Implement support for deduplicating the same property path across all objects using this type.
            'count' => -1,
          ],
        ],
        'perExtension' => [
          '_description' => 'This is not possible to generate today because config type definitions are plugins without a provider…',
        ],
      ];
      $statistics['assessment']['typesInUse'] = count($tiu_all) / count($t_all);
      $statistics['assessment']['typesInUsePartiallyValidatable'] = count($tiu_validatable) / count($tiu_all);
      $statistics['assessment']['typesInUseFullyValidatable'] = count($tiu_fully_validatable) / count($tiu_all);
      // @todo Computing `typesInUsePropertyPathsPartiallyValidatable` requires knowing how exactly how many of the property paths in a type are actually validatable.
      $statistics['assessment']['typesInUsePropertyPathsFullyValidatable'] = array_sum(array_intersect_key($tiu_all, array_fill_keys($tiu_fully_validatable, TRUE))) / array_sum($tiu_all);

      // Third: objects.
      $o_all = array_values(array_unique($analysis->objects));
      $o_p_all = $analysis->getValidatabilityPerPropertyPath();
      $o_p_validatable = array_filter($o_p_all);
      $o_p_not_validatable = array_diff_key($o_p_all, $o_p_validatable);
      $o_validatable = array_values(array_unique(array_intersect_key($analysis->objects, $o_p_validatable)));
      $o_not_validatable = array_values(array_unique(array_intersect_key($analysis->objects, $o_p_not_validatable)));
      $o_t_all = array_intersect_key($analysis->types, array_fill_keys($o_all, TRUE));
      $o_t_validatable = array_values(array_unique(array_intersect_key($analysis->types, array_fill_keys($o_validatable, TRUE))));
      $o_implicitly_fully_validatable = array_intersect(
        $o_t_all,
        array_values(array_unique(array_intersect_key($analysis->types, array_fill_keys(array_diff($o_validatable, $o_not_validatable), TRUE))))
      );
      $o_t_implicitly_fully_validatable = array_values(array_unique($o_implicitly_fully_validatable));
      $o_t_explicitly_fully_validatable = array_intersect($o_t_implicitly_fully_validatable, $tiu_fully_validatable);
      $o_explicitly_fully_validatable = array_intersect($o_t_all, $o_t_explicitly_fully_validatable);
      $statistics['objects'] += [
        'all' => [
          'count' => count($o_all),
          'list' => $o_all,
        ],
        // Distinguish between:
        // - validatable: >=1 property path in this object has >=1 validation
        //   constraint
        // - implicitly fully validatable: every property path in this object
        //   has >=1 validation constraint
        // - explicitly fully validatable: the type of this config object
        //   (simple config or config entity) is explicitly marked as
        //   `FullyValidatable`.
        'validatable' => [
          'count' => count($o_validatable),
          'list' => $o_validatable,
        ],
        'implicitlyFullyValidatable' => [
          'count' => count($o_implicitly_fully_validatable),
          'list' => array_keys($o_implicitly_fully_validatable),
        ],
        'fullyValidatable' => [
          'count' => count($o_explicitly_fully_validatable),
          'list' => array_keys($o_explicitly_fully_validatable),
        ],
        'perPropertyPath' => [
          'all' => [
            'count' => count($o_p_all),
          ],
          'validatable' => [
            'count' => count($o_p_validatable),
          ],
          'fullyValidatable' => [
            // @todo Implement support for the `FullyValidatable` constraint.
            'count' => -1,
          ],
        ],
      ];
      $statistics['typesInUse']['implicitlyFullyValidatable'] = [
        'count' => $o_t_implicitly_fully_validatable,
        'list' => $o_t_implicitly_fully_validatable,
      ];
      $statistics['assessment']['typesInUseImplicitlyFullyValidatable'] = count($o_t_implicitly_fully_validatable) / count($tiu_all);
      $statistics['assessment']['objectPropertyPathsValidatable'] = count($o_p_validatable) / count($o_p_all);
      $statistics['assessment']['objectPropertyPathsFullyValidatable'] = array_sum(array_intersect_key($tiu_all, array_fill_keys($tiu_fully_validatable, TRUE))) / count($o_p_all);
      $statistics['assessment']['objectsImplicitlyFullyValidatable'] = count($o_implicitly_fully_validatable) / count($o_all);
      $statistics['assessment']['objectsFullyValidatable'] = count($o_explicitly_fully_validatable) / count($o_all);

      return CommandResult::dataWithExitCode(json_encode($statistics, JSON_PRETTY_PRINT), $exitCode);
    }

    // Provide a legend if the "data" field is displayed.
    if (in_array('data', explode(',', $options['fields']), TRUE)) {
      return CommandResult::dataWithExitCode(new RowsOfFieldsWithLegend($rows), $exitCode);
    }

    return CommandResult::dataWithExitCode(new RowsOfFields($rows), $exitCode);
  }

  /**
   * Computes the relative property path given an absolute one + config name.
   *
   * @param string $config_name
   *   A config name.
   * @param string $absolute_property_path
   *   An absolute property path.
   *
   * @return string
   *   A relative property path.
   */
  private static function getRelativePropertyPath(string $config_name, string $absolute_property_path): string {
    return $absolute_property_path === $config_name
      // The root.
      ? ''
      // All other property paths.
      : str_replace("$config_name.", '', $absolute_property_path);
  }

  /**
   * Maps the validation constraints for the given property path to strings.
   *
   * @param \Drupal\config_inspector\ConfigSchemaValidatability $validatability
   *   The validatability of a config schema.
   * @param string $property_path
   *   The property path for which to retrieve the constraints.
   *
   * @return string[]
   *   Printable constraints.
   */
  private static function getPrintableConstraints(ConfigSchemaValidatability $validatability, string $property_path): array {
    $all_constraints = $validatability->getConstraints($property_path);
    $local_constraints = array_map(
      fn (string $constraint_name, $constraints_options) => trim(Yaml::encode([$constraint_name => $constraints_options])),
      array_keys($all_constraints['local']),
      array_values($all_constraints['local'])
    );
    $inherited_constraints = array_map(
      fn (string $constraint_name, $constraints_options) => "↣ " . trim(Yaml::encode([$constraint_name => $constraints_options])),
      array_keys($all_constraints['inherited']),
      array_values($all_constraints['inherited'])
    );

    if (empty($local_constraints) && empty($inherited_constraints)) {
      return [$validatability->computeValidatabilityTodo($property_path)];
    }

    return array_merge($local_constraints, $inherited_constraints);
  }

}

/**
 * A RowsOfFields subclass to prefix the output with a legend.
 */
class RowsOfFieldsWithLegend extends RowsOfFields implements MetadataInterface {

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    $legend = <<<EOL

  ✅❓  → Correct primitive type, detailed validation impossible.
  ✅✅  → Correct primitive type, passed all validation constraints.
EOL;
    return ['legend' => $legend];
  }

}
