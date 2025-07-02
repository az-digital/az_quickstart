<?php

namespace Drupal\Tests\block_field\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests block field widgets and formatters.
 *
 * @group block_field
 */
class BlockFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'block',
    'block_field',
    'block_field_test',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // BC for 10.2 and earlier, block_content.type revision schema changed from
    // integer to boolean.
    if (version_compare(\Drupal::VERSION, '10.3.0', '<')) {
      $this->strictConfigSchema = FALSE;
    }
    parent::setUp();
  }

  /**
   * Tests block field.
   */
  public function testBlockField() {
    $block_content = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'status' => 1,
    ]);
    $block_content->save();
    $assert = $this->assertSession();

    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'administer content types',
      'bypass node access',
      'administer node fields',
    ]);
    $this->drupalLogin($admin_user);

    // Create block field test using the three test blocks.
    // Check that add more and ajax callbacks are working as expected.
    $this->drupalGet('node/add/block_field_test');
    $this->submitForm([
      'title[0][value]' => 'Block field test',
    ], 'Add another item');
    $this->submitForm([], 'Add another item');
    $this->submitForm([
      'field_block_field_test[0][plugin_id]' => 'block_field_test_authenticated',
      'field_block_field_test[1][plugin_id]' => 'block_field_test_content',
      'field_block_field_test[2][plugin_id]' => 'block_field_test_time',
    ], 'Add another item');
    $this->submitForm([
      'field_block_field_test[0][plugin_id]' => 'block_field_test_authenticated',
      'field_block_field_test[1][plugin_id]' => 'block_field_test_content',
      'field_block_field_test[2][plugin_id]' => 'block_field_test_time',
      'field_block_field_test[3][plugin_id]' => 'block_content:' . $block_content->uuid(),
    ], 'Add another item');
    $this->submitForm([], 'Save');



    // Check blocks displayed to authenticated.
    $node = $this->drupalGetNodeByTitle('Block field test');

    // Edit and resave as a workaround, as Drupal 10.2+ no longer triggers a
    // rebuild when adding another item and the function test doesn't trigger
    // the select ajax callback.
    // @todo Change the widget to add a non-js fallback button instead.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitForm([], 'Save');
    $this->drupalGet($node->toUrl());

    $selector = '.field--name-field-block-field-test';
    $assert->elementExists('css', $selector);
    $assert->elementContains('css', $selector, '<div class="field__label">Block field test</div>');
    $assert->elementContains('css', $selector, '<h2>You are logged in as...</h2>');
    $assert->elementTextContains('css', $selector, $admin_user->label());
    $assert->elementContains('css', $selector, '<h2>Block field test content</h2>');
    $assert->elementContains('css', $selector, '<div class="block-field-test-content-block--custom-class ');
    $assert->elementContains('css', $selector, ' data-custom-attr="block-field-test-content-block--custom-data-attribute"');
    $assert->elementTextContains('css', $selector, 'This block was created at');
    $assert->elementContains('css', $selector, '<h2>The time is...</h2>');
    $assert->responseMatches('/\d\d:\d\d:\d\d/');
    $assert->elementTextContains('css', $selector, $block_content->label());

    // Check that a referenced block that is not published is not visible.
    $block_content->status = 0;
    $block_content->save();
    $this->drupalGet($node->toUrl());
    $assert->elementTextNotContains('css', $selector, $block_content->label());

    // Check adjusting block weights maintains plugin settings.
    $this->drupalGet($node->toUrl('edit-form'));
    // Switch the position of block 1 and 2.
    $this->submitForm([
      'field_block_field_test[0][_weight]' => 1,
      'field_block_field_test[1][_weight]' => 0,
    ], 'Save');
    $this->drupalGet($node->toUrl('edit-form'));
    // Plugin id and label should be switched.
    $assert->fieldValueEquals('field_block_field_test[0][plugin_id]', 'block_field_test_content');
    $assert->fieldValueEquals('field_block_field_test[0][settings][label]', 'Block field test content');
    $assert->fieldValueEquals('field_block_field_test[1][plugin_id]', 'block_field_test_authenticated');
    $assert->fieldValueEquals('field_block_field_test[1][settings][label]', 'You are logged in as...');

    // Create a block_field_test node.
    $block_node = $this->drupalCreateNode([
      'type' => 'block_field_test',
    ]);

    // Check authenticated block.
    $block_node->field_block_field_test->plugin_id = 'block_field_test_authenticated';
    $block_node->field_block_field_test->settings = [
      'label' => 'Authenticated',
      'label_display' => TRUE,
    ];
    $block_node->save();
    $this->drupalGet($block_node->toUrl());
    $assert->elementContains('css', $selector, '<h2>Authenticated</h2>');
    $assert->elementTextContains('css', $selector, $admin_user->label());

    // Check block_field_test_authenticated cache dependency is respected when
    // the user's name is updated.
    $admin_user->setUsername('admin_user');
    $admin_user->save();
    $this->drupalGet($block_node->toUrl());
    $assert->elementContains('css', $selector, '<h2>Authenticated</h2>');
    $assert->elementTextContains('css', $selector, 'admin_user');

    // Check authenticated block is not visible to anonymous users.
    $this->drupalLogout();
    $this->drupalGet($block_node->toUrl());
    $assert->elementNotExists('css', $selector);

    // Check custom block.
    $block_node->field_block_field_test->plugin_id = 'block_content:' . $block_content->uuid();
    $block_node->field_block_field_test->settings = [
      'label' => $block_content->label(),
      'label_display' => TRUE,
    ];
    $block_node->save();
    $this->drupalGet($block_node->toUrl());

    // When the block is not published anonymous users can't see it.
    $assert->elementNotExists('css', $selector);

    // Check the cache metadata of the referenced block is propagated by
    // publishing it and checking if it is visible for anonymous users.
    $block_content->status = 1;
    $block_content->save();
    $this->drupalGet($block_node->toUrl());
    $assert->elementTextContains('css', $selector, $block_content->label());

    // Check content block.
    $block_node->field_block_field_test->plugin_id = 'block_field_test_content';
    $block_node->field_block_field_test->settings = [
      'label' => 'Hello',
      'label_display' => TRUE,
      'content' => '<p>World</p>',
    ];
    $block_node->save();

    $this->drupalGet($block_node->toUrl());
    $assert->elementContains('css', $selector, '<h2>Hello</h2>');
    $assert->elementContains('css', $selector, '<p>World</p>');

    // ISSUE: Drupal's page cache it not respecting the time block max age,
    // so we need to log in to bypass page caching.
    $this->drupalLogin($admin_user);

    // Check time block.
    $block_node->field_block_field_test->plugin_id = 'block_field_test_time';
    $block_node->field_block_field_test->settings = [
      'label' => 'Time',
      'label_display' => TRUE,
    ];
    $block_node->save();

    // Check that time is set.
    $this->drupalGet($block_node->toUrl());
    $assert->responseMatches('/\d\d:\d\d:\d\d \(\d+\)/');

    // Get the current time.
    preg_match('/\d\d:\d\d:\d\d \(\d+\)/', $this->getSession()->getPage()->getContent(), $match);
    $time = $match[0];
    $assert->responseContains($time);

    // Have delay test one second so that the time is updated.
    sleep(1);

    // Check that time is never cached by reloading the page.
    $this->drupalGet($block_node->toUrl());
    $assert->responseMatches('/\d\d:\d\d:\d\d \(\d+\)/');
    $assert->responseNotContains($time);

    // Use the Block Field Label formatter for the field_block_field_test
    // display.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'block_field_test', 'default')
      ->setComponent('field_block_field_test', [
        'type' => 'block_field_label',
      ])
      ->save();

    // Assert only the label is shown.
    $this->drupalGet($block_node->toUrl());
    $assert->responseNotMatches('/\d\d:\d\d:\d\d \(\d+\)/');
    $assert->elementContains('css', $selector, 'Time');

    $this->drupalGet('admin/structure/types/manage/block_field_test/fields/node.block_field_test.field_block_field_test');
    $this->submitForm(['settings[selection_settings][plugin_ids][page_title_block]' => FALSE], 'Save settings');

    $this->drupalGet('admin/structure/types/manage/block_field_test/fields/node.block_field_test.field_block_field_test');
    $assert->statusCodeEquals(200);
  }


  /**
   * Test the block "Page Title".
   */
  public function testBlockPageTitle() {
    $assert = $this->assertSession();

    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'administer content types',
      'bypass node access',
      'administer node fields',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('node/add/block_field_test');
    $this->submitForm([
      'title[0][value]' => 'Block field test',
      'field_block_field_test[0][plugin_id]' => 'page_title_block',
    ], 'Save');

    $node = $this->drupalGetNodeByTitle('Block field test');
    $this->drupalGet($node->toUrl());
    $selector = '.field--name-field-block-field-test';
    $assert->elementExists('css', $selector);
    $assert->elementContains('css', $selector, '<div class="field__label">Block field test</div>');
    $assert->elementContains('css', $selector, '<h1 class="page-title">Block field test</h1>');

    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitForm([
      'title[0][value]' => 'Test Title',
    ], 'Save');
    $assert->elementContains('css', $selector, '<div class="field__label">Block field test</div>');
    $assert->elementNotContains('css', $selector, '<h1 class="page-title">Block field test</h1>');
    $assert->elementContains('css', $selector, '<h1 class="page-title">Test Title</h1>');
  }

}
