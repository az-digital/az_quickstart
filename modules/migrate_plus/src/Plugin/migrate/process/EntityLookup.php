<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin looks for existing entities.
 *
 * In its most simple form, this plugin needs no configuration, and determines
 * the configuration automatically. This requires the migration's process to
 * define a default value for the destination entity's bundle key, and the
 * destination field this plugin is on to be a supported type.
 *
 * Available configuration keys:
 * - entity_type: (optional) The ID of the entity type to query for.
 * - value_key: (optional) The name of the entity field on which the source
 *   value will be queried. If omitted, defaults to one of the following
 *   depending on the destination field type:
 *    - entity_reference: The entity label key.
 *    - file: The uri field.
 *    - image: The uri field.
 * - operator: (optional) The comparison operator supported by entity query:
 *   See \Drupal\Core\Entity\Query\QueryInterface::condition() for available
 *   values. Defaults to '=' for scalar values and 'IN' for arrays.
 * - bundle_key: (optional) The name of the bundle field on the entity type
 *   being queried.
 * - bundle: (optional) The value to query for the bundle - can be a string or
 *   an array.
 * - access_check: (optional) Indicates if access to the entity for this user
 *   will be checked. Default is true.
 * - ignore_case: (optional) Whether to ignore case in the query. Defaults to
 *   false, meaning the query is case-sensitive by default. Works only with
 *   strict operators: '=' and 'IN'.
 * - destination_field: (optional) If specified, and if the plugin's source
 *   value is an array, the result array's items will be themselves arrays of
 *   the form [destination_field => ENTITY_ID].
 *
 * @codingStandardsIgnoreStart
 *
 * Example usage with minimal configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: page
 *   field_tags:
 *     plugin: entity_lookup
 *     access_check: false
 *     source: tags
 * @endcode
 * In this example above, the access check is disabled.
 *
 * Example usage with full configuration:
 * @code
 *   field_tags:
 *     plugin: entity_lookup
 *     source: tags
 *     value_key: name
 *     bundle_key: vid
 *     bundle: tags
 *     entity_type: taxonomy_term
 *     ignore_case: true
 *     operator: STARTS_WITH
 * @endcode
 *
 * @codingStandardsIgnoreEnd
 *
 * @see \Drupal\Core\Entity\Query\QueryInterface::condition()
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup",
 *   handle_multiples = TRUE
 * )
 */
class EntityLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  protected ?EntityTypeManagerInterface $entityTypeManager;
  protected EntityFieldManagerInterface $entityFieldManager;
  protected MigrationInterface $migration;
  protected SelectionPluginManagerInterface $selectionPluginManager;
  protected ?string $destinationEntityType;
  protected ?string $destinationBundleKey = NULL;
  protected ?string $lookupValueKey = NULL;
  protected ?string $lookupBundleKey = NULL;
  protected $lookupBundle = NULL;
  protected ?string $lookupEntityType = NULL;
  protected ?string $destinationProperty;
  protected bool $accessCheck = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    $instance = new static(
      $configuration,
      $pluginId,
      $pluginDefinition
    );
    $instance->migration = $migration;
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->selectionPluginManager = $container->get('plugin.manager.entity_reference_selection');
    $pluginIdParts = explode(':', $instance->migration->getDestinationPlugin()->getPluginId());
    $instance->destinationEntityType = empty($pluginIdParts[1]) ? NULL : $pluginIdParts[1];
    $instance->destinationBundleKey = $instance->destinationEntityType ? $instance->entityTypeManager->getDefinition($instance->destinationEntityType)->getKey('bundle') : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If the source data is empty, return the same.
    if (empty($value)) {
      return $value;
    }

    // In case of subfields ('field_reference/target_id'), extract the field
    // name only.
    $parts = explode('/', $destination_property);
    $destination_property = reset($parts);
    $this->determineLookupProperties($destination_property);

    $this->destinationProperty = $this->configuration['destination_field'] ?? NULL;

    return $this->query($value);
  }

  /**
   * Determine the lookup properties from config or target field configuration.
   *
   * @param string $destinationProperty
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   */
  protected function determineLookupProperties(string $destinationProperty): void {
    if (isset($this->configuration['access_check'])) {
      $this->accessCheck = (bool) $this->configuration['access_check'];
    }
    if (!empty($this->configuration['value_key'])) {
      $this->lookupValueKey = $this->configuration['value_key'];
    }
    if (!empty($this->configuration['bundle_key'])) {
      $this->lookupBundleKey = $this->configuration['bundle_key'];
    }
    if (!empty($this->configuration['bundle'])) {
      $this->lookupBundle = $this->configuration['bundle'];
    }
    if (!empty($this->configuration['entity_type'])) {
      $this->lookupEntityType = $this->configuration['entity_type'];
    }

    if (empty($this->lookupValueKey) || empty($this->lookupBundleKey) || empty($this->lookupBundle) || empty($this->lookupEntityType)) {
      // See if we can introspect the lookup properties from destination field.
      if (!empty($this->migration->getProcess()[$this->destinationBundleKey][0]['default_value'])) {
        $destinationEntityBundle = $this->migration->getProcess()[$this->destinationBundleKey][0]['default_value'];
        $fieldConfig = $this->entityFieldManager->getFieldDefinitions($this->destinationEntityType, $destinationEntityBundle)[$destinationProperty]->getConfig($destinationEntityBundle);
        switch ($fieldConfig->getType()) {
          case 'entity_reference':
            if (empty($this->lookupBundle)) {
              $handlerSettings = $fieldConfig->getSetting('handler_settings');
              $bundles = array_filter((array) ($handlerSettings['target_bundles'] ?? []));
              if (count($bundles) == 1) {
                $this->lookupBundle = reset($bundles);
              }
              // This was added in 8.1.x is not supported in 8.0.x.
              elseif (!empty($handlerSettings['auto_create']) && !empty($handlerSettings['auto_create_bundle'])) {
                $this->lookupBundle = reset($handlerSettings['auto_create_bundle']);
              }
            }

            // Make an assumption that if the selection handler can target more
            // than one type of entity that we will use the first entity type.
            $fieldHandler = $fieldConfig->getSetting('handler');
            $selection = $this->selectionPluginManager->createInstance($fieldHandler);
            $this->lookupEntityType = $this->lookupEntityType ?: reset($selection->getPluginDefinition()['entity_types']);
            $this->lookupValueKey = $this->lookupValueKey ?: $this->entityTypeManager->getDefinition($this->lookupEntityType)->getKey('label');
            $this->lookupBundleKey = $this->lookupBundleKey ?: $this->entityTypeManager->getDefinition($this->lookupEntityType)->getKey('bundle');
            break;

          case 'file':
          case 'image':
            $this->lookupEntityType = 'file';
            $this->lookupValueKey = $this->lookupValueKey ?: 'uri';
            break;

          default:
            throw new MigrateException(sprintf('Destination field type %s is not a recognized reference type.', $fieldConfig->getType()));
        }
      }
    }

    // If there aren't enough lookup properties available by now, then bail.
    if (empty($this->lookupValueKey)) {
      throw new MigrateException('The entity_lookup plugin requires a value_key, none located.');
    }
    if (!empty($this->lookupBundleKey) && empty($this->lookupBundle)) {
      throw new MigrateException('The entity_lookup plugin found no bundle but destination entity requires one.');
    }
    if (empty($this->lookupEntityType)) {
      throw new MigrateException('The entity_lookup plugin requires a entity_type, none located.');
    }
  }

  /**
   * Checks for the existence of some value.
   *
   * @param mixed $value
   *   The value to query.
   *
   * @return mixed|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($value) {
    $query = $this->doGetQuery($value);
    return $this->processResults($query->execute(), $value);
  }

  private function doGetQuery($value): QueryInterface {
    $operator = !empty($this->configuration['operator']) ? $this->configuration['operator'] : '=';
    $multiple = is_array($value);

    // Apply correct operator for multiple values.
    if ($multiple && $operator === '=') {
      $operator = 'IN';
    }

    $query = $this->entityTypeManager->getStorage($this->lookupEntityType)
      ->getQuery()
      ->accessCheck($this->accessCheck)
      ->condition($this->lookupValueKey, $value, $operator);
    // Sqlite and possibly others returns data in a non-deterministic order.
    // Make it deterministic.
    if ($multiple) {
      $query->sort($this->lookupValueKey, 'DESC');
    }

    if ($this->lookupBundleKey) {
      $query->condition($this->lookupBundleKey, (array) $this->lookupBundle, 'IN');
    }
    return $query;
  }

  private function processResults($results, $original_value) {
    if (empty($results)) {
      return NULL;
    }

    // Entity queries typically are case-insensitive. Therefore, we need to
    // handle case-sensitive filtering as a post-query step. By default, it
    // filters case-insensitive. Change to true if that is not the desired
    // outcome.
    $ignoreCase = !empty($this->configuration['ignore_case']) ?: FALSE;
    $operator = !empty($this->configuration['operator']) ? $this->configuration['operator'] : '=';
    $multiple = is_array($original_value);

    // Do a case-sensitive comparison only for strict operators.
    if (!$ignoreCase && in_array($operator, ['=', 'IN'], TRUE)) {
      // Returns the entity's identifier.
      foreach ($results as $k => $identifier) {
        $entity = $this->entityTypeManager->getStorage($this->lookupEntityType)->load($identifier);
        $result_value = $entity->get($this->lookupValueKey);
        // If the value is a non-empty field, extract its first value's main
        // property (most of the time "value" but sometimes "target_id" or
        // anything declared by the field item).
        if ($result_value instanceof FieldItemList && !$result_value->isEmpty()) {
          $property = $result_value->first()->mainPropertyName();
          $result_value = $result_value->{$property};
        }

        if (($multiple && !in_array($result_value, $original_value, TRUE)) || (!$multiple && $result_value !== $original_value)) {
          unset($results[$k]);
        }
      }
    }

    if ($multiple && !empty($this->destinationProperty)) {
      array_walk($results, function (&$value): void {
        $value = [$this->destinationProperty => $value];
      });
    }

    return $multiple ? array_values($results) : reset($results);
  }

}
