<?php

namespace Drupal\Tests\field_group_migrate\Kernel\Migrate\d7;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\field_group_migrate\Traits\FieldGroupMigrationAssertionsTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests field group migration.
 *
 * @group field_group
 */
class MigrateFieldGroupTest extends MigrateDrupal7TestBase {

  use FieldGroupMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_group',
    'field_group_migrate',
    'comment',
    'datetime',
    'image',
    'link',
    'node',
    'taxonomy',
    'telephone',
    'text',
    'taxonomy',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/drupal7.php');

    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_taxonomy_vocabulary',
      'd7_view_modes',
      'd7_field',
      'd7_field_group',
    ]);
  }

  /**
   * Test field group migration from Drupal 7 to 8.
   */
  public function testFieldGroup() {
    $this->assertNodeArticleTeaserDisplay();
    $this->assertNodePageDefaultDisplay();
    $this->assertUserDefaultDisplay();
    // Check an entity_view_display without a field group.
    $page_teaser_display = EntityViewDisplay::load('node.page.teaser');
    $this->assertEmpty($page_teaser_display->getThirdPartySettings('field_group'));

    $this->assertNodeArticleDefaultForm();
    $this->assertNodePageDefaultForm();
    // Check an entity_form_display without a field group.
    $blog_form = EntityFormDisplay::load('node.blog.default');
    $this->assertEmpty($blog_form->getThirdPartySettings('field_group'));
  }

}
