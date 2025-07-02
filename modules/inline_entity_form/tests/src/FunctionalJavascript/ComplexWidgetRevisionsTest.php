<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

/**
 * IEF complex entity reference revisions tests.
 *
 * @group inline_entity_form
 */
class ComplexWidgetRevisionsTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'entity_test',
    'entity_reference_revisions',
    'inline_entity_form_test',
  ];

  /**
   * URL to add new content.
   *
   * @var string
   */
  protected $formContentAddUrl;

  /**
   * Entity form display storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $entityFormDisplayStorage;

  /**
   * Prepares environment for testing.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'administer entity_test__without_bundle content',
      'administer entity_test content',
      'administer content types',
      'create err_level_1 content',
      'edit any err_level_1 content',
      'delete any err_level_1 content',
      'create err_level_2 content',
      'edit any err_level_2 content',
      'delete any err_level_2 content',
      'create err_level_3 content',
      'edit any err_level_3 content',
      'delete any err_level_3 content',
      'view own unpublished content',
    ]);
    $this->drupalLogin($this->user);

    $this->formContentAddUrl = 'node/add/err_level_1';
    $this->entityFormDisplayStorage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
  }

  /**
   * Data provider for ::testRevisionsAtDepth.
   */
  public static function revisionsAtDepthDataProvider(): array {
    return [
      [FALSE],
      [TRUE],
    ];
  }

  /**
   * Tests saving entity reference revisions' field types at depth.
   *
   * @dataProvider revisionsAtDepthDataProvider
   */
  public function testRevisionsAtDepth(bool $inner_widget_adds_revisions) {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Get the xpath selectors for the input fields in this test.
    $top_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);

    // Get the xpath selectors for the buttons in this test.
    $first_add_new_node_button_xpath = $this->getXpathForButtonWithValue('Add new node', 1);
    $first_create_node_button_xpath = $this->getXpathForButtonWithValue('Create node', 1);
    $first_edit_button_xpath = $this->getXpathForButtonWithValue('Edit', 1);
    $first_update_button_xpath = $this->getXpathForButtonWithValue('Update node', 1);

    $level_2_display_config = $this->entityFormDisplayStorage->load('node.err_level_2.default');
    $component = $level_2_display_config->getComponent('field_level_3_items');
    $component['settings']['revision'] = $inner_widget_adds_revisions;
    $level_2_display_config->setComponent('field_level_3_items', $component);
    $level_2_display_config->save();

    $this->drupalGet($this->formContentAddUrl);

    // Open up level 2 and 3 IEF forms.
    $assert_session->elementExists('xpath', $first_add_new_node_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $nested_title_field_xpath));
    $assert_session->elementExists('xpath', $first_add_new_node_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $double_nested_title_field_xpath));

    // Fill in and save level 3 IEF form.
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)->setValue('Level 3');
    $assert_session->elementExists('xpath', $first_create_node_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $double_nested_title_field_xpath));

    // Fill in and save level 2 IEF form.
    $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue('Level 2');
    $assert_session->elementExists('xpath', $first_create_node_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $nested_title_field_xpath));

    // Save the top level entity.
    $assert_session->elementExists('xpath', $top_title_field_xpath)->setValue('Level 1');
    $page->pressButton('Save');

    // Re-edit the created node to test for revisions.
    $node = $this->drupalGetNodeByTitle('Level 1');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Open up level 2 and 3 IEF forms.
    $assert_session->elementExists('xpath', $first_edit_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $nested_title_field_xpath));
    $assert_session->elementExists('xpath', $first_edit_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $double_nested_title_field_xpath));

    // Change level 3 IEF node title.
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)->setValue('Level 3.1');
    $assert_session->elementExists('xpath', $first_update_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $double_nested_title_field_xpath));

    // Change level 2 IEF node title.
    $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue('Level 2.1');
    $assert_session->elementExists('xpath', $first_update_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $nested_title_field_xpath));

    // Save the top level entity.
    $assert_session->elementExists('xpath', $top_title_field_xpath)->setValue('Level 1.1');
    $page->pressButton('Save');

    // Assert that the entities are correctly saved.
    $assert_session->pageTextContains('Level 1.1 has been updated.');
    $assert_session->pageTextContains('Level 2.1');
    $assert_session->pageTextContains('Level 3.1');

    // Load the current revision id of the Level 2 node.
    $node_level_2 = $this->drupalGetNodeByTitle('Level 2.1');
    $node_level_2_vid = $node_level_2->getLoadedRevisionId();

    // Load the current revision id of the Level 3 node.
    $node_level_3 = $this->drupalGetNodeByTitle('Level 3.1');
    $node_level_3_vid = $node_level_3->getLoadedRevisionId();

    // Re-edit the created node to test for revisions.
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Open up level 2 and 3 IEF forms.
    $assert_session->elementExists('xpath', $first_edit_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $nested_title_field_xpath));
    $assert_session->elementExists('xpath', $first_edit_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $double_nested_title_field_xpath));

    // Fill in and save level 3 IEF form.
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)->setValue('Level 3.2');
    $assert_session->elementExists('xpath', $first_update_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $double_nested_title_field_xpath));

    // Change level 2 IEF node title.
    $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue('Level 2.2');
    $assert_session->elementExists('xpath', $first_update_button_xpath)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $nested_title_field_xpath));

    // Save the top level entity.
    $assert_session->elementExists('xpath', $top_title_field_xpath)->setValue('Level 1.2');
    $page->pressButton('Save');

    // Assert that the entities are correctly saved.
    $assert_session->pageTextContains('Level 1.2 has been updated.');
    $assert_session->pageTextContains('Level 2.2');
    $assert_session->pageTextContains('Level 3.2');

    // Clear node cache.
    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache();

    // Load the current revision id of the Level 2 node.
    $node_level_2 = $this->drupalGetNodeByTitle('Level 2.2');
    $node_level_2_vid_new = $node_level_2->getLoadedRevisionId();

    // Assert that a new revision created.
    $this->assertNotEquals($node_level_2_vid, $node_level_2_vid_new);

    // Load the current revision id of the Level 3 node.
    $node_level_3 = $this->drupalGetNodeByTitle('Level 3.2');
    $node_level_3_vid_new = $node_level_3->getLoadedRevisionId();

    // Assert that (no) new revision created.
    if ($inner_widget_adds_revisions) {
      $this->assertNotEquals($node_level_3_vid, $node_level_3_vid_new);
    }
    else {
      $this->assertEquals($node_level_3_vid, $node_level_3_vid_new);
    }
  }

  /**
   * Tests saving entity revision with test entity that has no bundle.
   */
  public function testRevisionsWithTestEntityNoBundle() {
    // Get the xpath selectors for the fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $name_field_xpath = $this->getXpathForNthInputByLabelText('Name', 1);

    // Get the xpath selectors for the buttons in this test.
    $first_add_new_no_bundle_node_button = $this->getXpathForButtonWithValue('Add new entity test without bundle', 1);
    $first_no_bundle_create_node_button = $this->getXpathForButtonWithValue('Create entity test without bundle', 1);
    $first_no_bundle_node_edit_button = $this->getXpathForButtonWithValue('Edit', 1);
    $first_no_bundle_update_node_button = $this->getXpathForButtonWithValue('Update entity test without bundle', 1);

    $this->drupalGet($this->formContentAddUrl);
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Open up test entity with no bundle IEF form.
    $assert_session->elementExists('xpath', $first_add_new_no_bundle_node_button)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $name_field_xpath));

    // Save level 2 test entity without bundle IEF form.
    $assert_session->elementExists('xpath', $name_field_xpath)->setValue('Level 2 entity without bundle');
    $assert_session->elementExists('xpath', $first_no_bundle_create_node_button)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $name_field_xpath));

    // Save the top level entity.
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Level 1');
    $page->pressButton('Save');

    // Assert that the entities are correctly saved.
    $assert_session->pageTextContains('Level 1 has been created.');
    $assert_session->pageTextContains('Level 2 entity without bundle');

    // Load the new revision id of the entity.
    $entity_no_bundle = $this->container->get('entity_type.manager')
      ->getStorage('entity_test_no_bundle')
      ->loadByProperties(['name' => 'Level 2 entity without bundle']);
    $entity = reset($entity_no_bundle);
    $entity_no_bundle_vid = $entity->getLoadedRevisionId();

    // Re-edit the created node to test for revisions.
    $node = $this->drupalGetNodeByTitle('Level 1');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Open up test entity with no bundle IEF form for editing.
    $assert_session->elementExists('xpath', $first_no_bundle_node_edit_button)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $name_field_xpath));

    // Change test entity with no bundle title.
    $assert_session->elementExists('xpath', $name_field_xpath)->setValue('Level 2.1 entity without bundle');
    $assert_session->elementExists('xpath', $first_no_bundle_update_node_button)->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $name_field_xpath));

    // Save the top level entity.
    $page->fillField('title[0][value]', 'Level 1.1');
    $page->pressButton('Save');

    // Assert that the entities are correctly saved.
    $assert_session->pageTextContains('Level 1.1 has been updated.');
    $assert_session->pageTextContains('Level 2.1 entity without bundle');

    // Reload the new revision id of the entity.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_test_no_bundle')
      ->resetCache();
    $entity_no_bundle = $this->container->get('entity_type.manager')
      ->getStorage('entity_test_no_bundle')
      ->load($entity->id());
    $entity_no_bundle_vid_new = $entity_no_bundle->getLoadedRevisionId();

    // Assert that new revision was created.
    $this->assertNotEquals($entity_no_bundle_vid, $entity_no_bundle_vid_new);
  }

}
