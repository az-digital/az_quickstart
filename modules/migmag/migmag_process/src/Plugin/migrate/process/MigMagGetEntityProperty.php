<?php

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process plugin which returns the value of the specified entity's property.
 *
 * Available configuration keys:
 * - entity_type_id (required): The type ID of the entity which property should
 *   be fetched. For example: 'node', 'node_type', 'date_format'.
 *   In theory, every entity ID can be used which are annotated with
 *   "/^@\w*EntityType$/" (in core: @ConfigEntityType and @ContentEntityType).
 *   database.
 * - property (required): An entity property whose value should be returned.
 *   If the property is a FieldItemListInterface, then the return value will be
 *   array containing the array of every field item's value (which might have
 *   multiple properties). You can also specify the most fundamental
 *   EntityInterface methods: 'uuid', 'id', 'isNew', 'getEntityTypeId',
 *   'bundle', 'label', 'uriRelationships', 'getOriginalId', 'toArray',
 *   'getConfigDependencyKey', 'getConfigDependencyName' and 'getConfigTarget'.
 * - load_revision (optional, defaults to NULL):
 *   If this is set to TRUE and the given entity type is revisionable, then the
 *   entity will be loaded based on revision ID.
 * - load_translation (optional, defaults to NULL): If this is set to TRUE and
 *   the given entity type is configured to be translatable, then this plugin
 *   tries to load the entity's translation. In this case, the input value
 *   should be an array of the entity ID and its translation langcode, OR the
 *   revision ID and the translation langcode.
 *
 * The plugin returns NULL if:
 * - The specified entity cannot be loaded.
 * - The specified property (field) cannot be found in the entity.
 * - The method specified in the property config isn't allowed to be called:
 *   see static::ALLOWED_GETTERS.
 * - The value of the specified property or method is NULL.
 *
 * Examples:
 *
 * Get the UUID of a taxonomy term by calling the uuid() method:
 * @code
 * process:
 *   term_uuid:
 *     plugin: migmag_get_entity_property
 *     source: tid
 *     entity_type_id: 'taxonomy_term'
 *     property: 'uuid'
 * @endcode
 *
 * Get the title of a specific node revision:
 * @code
 * process:
 *   node_title:
 *     plugin: migmag_get_entity_property
 *     source: vid
 *     entity_type_id: 'node'
 *     property: 'label'
 *     load_revision: true
 * @endcode
 *
 * Get the title of a specific node revision's translation:
 * @code
 * process:
 *   node_rev_title:
 *     plugin: migmag_get_entity_property
 *     source:
 *       - vid
 *       - langcode
 *     entity_type_id: 'node'
 *     property: 'label'
 *     load_revision: true
 *     load_translation: true
 * @endcode
 *
 * You can combine this plugin with core's extract plugin to get the value of
 * the uri property of the first field item in the "field_link" field of a node:
 * @code
 * process:
 *   node_field_link_uri:
 *     -
 *       plugin: migmag_get_entity_property
 *       source: nid
 *       entity_type_id: 'node'
 *       property: 'field_link'
 *     -
 *       plugin: explode
 *       index:
 *         - 0
 *         - uri
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_get_entity_property"
 * )
 */
class MigMagGetEntityProperty extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * List of allowed getters like id() or uuid().
   *
   * @see \Drupal\Core\Entity\EntityInterface
   *
   * @const string[]
   */
  const ALLOWED_GETTERS = [
    'uuid',
    'id',
    'isNew',
    'getEntityTypeId',
    'bundle',
    'label',
    'uriRelationships',
    'getOriginalId',
    'toArray',
    'getConfigDependencyKey',
    'getConfigDependencyName',
    'getConfigTarget',
  ];

  /**
   * The storage of the entity (fetched from the plugin configuration).
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new MigMagGetEntityProperty instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The migration plugin's manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $storage) {
    $configuration += [
      'load_revision' => NULL,
      'load_translation' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $storage;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   If the specified entity type ID isn't available.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage($configuration['entity_type_id'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $property = $this->configuration['property'];
    $source_value = (array) $value;
    $identifier = reset($source_value);

    $entity = $this->configuration['load_revision'] && $this->entityStorage instanceof RevisionableStorageInterface
      ? $this->entityStorage->loadRevision($identifier)
      : $this->entityStorage->load($identifier);

    if (empty($entity)) {
      return NULL;
    }

    // Always check the default translation.
    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getUntranslated();
    }

    // But if we have a language code and the entity is translatable, and it has
    // a translation in the given language, then check the translation.
    $langcode = $this->configuration['load_translation'] && count($source_value) > 1
      ? next($source_value)
      : NULL;
    if (
      $langcode &&
      $entity instanceof TranslatableInterface &&
      $entity->hasTranslation($langcode)
    ) {
      $entity = $entity->getTranslation($langcode);
    }

    if (in_array($property, self::ALLOWED_GETTERS, TRUE)) {
      return call_user_func([$entity, $property]);
    }

    try {
      $property_value = $entity->get($property);
    }
    catch (\InvalidArgumentException $e) {
      // This field or property does not exist.
      return NULL;
    }
    return $property_value instanceof FieldItemListInterface
      ? $property_value->getValue()
      : $property_value;
  }

}
