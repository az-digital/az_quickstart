<?php

namespace Drupal\field_group_migrate\Plugin\migrate\source\d7;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\field_group\FieldGroupFormatterPluginManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drupal 7 field_group source.
 *
 * @MigrateSource(
 *   id = "d7_field_group",
 *   source_module = "field_group",
 *   destination_module = "field_group"
 * )
 */
class FieldGroup extends DrupalSqlBase {

  /**
   * The field group formatter plugin manager.
   *
   * @var \Drupal\field_group\FieldGroupFormatterPluginManager
   */
  protected $fieldGroupFormatterManager;

  /**
   * Constructs a new FieldGroup migrate source plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface|null $migration
   *   The current migration plugin, if available.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\field_group\FieldGroupFormatterPluginManager $field_group_formatter_manager
   *   The field group formatter plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, FieldGroupFormatterPluginManager $field_group_formatter_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
    $this->fieldGroupFormatterManager = $field_group_formatter_manager;
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
      $container->get('plugin.manager.field_group.formatters')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_group', 'f')->fields('f');
    $entity_type = $this->configuration['entity_type'] ?? NULL;
    $bundle = $this->configuration['bundle'] ?? NULL;

    if ($entity_type) {
      $query->condition('f.entity_type', $entity_type);

      if ($bundle) {
        $query->condition('f.bundle', $bundle);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $data = unserialize($row->getSourceProperty('data'), ['allowed_classes' => FALSE]);
    $format_settings = $data['format_settings'] + $data['format_settings']['instance_settings'];
    unset($format_settings['instance_settings']);
    $settings = [
      'children' => $data['children'],
      'parent_name' => $row->getSourceProperty('parent_name'),
      'weight' => $data['weight'],
      'label' => $data['label'],
      'format_settings' => $format_settings,
      'format_type' => $data['format_type'],
      'region' => 'content',
    ];
    unset($settings['format_settings']['label']);

    switch ($data['format_type']) {
      case 'div':
        $settings['format_type'] = 'html_element';
        $settings['format_settings']['element'] = 'div';
        break;

      case 'tabs':
        $settings['format_type'] = 'tabs';
        $settings['format_settings']['direction'] = 'vertical';
        break;

      case 'htabs':
        $settings['format_type'] = 'tabs';
        $settings['format_settings']['direction'] = 'horizontal';
        break;

      case 'htab':
        $settings['format_type'] = 'tab';
        break;

      case 'multipage-group':
        // @todo Check if there is a better way to deal with this format type.
        $settings['format_type'] = 'tabs';
        break;

      case 'multipage':
        // @todo Check if there is a better way to deal with this format type.
        $settings['format_type'] = 'tab';
        break;

      case 'html-element':
        $settings['format_type'] = 'html_element';
        break;
    }

    // Add default settings.
    $context = $row->getSourceProperty('mode') === 'form' ? 'form' : 'view';
    $default_settings = $this->fieldGroupFormatterManager->prepareConfiguration($settings['format_type'], $context, $settings);
    $settings['format_settings'] += $default_settings['settings'];

    // Clean up obsolete settings.
    switch ($settings['format_type']) {
      case 'tabs':
        // "Tabs" does not have a "formatter" configuration, but it might be
        // present when the source field group was "htabs".
        unset($settings['format_settings']['formatter']);
        break;
    }

    $row->setSourceProperty('settings', $settings);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('ID'),
      'identifier' => $this->t('Identifier'),
      'group_name' => $this->t('Group name'),
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
      'mode' => $this->t('View mode'),
      'parent_name' => $this->t('Parent name'),
      'region' => $this->t('Region'),
      'data' => $this->t('Data'),
    ];
    return $fields;
  }

}
