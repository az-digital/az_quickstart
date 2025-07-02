<?php

namespace Drupal\Tests\chosen\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Chosen migration.
 *
 * @group chosen
 */
class ChosenMigrationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'node',
    'taxonomy',
    'text',
    'chosen',
    'chosen_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['comment', 'node']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('chosen'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests Chosen data and field widget migration.
   */
  public function testChosenMigration(): void {
    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_taxonomy_vocabulary',
      'd7_field',
      'd7_field_instance',
      'd7_field_instance_widget_settings',
      'd7_chosen_settings',
    ]);
    $expected_config = [
      'minimum_single' => 0,
      'minimum_multiple' => 0,
      'disable_search_threshold' => 0,
      'minimum_width' => 200,
      'jquery_selector' => 'select:visible',
      'search_contains' => TRUE,
      'disable_search' => FALSE,
      'allow_single_deselect' => TRUE,
      'placeholder_text_multiple' => 'Choose some options',
      'placeholder_text_single' => 'Choose an option',
      'no_results_text' => 'No results match',
      'disabled_themes' => [
        'bartik' => '0',
        'seven' => '0',
      ],
      'chosen_include' => 2,
      'langcode' => 'en',
    ];
    // Checks the configuration are migrated.
    $this->assertEquals($expected_config, $this->config('chosen.settings')->getRawData());
    $config_after = $this->config('core.entity_form_display.node.article.default')->getRawData();
    // Checks the field widget is changed to 'chosen_select'.
    $this->assertEquals($config_after['content']['field_car_type']['type'], 'chosen_select');
  }

}
