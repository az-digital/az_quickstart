<?php

namespace Drupal\paragraphs\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for field collection migrations.
 *
 * @MigrateField(
 *   id = "field_collection",
 *   core = {7},
 *   type_map = {
 *     "field_collection" = "entity_reference_revisions",
 *   },
 *   source_module = "field_collection",
 *   destination_module = "paragraphs",
 * )
 */
class FieldCollection extends FieldPluginBase {

  /*
   * Length of the 'field_' prefix that field collection prepends to bundles.
   */
  const FIELD_COLLECTION_PREFIX_LENGTH = 6;

  /**
   * Recursion counter.
   */
  static int $recursionCounter = 0;

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          [
            'plugin' => 'paragraphs_lookup',
            'tags' => 'Field Collection Content',
            'source' => 'value',
          ],
          [
            'plugin' => 'extract',
            'index' => ['id'],
          ],
        ],
        'target_revision_id' => [
          [
            'plugin' => 'paragraphs_lookup',
            'tags' => [
              'Field Collection Revisions Content',
              'Field Collection Content',
            ],
            'tag_ids' => [
              'Field Collection Revisions Content' => ['revision_id'],
              'Field Collection Content' => ['value'],
            ],
          ],
          [
            'plugin' => 'extract',
            'index' => ['revision_id'],
          ],
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);

    // Workaround for recursion on D11+, because getMigrationDependencies()
    // expands plugins, it will go through the deriver again, which will create
    // a stub migration again.
    if (static::$recursionCounter > 0) {
      return;
    }
    static::$recursionCounter++;


    // Add the respective field collection migration as a dependency.
    $migration_dependency = 'd7_field_collection:' . substr($field_name, static::FIELD_COLLECTION_PREFIX_LENGTH);
    $migration_rev_dependency = 'd7_field_collection_revisions:' . substr($field_name, static::FIELD_COLLECTION_PREFIX_LENGTH);
    $dependencies = $migration->getMigrationDependencies() + ['required' => []];
    $dependencies['required'] = array_unique(array_merge(array_values($dependencies['required']), [$migration_dependency]));
    $migration->set('migration_dependencies', $dependencies);

    if (strpos($migration->getDestinationPlugin()->getPluginId(), 'entity_revision:') === 0 || strpos($migration->getDestinationPlugin()->getPluginId(), 'entity_complete:') === 0) {
      $dependencies['required'] = array_unique(array_merge(array_values($dependencies['required']), [$migration_rev_dependency]));
      $migration->set('migration_dependencies', $dependencies);
    }

    static::$recursionCounter--;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $this->addViewModeProcess($migration);
    parent::alterFieldFormatterMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'field_collection_view' => 'entity_reference_revisions_entity_view',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return ['field_collection_embed' => 'entity_reference_paragraphs']
      + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'field_collection' => [
        'plugin' => 'field_collection_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'field_collection' => [
        'plugin' => 'field_collection_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * Adds process for view mode settings.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   */
  protected function addViewModeProcess(MigrationInterface $migration) {
    $view_mode = [
      'field_collection' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'field_collection',
        'process' => [
          'plugin' => 'get',
          'source' => 'formatter/settings/view_mode',
        ],
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings/view_mode', $view_mode);
  }

}
