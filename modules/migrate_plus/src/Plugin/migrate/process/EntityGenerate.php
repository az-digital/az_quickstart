<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin generates entities within the process plugin.
 *
 * All the configuration from the lookup plugin applies here. In its most
 * simple form, this plugin needs no configuration. If there are fields on the
 * generated entity that are required or need some value, their values can be
 * provided via values and/or default_values configuration options.
 *
 * Available configuration keys:
 * - default_values: (optional) A keyed array of default static values to be
 *   used for the generated entity.
 * - values: (optional) A keyed array of values to be used for the generated
 *   entity. It supports source and destination fields as you would normally use
 *   in a process pipeline.
 *
 * Example:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   foo: bar
 *   field_tags:
 *     plugin: entity_generate
 *     source: tags
 *     default_values:
 *       description: Default description
 *     values:
 *       field_long_description: some_source_field
 *       field_foo: '@foo'
 * @endcode
 *
 * @see \Drupal\migrate_plus\Plugin\migrate\process\EntityLookup
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate"
 * )
 */
class EntityGenerate extends EntityLookup {

  protected ?Row $row = NULL;
  protected ?MigrateExecutableInterface $migrateExecutable = NULL;
  protected ?MigratePluginManagerInterface $processPluginManager = NULL;
  protected ?Get $getProcessPlugin = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL): self {
    $instance = parent::create($container, $configuration, $pluginId, $pluginDefinition, $migration);
    $instance->processPluginManager = $container->get('plugin.manager.migrate.process');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->row = $row;
    $this->migrateExecutable = $migrate_executable;
    // Creates an entity if the lookup determines it doesn't exist.
    if (!($result = parent::transform($value, $migrate_executable, $row, $destination_property))) {
      $result = $this->generateEntity($value);
    }

    return $result;
  }

  /**
   * Generates an entity for a given value.
   *
   * @param string $value
   *   Value to use in creation of the entity.
   *
   * @return int|string
   *   The entity id of the generated entity.
   */
  protected function generateEntity($value) {
    if (!empty($value)) {
      $entity = $this->entityTypeManager
        ->getStorage($this->lookupEntityType)
        ->create($this->entity($value));
      $entity->save();

      return $entity->id();
    }
  }

  /**
   * Fabricate an entity.
   *
   * This is intended to be extended by implementing classes to provide for more
   * dynamic default values, rather than just static ones.
   *
   * @param mixed $value
   *   Primary value to use in creation of the entity.
   *
   *   Entity value array.
   */
  protected function entity($value): array {
    $entity_values = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $entity_values[$this->lookupBundleKey] = $this->lookupBundle;
    }

    // Gather any static default values for properties/fields.
    if (isset($this->configuration['default_values']) && is_array($this->configuration['default_values'])) {
      foreach ($this->configuration['default_values'] as $key => $default_value) {
        $entity_values[$key] = $default_value;
      }
    }
    // Gather any additional properties/fields.
    if (isset($this->configuration['values']) && is_array($this->configuration['values'])) {
      foreach ($this->configuration['values'] as $key => $property) {
        $source_value = $this->row->get($property);
        NestedArray::setValue($entity_values, explode(Row::PROPERTY_SEPARATOR, $key), $source_value, TRUE);
      }
    }

    return $entity_values;
  }

}
