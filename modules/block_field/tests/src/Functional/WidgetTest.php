<?php

namespace Drupal\Tests\block_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the block field widget.
 *
 * @group block_field
 */
class WidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'block',
    'block_field',
    'block_field_test',
    'block_field_widget_test',
    'field_ui',
  ];

  /**
   * The test block node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $blockNode;

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

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer nodes',
      'bypass node access',
    ]));

    $this->drupalGet('node/add/block_node');
    $this->submitForm([
      'title[0][value]' => 'Block field test',
      'field_block[0][plugin_id]' => 'views_block:items-block_1',
    ], 'Save');

    $this->blockNode = $this->drupalGetNodeByTitle('Block field test');
  }

  /**
   * Test block settings are stored correctly.
   */
  public function testBlockSettingsAreStoredCorrectly() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $items = $this->createDummyNodes('item', 5);

    $this->drupalGet($this->blockNode->toUrl('edit-form'));
    $assert_session->checkboxChecked('Display title');
    $assert_session->checkboxNotChecked('Override title');
    $items_per_page_element = $page->findField('Items per block');
    $this->assertNotNull($items_per_page_element);
    $this->assertEquals('none', $items_per_page_element->getValue());
    $assert_session->elementContains('named', ['field', 'Items per block'], '1 (default setting)');
    $page->selectFieldOption('Items per block', 10);
    // This view has a contextual filter to exclude the node from the URL from
    // showing up if the context is present. Initially we do not choose that
    // context when placing the block.
    $exclude_element = $page->findField('Exclude');
    $this->assertNotNull($exclude_element);
    $this->assertEmpty($exclude_element->getValue());
    // Save the node and check the view items.
    $page->pressButton('Save');
    $assert_session->pageTextContains("Block node {$this->blockNode->getTitle()} has been updated");

    $css_selector = '.views-element-container';
    foreach ($items as $item) {
      $this->assertSession()->elementContains('css', $css_selector, $item->getTitle());
    }

    // The node we are visiting shows up in the views results.
    $this->assertSession()->elementContains('css', $css_selector, 'Block field test');

    // Select the context to exclude the node from the URL and try again.
    $this->drupalGet($this->blockNode->toUrl('edit-form'));
    $page->selectFieldOption('Exclude', 'Node from URL');
    $page->pressButton('Save');
    $assert_session->pageTextContains("Block node {$this->blockNode->getTitle()} has been updated");

    foreach ($items as $item) {
      $this->assertSession()->elementContains('css', $css_selector, $item->getTitle());
    }
    // The node we are visiting does not show up anymore.
    $this->assertSession()->elementNotContains('css', $css_selector, 'Block field test');
  }

  /**
   * Test configuration form options.
   */
  public function testConfigurationFormOptions() {
    $assert = $this->assertSession();

    // Configuration form: full (the default).
    $this->drupalGet($this->blockNode->toUrl('edit-form'));
    $assert->fieldExists('field_block[0][settings][label_display]');
    $assert->fieldExists('field_block[0][settings][override][items_per_page]');
    $assert->fieldExists('field_block[0][settings][views_label_checkbox]');
    $assert->fieldExists('field_block[0][settings][views_label]');

    // Configuration form: hidden.
    $this->drupalGet('admin/structure/types/manage/block_node/form-display');
    $this->submitForm([], 'field_block_settings_edit');
    $edit = [
      'fields[field_block][settings_edit_form][settings][configuration_form]' => 'hidden',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet($this->blockNode->toUrl('edit-form'));
    $assert->fieldNotExists('field_block[0][settings][label_display]');
    $assert->fieldNotExists('field_block[0][settings][override][items_per_page]');
    $assert->fieldNotExists('field_block[0][settings][views_label_checkbox]');
    $assert->fieldNotExists('field_block[0][settings][views_label]');
  }

  /**
   * Tests that validation errors from the block form are bubbled up.
   */
  public function testBlockFieldValidation() {
    $assert = $this->assertSession();
    $this->drupalGet('node/add/block_node');
    $this->submitForm([
      'title[0][value]' => 'Block field validation test',
      'field_block[0][plugin_id]' => 'block_field_test_validation',
    ], 'Save');

    $node = $this->drupalGetNodeByTitle('Block field validation test');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->submitForm([
      'field_block[0][settings][content]' => 'error by name',
    ], 'Save');
    $assert->pageTextContains('Come ere boi!');
    $assert->elementAttributeContains('css', 'input[name="field_block[0][settings][content]"]', 'class', 'error');
    $this->submitForm([
      'field_block[0][settings][content]' => 'error by element',
    ], 'Save');
    $assert->pageTextContains('Gimmie them toez!');
    $assert->elementAttributeContains('css', 'input[name="field_block[0][settings][content]"]', 'class', 'error');
    $this->submitForm([
      'field_block[0][settings][content]' => 'something else',
    ], 'Save');
    $assert->pageTextContains('Block node Block field validation test has been updated.');
  }

  /**
   * Create dummy nodes.
   *
   * @param string $bundle
   *   The bundle type to create.
   * @param int $numberOfNodes
   *   The number of nodes to create.
   *
   * @return \Drupal\node\NodeInterface[]
   *   And array of created nodes.
   */
  private function createDummyNodes($bundle, $numberOfNodes) {
    $nodes = [];

    for ($i = 0; $i < $numberOfNodes; $i++) {
      $nodes[] = $this->createNode(['type' => $bundle]);
    }

    return $nodes;
  }

}
