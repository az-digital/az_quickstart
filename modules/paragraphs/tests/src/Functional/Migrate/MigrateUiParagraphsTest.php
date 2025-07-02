<?php

namespace Drupal\Tests\paragraphs\Functional\Migrate;

use Drupal\Tests\paragraphs\Traits\ParagraphsNodeMigrationAssertionsTrait;

/**
 * Tests the migration of paragraph entities.
 *
 * @group paragraphs
 *
 * @group legacy
 */
class MigrateUiParagraphsTest extends MigrateUiParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  use ParagraphsNodeMigrationAssertionsTrait;

  /**
   * Tests the result of the paragraphs migration.
   *
   * @dataProvider providerParagraphsMigrate
   */
  public function testParagraphsMigrate($node_migrate_type_classic) {
    $this->setClassicNodeMigration($node_migrate_type_classic);
    $this->assertMigrateUpgradeViaUi();
    $this->assertParagraphsMigrationResults();
    $this->assertNode8Paragraphs();
    $this->assertNode9Paragraphs();
    $this->assertIcelandicNode9Paragraphs();
  }

  /**
   * Provides data and expected results for testing paragraph migrations.
   *
   * @return bool[][]
   *   Classic node migration type.
   */
  public static function providerParagraphsMigrate() {
    return [
      ['node_migrate_type_classic' => TRUE],
      ['node_migrate_type_classic' => FALSE],
    ];
  }

}
