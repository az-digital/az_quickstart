<?php

namespace Drupal\Tests\entity_reference_revisions\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the entity_reference_revisions diff plugin.
 *
 * @group entity_reference_revisions
 *
 * @dependencies diff
 */
class EntityReferenceRevisionsDiffTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block_content',
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'diff',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create article content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Disable visual inline diff.
    $config = $this->config('diff.settings')
      ->set('general_settings.layout_plugins.visual_inline.enabled', FALSE);
    $config->save();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'view all revisions',
      'edit any article content',
      'create article content',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Test for diff plugin of ERR.
   *
   * Tests that the diff is displayed when changes are made in an ERR field.
   */
  public function testEntityReferenceRevisionsDiff() {
    // Add an entity_reference_revisions field.
    static::fieldUIAddNewField('admin/structure/types/manage/article', 'err_field', 'err_field', 'entity_reference_revisions', [
      'settings[target_type]' => 'node',
      'cardinality' => '-1',
    ], [
      'settings[handler_settings][target_bundles][article]' => TRUE,
    ]);

    // Create first referenced node.
    $title_node_1 = 'referenced_node_1';
    $edit = [
      'title[0][value]' => $title_node_1,
      'body[0][value]' => 'body_node_1',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Create second referenced node.
    $title_node_2 = 'referenced_node_2';
    $edit = [
      'title[0][value]' => $title_node_2,
      'body[0][value]' => 'body_node_2',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Create referencing node.
    $title = 'referencing_node';
    $node = $this->drupalGetNodeByTitle($title_node_1);
    $edit = [
      'title[0][value]' => $title,
      'field_err_field[0][target_id]' => $title_node_1 . ' (' . $node->id() . ')',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');

    // Check the plugin is set.
    $this->drupalGet('admin/config/content/diff/fields');
    $this->submitForm(['fields[node__field_err_field][plugin][type]' => 'entity_reference_revisions_field_diff_builder'], 'Save');

    // Update the referenced node of the err field and create a new revision.
    $node = $this->drupalGetNodeByTitle($title);
    $referenced_node_new = $this->drupalGetNodeByTitle($title_node_2);
    $edit = [
      'field_err_field[0][target_id]' => $title_node_2 . ' (' . $referenced_node_new->id() . ')',
      'revision' => TRUE,
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Compare the revisions of the referencing node.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->submitForm([], 'Compare selected revisions');

    // Assert the field changes.
    $this->assertSession()->responseContains('class="diff-context diff-deletedline">' . $title_node_1);
    $this->assertSession()->responseContains('class="diff-context diff-addedline">' . $title_node_2);
  }

}
