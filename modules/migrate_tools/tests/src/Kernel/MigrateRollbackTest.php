<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Kernel;

use Drupal\migrate_tools\MigrateExecutable;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests rolling back of imports.
 *
 * @group migrate_tools
 */
final class MigrateRollbackTest extends MigrateTestBase {

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
  public function testRollback(): void {
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

    // Import and validate vocabulary config entities were created.
    $executable = new MigrateExecutable($vocabulary_migration, $this, []);
    $executable->import();
    foreach ($vocabulary_data_rows as $row) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
      $vocabulary = Vocabulary::load($row['id']);
      $this->assertInstanceOf(VocabularyInterface::class, $vocabulary);
      $map_row = $vocabulary_id_map->getRowBySource(['id' => $row['id']]);
      $this->assertEquals($map_row['destid1'], $vocabulary->id());
    }

    // Test id list rollback.
    $rollback_executable = new MigrateExecutable($vocabulary_migration, $this, ['idlist' => 1]);
    $rollback_executable->rollback();
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
