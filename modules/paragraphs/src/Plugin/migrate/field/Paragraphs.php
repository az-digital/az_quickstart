<?php

namespace Drupal\paragraphs\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field Plugin for paragraphs migrations.
 *
 * @MigrateField(
 *   id = "paragraphs",
 *   core = {7},
 *   type_map = {
 *     "paragraphs" = "entity_reference_revisions",
 *   },
 *   source_module = "paragraphs",
 *   destination_module = "paragraphs",
 * )
 */
class Paragraphs extends FieldPluginBase {

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
            'tags' => 'Paragraphs Content',
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
              'Paragraphs Revisions Content',
              'Paragraphs Content',
            ],
            'tag_ids' => [
              'Paragraphs Revisions Content' => ['revision_id'],
              'Paragraphs Content' => ['value'],
            ],
            // D8.4 Does not like an empty source value, Even when using ids.
            'source' => 'value',
          ],
          [
            'plugin' => 'extract',
            'index' => ['revision_id'],
          ],
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);

    // Add paragraphs migration as a dependency (if this is not a paragraph
    // migration).
    // @todo: This is a great example why we should consider derive paragraph
    // migrations based on parent entity type (and bundle).
    if (!in_array('Paragraphs Content', $migration->getMigrationTags(), TRUE)) {

      // Workaround for recursion on D11+, because getMigrationDependencies()
      // expands plugins, it will go through the deriver again, which will create
      // a stub migration again.
      if (static::$recursionCounter > 0) {
        return;
      }
      static::$recursionCounter++;

      $dependencies = $migration->getMigrationDependencies() + ['required' => []];
      $dependencies['required'] = array_unique(array_merge(array_values($dependencies['required']), [
        'd7_paragraphs',
      ]));
      $migration->set('migration_dependencies', $dependencies);

      if (strpos($migration->getDestinationPlugin()->getPluginId(), 'entity_revision:') === 0 || strpos($migration->getDestinationPlugin()->getPluginId(), 'entity_complete:') === 0) {
        $dependencies['required'] = array_unique(array_merge(array_values($dependencies['required']), [
          'd7_paragraphs_revisions',
        ]));
        $migration->set('migration_dependencies', $dependencies);
      }

      static::$recursionCounter--;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldWidgetMigration(MigrationInterface $migration) {
    parent::alterFieldWidgetMigration($migration);
    $this->paragraphAlterFieldWidgetMigration($migration);
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
      'paragraphs_view' => 'entity_reference_revisions_entity_view',
    ] + parent::getFieldFormatterMap();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'paragraphs_embed' => 'entity_reference_paragraphs',
    ] + parent::getFieldWidgetMap();
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'paragraphs' => [
        'plugin' => 'paragraphs_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'paragraphs' => [
        'plugin' => 'paragraphs_field_instance_settings',
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
      'paragraphs' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'formatter/settings/view_mode',
        ],
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings/view_mode', $view_mode);
  }

  /**
   * Adds processes for paragraphs field widgets.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   */
  protected function paragraphAlterFieldWidgetMigration(MigrationInterface $migration) {
    $title = [
      'paragraphs' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/title',
        ],
      ],
    ];
    $title_plural = [
      'paragraphs' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/title_multiple',
        ],
      ],
    ];
    $edit_mode = [
      'paragraphs' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/default_edit_mode',
        ],
      ],
    ];
    $add_mode = [
      'paragraphs' => [
        'plugin' => 'paragraphs_process_on_value',
        'source_value' => 'type',
        'expected_value' => 'paragraphs',
        'process' => [
          'plugin' => 'get',
          'source' => 'settings/add_mode',
        ],
      ],
    ];

    $migration->mergeProcessOfProperty('options/settings/title', $title);
    $migration->mergeProcessOfProperty('options/settings/title_plural', $title_plural);
    $migration->mergeProcessOfProperty('options/settings/edit_mode', $edit_mode);
    $migration->mergeProcessOfProperty('options/settings/add_mode', $add_mode);
  }

}
