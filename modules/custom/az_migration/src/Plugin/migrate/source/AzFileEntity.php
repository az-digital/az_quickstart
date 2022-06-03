<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File Entity Item source plugin.
 *
 * Available configuration keys:
 * - type: (optional) If supplied, this will only return fields
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_item",
 *   source_module = "file_entity",
 * )
 */
class AzFileEntity extends FieldableEntity {

  /**
   * Constructs a FileEntityItem instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The current migration.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $configuration += [
      'type' => NULL,
      'scheme' => $configuration['uri_prefix'] ?? NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    [
      'type' => $type,
      'scheme' => $scheme,
    ] = $this->configuration;

    if ($scheme && (($pos = strpos($scheme, '://')) !== FALSE)) {
      $scheme = substr($scheme, 0, $pos);
    }
    $query = $this->getFileEntityBaseQuery(NULL, FALSE)
      ->fields('fm')
      ->orderBy('fm.timestamp');

    // Filter by type, if configured.
    if ($type) {
      $query->condition('fm.type', $type);
    }

    // Filter by URI prefix if specified.
    if ($scheme) {
      $query->where("{$this->getSchemeExpression()} = :scheme", [
        ':scheme' => $scheme,
      ]);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    parent::prepareQuery();

    $this->query->addTag('migrate__az_migration');
    $this->query->addTag('migrate__az_migration__file_entity');
    $this->query->addTag('migrate__az_migration__media_content');
    $this->query->addTag("migrate__az_migration__source__{$this->pluginId}");

    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'type' => $type,
      'scheme' => $scheme,
    ] = $row->getSource();

    if (!($dealer_plugin = $this->fileEntityDealerManager->createInstanceFromTypeAndScheme($type, $scheme))) {
      return FALSE;
    }

    // Get Field API field values.
    $fields = $this->getFields('file', $type);
    $file_id = $row->getSourceProperty('fid');
    foreach (array_keys($fields) as $field_name) {
      $row->setSourceProperty($field_name, $this->getFieldValues('file', $field_name, $file_id));
    }

    $row->setSourceProperty('bundle', $dealer_plugin->getDestinationMediaTypeId());
    $dealer_plugin->prepareMediaEntityRow($row, $this->getDatabase());

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Fields provided by file_admin module are only included here for developer
    // convenience so that they can be adjusted by altering the generated
    // migration plugins.
    $fields = [
      'fid' => $this->t('The file identifier'),
      'uid' => $this->t('The user identifier'),
      'filename' => $this->t('The file name'),
      'uri' => $this->t('The URI of the file'),
      'filemime' => $this->t('The file mimetype'),
      'filesize' => $this->t('The file size'),
      'status' => $this->t('The file status'),
      'timestamp' => $this->t('The time that the file was added'),
      'type' => $this->t('The file type'),
      'created' => $this->t('The created timestamp - (if file_admin module is present in Drupal 7)'),
      'published' => $this->t('The published timestamp - (if file_admin module is present in Drupal 7)'),
      'promote' => $this->t('The promoted flag - (if file_admin module is present in Drupal 7)'),
      'sticky' => $this->t('The sticky flag - (if file_admin module is present in Drupal 7)'),
      'vid' => $this->t('The vid'),
      'image_field_alt' => $this->t('The alternate text for the image (if this is a value of an image field)'),
      'image_field_text' => $this->t('The title text for the image (if this is a value of an image field)'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['fid']['alias'] = 'fm';
    return $ids;
  }

}
