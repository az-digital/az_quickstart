<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Kernel;

use Drupal\migrate_tools\MigrateExecutable;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests imports.
 *
 * @group migrate_tools
 */
final class MigrateImportTest extends MigrateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'system',
    'taxonomy',
    'text',
    'user',
    'system',
  ];

  protected $collectMessages = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('taxonomy_term');
    $this->installConfig(['taxonomy']);
  }

  /**
   * Tests rolling back configuration and content entities.
   */
  public function testImport(): void {
    // We use vocabularies to demonstrate importing and rolling back
    // configuration entities.
    $vocabulary_data_rows = [
      ['id' => '1', 'name' => 'categories', 'weight' => '2'],
      ['id' => '2', 'name' => 'tags', 'weight' => '1'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'id' => 'vocabularies',
      'migration_tags' => ['Import and rollback test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $vocabulary_data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'vid' => 'id',
        'name' => 'name',
        'weight' => 'weight',
      ],
      'destination' => ['plugin' => 'entity:taxonomy_vocabulary'],
    ];

    /** @var \Drupal\migrate\Plugin\MigrationInterface $vocabulary_migration */
    $vocabulary_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $vocabulary_id_map = $vocabulary_migration->getIdMap();

    // Test id list import.
    $executable = new MigrateExecutable($vocabulary_migration, $this, ['idlist' => 2]);
    $executable->import();

    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = Vocabulary::load(1);
    $this->assertEmpty($vocabulary);
    $map_row = $vocabulary_id_map->getRowBySource(['id' => 1]);
    $this->assertEmpty($map_row);
    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = Vocabulary::load(2);
    $this->assertInstanceOf(VocabularyInterface::class, $vocabulary);
    $map_row = $vocabulary_id_map->getRowBySource(['id' => 2]);
    $this->assertEquals($map_row['destid1'], $vocabulary->id());
  }

}
