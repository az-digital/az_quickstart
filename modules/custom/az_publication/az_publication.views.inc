<?php

/**
 * @file
 * Provide views data tied to the az_publication module.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 *
 * Generates dynamic views data based on configuration entities.
 */
function az_publication_views_data() {
  /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
  $data = [];
  $typed_config = Drupal::service('config.typed');

  $all_definitions = $typed_config->getDefinitions();
  $all_definitions_keys = array_keys($all_definitions);
  // Small optimization: the config entities due to their prefixed nature always
  // end up in *. Other types might have this too so these are just candidates.
  $config_keys = preg_grep('/\*$/', $all_definitions_keys);
  $entity_types = \Drupal::entityTypeManager()->getDefinitions();
  // Registers an entity area handler per entity type.
  /** @var Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_info */
  foreach ($entity_types as $entity_type => $entity_info) {

    if (!$entity_info->entityClassImplements('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
      continue;
    }

    // Exclude entity types, which cannot be rendered.
    if ($entity_info->hasListBuilderClass()) {
      $label = $entity_info->getLabel();
      $data['views']['entity_' . $entity_type] = [
        'title' => t('Rendered entity - @label', ['@label' => $label]),
        'help' => t('Displays a rendered @label entity in an area.', ['@label' => $label]),
        'area' => [
          'entity_type' => $entity_type,
          'id' => 'entity',
        ],
      ];
    }

    if ($prefix = $entity_info->getConfigPrefix()) {
      $table_name = 'config_' . str_replace('.', '_', $prefix);
      $id = $entity_info->getKey('id');
      $data[$table_name]['table'] = [
        'group' => t('Config'),
        'entity type' => $entity_type,
        'entity revision' => FALSE,
      ];
      $data[$table_name]['table']['base'] = [
        'title' => $entity_info->getLabel(),
        'field' => $id,
        'query_id' => 'az_publication_entity_query',
      ];

      $data[$table_name][$id]['field'] = [
        'id' => 'standard',
        'title' => 'id',
      ];
      $data[$table_name]['operation'] = [
        'title' => t('Operations'),
        'help' => t('Operations'),
        'field' => [
          'id' => 'config_entity_operations',
        ],
      ];

      // We do not kno how many stars a given config prefix requires, for
      // example the schema for block is under block.block.*.*.
      $keys = preg_grep('/^' . str_replace('.', '\.', $prefix) . '\.\*/', $config_keys);
      // There always will be just one. @TODO: add exception?
      $key = reset($keys);
      if (isset($all_definitions[$key])) {
        $definition = $all_definitions[$key];

        if (is_array($definition)) {
          _views_az_publication_process_schema($data[$table_name], $all_definitions, $all_definitions_keys, $definition);
        }
      }
    }
  }
  return $data;
}

/**
 * Process all config definitions.
 *
 * @param array $views_config_data
 *   Views config data structure.
 * @param array $all_definitions
 *   All config definitions.
 * @param array $all_definitions_keys
 *   All config definition keys.
 * @param array $definition
 *   The current definition. Need to ensure a string isn't passed.
 * @param string $prefix
 *   A prefix.
 * @param array $history
 *   Current recursive path, as definition labels.
 */
function _views_az_publication_process_schema(array &$views_config_data, array &$all_definitions, array &$all_definitions_keys, array $definition, $prefix = '', array $history = []) {
  // Prevent endless loops.
  $hashed_def = md5(serialize($definition));
  if (in_array($hashed_def, $history)) {
    return;
  }
  $history[] = $hashed_def;

  $delimiter = $prefix ? '.' : '';
  if (isset($definition['type'])) {
    if (is_array($definition['type'])) {
      return;
    }
    $type = $definition['type'];
    // The type might be constant (for e.g. type: views_display) or might be a
    // reference, for eg. type: views.field.[plugin_id] As it is impossible to
    // know just from the metadata what the exact type will end up in the
    // latter case, iterate over every possibility (views.field.boolean,
    // views.field.counter, etc).
    if (isset($all_definitions[$type])) {
      $new_definition = $all_definitions[$type];
      if (isset($new_definition['type'])) {
        if (is_array($new_definition)) {
          _views_az_publication_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, $prefix, $history);
        }
      }
      // If the new definition type has no type then the current one is a
      // primitive type (for e.g. string). If there is a label and this is
      // neither a mapping nor a sequence then add it to Views data.
      elseif (!isset($definition['mapping']) && !isset($definition['sequence']) && isset($definition['label'])) {
        _views_az_publication_data_add($views_config_data, $definition, $prefix);
        // As there is no mapping or sequence, this prefix is done.
        return;
      }
    }
    else {
      // For field.[%parent.type].settings, find field.email.settings etc.
      // For this, create a PCRE mask replacing the [] with "any string" for eg.
      // field\..*\.settings.
      $left = substr($type, 0, strpos($type, '['));
      $right = substr($type, strpos($type, ']') + 1);
      $mask = str_replace('.', '\.', $left) . '.*' . str_replace('.', '\.', $right);
      // Now find every possible type and recurse.
      foreach (preg_grep('/^' . $mask . '/', $all_definitions_keys) as $key) {
        $new_definition = $all_definitions[$key];

        if (is_array($new_definition)) {
          _views_az_publication_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, $prefix, $history);
        }
      }
    }
  }
  // Finished with the type-related recursions, now do the specific recursions.
  if (isset($definition['mapping'])) {
    foreach ($definition['mapping'] as $k => $new_definition) {
      if (is_array($new_definition)) {
        _views_az_publication_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, "$prefix$delimiter$k", $history);
      }
    }
  }
  elseif (isset($definition['sequence'])) {
    foreach ($definition['sequence'] as $new_definition) {
      if (is_array($new_definition)) {
        _views_az_publication_process_schema($views_config_data, $all_definitions, $all_definitions_keys, $new_definition, "$prefix$delimiter*", $history);
      }
    }
  }
}

/**
 * Adds a field to the Views data based on the schema definition.
 *
 * @param array $table_data
 *   Views table data structure.
 * @param array $definition
 *   The schema definition for the field.
 * @param string $prefix
 *   The field prefix.
 */
function _views_az_publication_add_field(array &$table_data, array $definition, $prefix) {
  // Define field based on type
  $table_data[$prefix] = [
    'title' => $definition['label'],
    'help' => t('Field: @prefix', ['@prefix' => $prefix]),
    'field' => [
      'id' => 'standard', // Default, adjust as needed
    ],
  ];
}

/**
 * Adjust config data based on type.
 *
 * @param array $views_config_data
 *   Views config data structure.
 * @param array $definition
 *   The current definition.
 * @param string $prefix
 *   A prefix.
 */
function _views_az_publication_data_add(array &$views_config_data, array $definition, $prefix) {
  $title = $definition['label'];
  if (strtolower($title) == 'text') {
    $title = ucfirst($prefix);
  }
  $views_config_data[$prefix] = [
    'title' => $title,
    'help' => $prefix,
    'sort' => [
      'id' => 'standard',
    ],
  ];

  switch ($definition['type']) {
    case 'boolean':
      $views_config_data[$prefix]['field']['id'] = 'standard';
      $views_config_data[$prefix]['filter']['id'] = 'config_entity_boolean';
      $views_config_data[$prefix]['argument']['id'] = 'standard';
      break;

    case 'integer':
      $views_config_data[$prefix]['field']['id'] = 'numeric';
      $views_config_data[$prefix]['filter']['id'] = 'numeric';
      $views_config_data[$prefix]['argument']['id'] = 'numeric';
      break;

    default:
      $views_config_data[$prefix]['field']['id'] = 'standard';
      $views_config_data[$prefix]['filter']['id'] = 'config_entity_string';
      $views_config_data[$prefix]['argument']['id'] = 'standard';
      break;
  }
}


