<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Extracts a specified field's value from an entity.
 *
 * This considers the source value to be an entity ID, and returns a field
 * value from that field. The type of the entity and the field name must be
 * specified. Optionally, the field property should be specified if it is not
 * the default of 'value'.
 *
 * Available configuration keys:
 * - entity_type: The entity type ID to query for.
 * - field_name: The machine name of the field to be extracted from the loaded
 *   entity.
 * - langcode: (optional) The language code of entity translation. If given, the
 *   entity translation is loaded. It can only be used with content entities.
 *
 * Example:
 * @code
 * process:
 *   field_foo:
 *     plugin: entity_value
 *     source: field_noderef/0/target_id
 *     entity_type: node
 *     langcode: es
 *     field_name: field_foo
 * @endcode
 *
 * In this example field_foo field value will be retrieved from Spanish
 * translation of the loaded node.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_value",
 * )
 */
class EntityValue extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected string $fieldName;
  protected ?string $langCodeRef;
  protected EntityStorageInterface $entityStorage;

  /**
   * Flag indicating whether there are multiple values.
   */
  protected ?bool $multiple = NULL;

  /**
   * Creates a EntityValue instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \InvalidArgumentException
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;

    if (empty($this->configuration['entity_type'])) {
      throw new \InvalidArgumentException("'entity_type' configuration must be specified for the entity_value process plugin.");
    }

    $entity_type = $this->configuration['entity_type'];
    $this->entityStorage = $this->entityTypeManager->getStorage($entity_type);

    $this->langCodeRef = $this->configuration['langcode'] ?? NULL;

    if (empty($this->configuration['field_name'])) {
      throw new \InvalidArgumentException("'field_name' configuration must be specified for migrate_plus_entity_value process plugin.");
    }

    $this->fieldName = $this->configuration['field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->multiple = is_array($value);
    if (!isset($value)) {
      return [];
    }
    $ids = $this->multiple ? $value : [$value];
    $entities = $this->loadEntities($ids);

    $langcode = $this->langCodeRef;
    $arrays = array_map(function (EntityInterface $entity) use ($langcode) {
      if ($entity instanceof ContentEntityInterface) {
        if ($langcode) {
          $entity = $entity->getTranslation($langcode);
        }
        else {
          $entity = $entity->getUntranslated();
        }
      }
      elseif ($langcode) {
        throw new MigrateException('Langcode can only be used with content entities currently.');
      }
      try {
        return $entity->get($this->fieldName)->getValue();
      }
      catch (\Exception $e) {
        // Re-throw any exception thrown by the entity system.
        throw new MigrateException("Got exception reading field value {$this->fieldName} entity with ID {$entity->id()} in migrate_plus_entity_value process plugin:" . $e->getMessage());
      }
    }, $entities);

    return $this->multiple ? array_values($arrays) : ($arrays ? reset($arrays) : []);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple(): bool {
    return $this->multiple;
  }

  /**
   * Load entities.
   *
   * @param array $ids
   *   The entity IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The entities.
   */
  protected function loadEntities(array $ids): array {
    $entities = $this->entityStorage->loadMultiple($ids);
    return $entities;
  }

}
