<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\Role;

/**
 * IEF complex field widget tests.
 *
 * @group inline_entity_form
 */
class ComplexWidgetTest extends InlineEntityFormTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'inline_entity_form_test',
    'field',
    'field_ui',
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_reference_type content',
      'create ief_test_nested1 content',
      'create ief_test_nested2 content',
      'create ief_test_nested3 content',
      'edit any ief_reference_type content',
      'delete any ief_reference_type content',
      'create ief_test_complex content',
      'edit any ief_test_complex content',
      'delete any ief_test_complex content',
      'edit any ief_test_nested1 content',
      'edit any ief_test_nested2 content',
      'edit any ief_test_nested3 content',
      'view own unpublished content',
      'administer content types',
    ]);
    $this->drupalLogin($this->user);

    $this->formContentAddUrl = 'node/add/ief_test_complex';
    $this->entityFormDisplayStorage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
  }

  /**
   * Tests if form behaves correctly when field is empty.
   */
  public function testEmptyField() {
    // Get the xpath selectors for the input fields in this test.
    $inner_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $first_name_field_xpath = $this->getXpathForNthInputByLabelText('First name', 1);
    $last_name_field_xpath = $this->getXpathForNthInputByLabelText('Last name', 1);

    // Get the xpath selectors for the fieldset labels in this test.
    $fieldset_label_all_bundles_xpath = $this->getXpathForFieldsetLabel('All bundles', 1);
    $fieldset_label_multi_xpath = $this->getXpathForFieldsetLabel('Multiple nodes', 1);

    $assert_session = $this->assertSession();

    // Don't allow addition of existing nodes.
    $this->updateSetting('allow_existing', FALSE);
    $this->drupalGet($this->formContentAddUrl);

    // Assert fieldset title on inline form exists.
    $assert_session->elementExists('xpath', $fieldset_label_all_bundles_xpath);
    $assert_session->elementExists('xpath', $fieldset_label_multi_xpath);

    // Assert title field on inline form exists.
    $assert_session->elementExists('xpath', $inner_title_field_xpath);

    // Assert first name field on inline form exists.
    $assert_session->elementExists('xpath', $first_name_field_xpath);

    // Assert last name field on inline form exists.
    $assert_session->elementExists('xpath', $last_name_field_xpath);
    $assert_session->buttonExists('Create node');

    // Allow addition of existing nodes.
    $this->updateSetting('allow_existing', TRUE);

    // Asserts 'Add new node' form elements.
    $this->drupalGet($this->formContentAddUrl);
    $multi_fieldset = $assert_session
      ->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    // Assert fieldset titles.
    $assert_session->elementExists('xpath', $fieldset_label_multi_xpath);

    // Assert title field does not appear.
    $assert_session->elementNotExists('xpath', $inner_title_field_xpath);

    // Assert first name field does not appear.
    $assert_session->elementNotExists('xpath', $first_name_field_xpath);

    // Assert last name field does not appear.
    $assert_session->elementNotExists('xpath', $last_name_field_xpath);
    $assert_session->buttonExists('Add existing node', $multi_fieldset);

    // Now submit 'Add new node' button in the 'Multiple nodes' fieldset.
    $multi_fieldset->pressButton('Add new node');

    // Assert fieldset title.
    $assert_session->elementExists('xpath', $fieldset_label_multi_xpath);

    // Assert title field on inline form exists.
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $inner_title_field_xpath));

    // Assert first name field on inline form exists.
    $assert_session->elementExists('xpath', $first_name_field_xpath);

    // Assert second name field on inline form exists.
    $assert_session->elementExists('xpath', $last_name_field_xpath);
    $assert_session->buttonExists('Create node');
    $assert_session->buttonExists('Cancel');

    // Asserts 'Add existing node' form elements.
    $this->drupalGet($this->formContentAddUrl);
    $multi_fieldset = $assert_session->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $multi_fieldset->pressButton('Add existing node');

    // Assert existing entity reference autocomplete field appears.
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $this->getXpathForAutoCompleteInput()));
    $assert_session->buttonExists('Add node');
    $assert_session->buttonExists('Cancel');
  }

  /**
   * Tests creation of entities.
   */
  public function testEntityCreation() {
    // Get the xpath selectors for the input fields in this test.
    $first_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $inner_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $first_name_field_xpath = $this->getXpathForNthInputByLabelText('First name', 1);
    $last_name_field_xpath = $this->getXpathForNthInputByLabelText('Last name', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Allow addition of existing nodes.
    $this->updateSetting('allow_existing', TRUE);

    $this->drupalGet($this->formContentAddUrl);
    $multi_fieldset = $assert_session
      ->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $multi_fieldset->pressButton('Add new node');
    $this->assertNotEmpty($create_node_button = $assert_session->waitForButton('Create node'));
    $create_node_button->press();
    $this->assertNotEmpty($assert_session->waitForElement('css', 'div[data-drupal-messages]'));
    $assert_session->pageTextContains('First name field is required.');
    $assert_session->pageTextContains('Last name field is required.');
    $assert_session->pageTextContains('Title field is required.');

    // Create ief_reference_type node in IEF.
    $this->drupalGet($this->formContentAddUrl);
    $multi_fieldset = $assert_session
      ->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $multi_fieldset->pressButton('Add new node');
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $inner_title_field_xpath));
    $assert_session->elementExists('xpath', $inner_title_field_xpath)->setValue('Some reference');
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('John');
    $assert_session->elementExists('xpath', $last_name_field_xpath)->setValue('Doe');
    $page->pressButton('Create node');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ief-row-entity'));

    // Tests if correct fields appear in the table.
    $assert_session->elementTextContains('css', '.ief-row-entity .inline-entity-form-node-label', 'Some reference');
    $assert_session->elementTextContains('css', '.ief-row-entity .inline-entity-form-node-status', 'Published');

    // Tests if edit and remove buttons appear.
    $multi_fieldset = $assert_session
      ->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $assert_session->buttonExists('Edit', $multi_fieldset);
    $assert_session->buttonExists('Remove', $multi_fieldset);

    // Test edit functionality.
    $assert_session->buttonExists('Edit', $multi_fieldset)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $inner_title_field_xpath));
    $assert_session->elementExists('xpath', $inner_title_field_xpath)->setValue('Some changed reference');
    $page->pressButton('Update node');
    $this->waitForRowByTitle('Some changed reference');

    // Tests if correct fields appear in the table.
    $assert_session->elementTextContains('css', '.ief-row-entity .inline-entity-form-node-label', 'Some changed reference');
    $assert_session->elementTextContains('css', '.ief-row-entity .inline-entity-form-node-status', 'Published');

    // Tests if edit and remove buttons appear.
    $multi_fieldset = $assert_session
      ->elementExists('css', 'fieldset[data-drupal-selector="edit-multi"]');
    $assert_session->buttonExists('Edit', $multi_fieldset);
    $assert_session->buttonExists('Remove', $multi_fieldset);

    // Make sure unrelated AJAX submit doesn't save the referenced entity.
    // @todo restore this test.
    // @see https://www.drupal.org/project/inline_entity_form/issues/3088453
    $assert_session->elementExists('xpath', $first_title_field_xpath)->setValue('Some title');
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been created.');

    // Checks values of created entities.
    $node = $this->drupalGetNodeByTitle('Some changed reference');
    $this->assertNotEmpty($node, 'Created ief_reference_type node ' . $node->label());
    $this->assertSame('John', $node->get('first_name')->value, 'First name in reference node set to John');
    $this->assertSame('Doe', $node->get('last_name')->value, 'Last name in reference node set to Doe');

    $parent_node = $this->drupalGetNodeByTitle('Some title');
    $this->assertNotEmpty($parent_node, 'Created ief_test_complex node ' . $parent_node->label());
    $this->assertSame($node->id(), $parent_node->multi->target_id, 'Reference node id set to ' . $node->id());
  }

  /**
   * Tests the entity creation with different bundles nested in each other.
   *
   * Ief_test_nested1 -> ief_test_nested2 -> ief_test_nested3
   */
  public function testNestedEntityCreationWithDifferentBundlesAjaxSubmit() {
    // Get the xpath selectors for the input fields in this test.
    $top_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);

    // Get the xpath selectors for the fieldset labels in this test.
    $top_fieldset_label_xpath = $this->getXpathForFieldsetLabel('Multiple nodes', 1);
    $nested_fieldset_label_xpath = $this->getXpathForFieldsetLabel('Multiple nodes', 2);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    foreach ([FALSE, TRUE] as $required) {
      $this->setupNestedComplexForm($required);
      $assert_session->elementExists('xpath', $top_fieldset_label_xpath);
      $assert_session->elementExists('xpath', $nested_fieldset_label_xpath);
      $required_string = ($required) ? ' required' : ' unrequired';
      $double_nested_title = 'Dream within a dream' . $required_string;
      $nested_title = 'Dream' . $required_string;
      $top_level_title = 'Reality' . $required_string;
      $this->assertNotEmpty($field = $assert_session->waitForElement('xpath', $double_nested_title_field_xpath));
      $field->setValue($double_nested_title);
      $page->pressButton('Create node 3');
      $this->waitForRowByTitle($double_nested_title);

      // Assert title of second nested node found.
      $this->assertNoNodeByTitle($double_nested_title, 'Second nested entity is not saved yet.');

      $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue($nested_title);
      $page->pressButton('Create node 2');
      $this->waitForRowByTitle($nested_title);
      $this->assertNoNodeByTitle($nested_title, 'First nested entity is not saved yet.');

      $assert_session->elementExists('xpath', $top_title_field_xpath)->setValue($top_level_title);
      $page->pressButton('Save');
      $assert_session->pageTextContains("IEF test nested 1 $top_level_title has been created.");
      $top_level_node = $this->drupalGetNodeByTitle($top_level_title);
      $this->assertNotEmpty($top_level_node);
      $nested_node = $this->drupalGetNodeByTitle($nested_title);
      $this->assertNotEmpty($nested_node);
      $double_nested_node = $this->drupalGetNodeByTitle($double_nested_title);
      $this->assertNotEmpty($double_nested_node);
      $this->checkNestedNodes($top_level_node, $nested_node, $double_nested_node);
    }
  }

  /**
   * Tests the entity creation with different bundles nested in each other.
   *
   * Ief_test_nested1 -> ief_test_nested2 -> ief_test_nested3
   */
  public function testNestedEntityCreationWithDifferentBundlesNoAjaxSubmit() {
    // Get the xpath selectors for the input fields in this test.
    $top_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);

    // Get the xpath selectors for the fieldset labels in this test.
    $top_fieldset_label_xpath = $this->getXpathForFieldsetLabel('Multiple nodes', 1);
    $nested_fieldset_label_xpath = $this->getXpathForFieldsetLabel('Multiple nodes', 2);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    foreach ([FALSE, TRUE] as $required) {
      $this->setupNestedComplexForm($required);
      $assert_session->elementExists('xpath', $top_fieldset_label_xpath);
      $assert_session->elementExists('xpath', $nested_fieldset_label_xpath);
      $required_string = ($required) ? ' required' : ' unrequired';
      $double_nested_title = 'Dream within a dream' . $required_string;
      $nested_title = 'Dream' . $required_string;
      $top_level_title = 'Reality' . $required_string;
      $assert_session->elementExists('xpath', $top_title_field_xpath)->setValue($top_level_title);
      $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue($nested_title);
      $assert_session->elementExists('xpath', $double_nested_title_field_xpath)->setValue($double_nested_title);
      $page->pressButton('Save');
      $assert_session->pageTextContains("IEF test nested 1 $top_level_title has been created.");
      $top_level_node = $this->drupalGetNodeByTitle($top_level_title);
      $this->assertNotEmpty($top_level_node);
      $nested_node = $this->drupalGetNodeByTitle($nested_title);
      $this->assertNotEmpty($nested_node);
      $double_nested_node = $this->drupalGetNodeByTitle($double_nested_title);
      $this->assertNotEmpty($double_nested_node);
      $this->checkNestedNodes($top_level_node, $nested_node, $double_nested_node);
    }
  }

  /**
   * Tests if editing and removing entities work.
   */
  public function testEntityEditingAndRemoving() {
    // Get the xpath selectors for the fields in this test.
    $inner_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $first_name_field_xpath = $this->getXpathForNthInputByLabelText('First name', 1);
    $last_name_field_xpath = $this->getXpathForNthInputByLabelText('Last name', 1);
    $first_delete_checkbox_xpath = $this->getXpathForNthInputByLabelText('Delete this node from the system.', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Allow addition of existing nodes.
    $this->updateSetting('allow_existing', TRUE);

    // Create three ief_reference_type entities.
    $referenceNodes = $this->createReferenceContent();
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'Some title',
      'multi' => array_values($referenceNodes),
    ]);

    $parent_node = $this->drupalGetNodeByTitle('Some title');

    // Edit the second entity.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->elementExists('xpath', '(//input[@value="Edit"])[2]')->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $inner_title_field_xpath));
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('John');
    $assert_session->elementExists('xpath', $last_name_field_xpath)->setValue('Doe');
    $page->pressButton('Update node');
    $this->assertNotEmpty($assert_session->waitForElementRemoved('css', 'div[data-drupal-selector="edit-multi-form-inline-entity-form-entities-1-form"]'));
    $this->waitForRowByTitle('Some reference 2');

    // Save the ief_test_complex node.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');

    // Checks values of changed entities.
    $node = $this->drupalGetNodeByTitle('Some reference 2', TRUE);
    $this->assertSame('John', $node->first_name->value, 'First name in reference node changed to John');
    $this->assertSame('Doe', $node->last_name->value, 'Last name in reference node changed to Doe');

    // Delete the second entity.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 3);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[2]')->press();
    $this->assertNotEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $first_delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove Some reference 2?');
    $confirm_checkbox->check();
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[2]')->press();
    $this->waitForRowRemovedByTitle('Some reference 2');

    // Assert two rows show, instead of 3.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);

    // Save the ief_test_complex node.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');

    $deleted_node = $this->drupalGetNodeByTitle('Some reference 2');
    $this->assertEmpty($deleted_node, 'The inline entity was deleted from the site.');

    // Checks that entity does nor appear in IEF.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    // Assert 2 rows show, instead of 3.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);
    $this->assertRowByTitle('Some reference 1');
    $this->assertNoRowByTitle('Some reference 2');
    $this->assertRowByTitle('Some reference 3');

    // Delete the third entity reference only, don't delete the node. The third
    // entity now is second referenced entity because the second one was deleted
    // in previous step.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[2]')->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $first_delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove Some reference 3?');
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[2]')->press();
    $this->waitForRowRemovedByTitle('Some reference 3');

    // Assert only one row displays.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 1);
    $this->assertRowByTitle('Some reference 1');
    $this->assertNoRowByTitle('Some reference 2');
    $this->assertNoRowByTitle('Some reference 3');

    // Save the ief_test_complex node.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');

    // Checks that entity is not deleted.
    $node = $this->drupalGetNodeByTitle('Some reference 3');
    $this->assertNotEmpty($node, 'Reference node not deleted');
  }

  /**
   * Tests if referencing existing entities work.
   */
  public function testReferencingExistingEntities() {
    // Get the xpath selectors for the input fields in this test.
    $node_field_xpath = $this->getXpathForNthInputByLabelText('Node', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Allow addition of existing nodes.
    $this->updateSetting('allow_existing', TRUE);

    // Create three ief_reference_type entities.
    $referenceNodes = $this->createReferenceContent();

    // Create a node for every bundle available.
    $bundle_nodes = $this->createNodeForEveryBundle();

    // Create ief_test_complex node with first ief_reference_type node and first
    // node from bundle nodes.
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'Some title',
      'multi' => [1],
      'all_bundles' => key($bundle_nodes),
    ]);

    // Remove first node since we already added it.
    unset($bundle_nodes[key($bundle_nodes)]);

    $parent_node = $this->drupalGetNodeByTitle('Some title', TRUE);

    // Add remaining existing reference nodes.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    for ($i = 2; $i <= 3; $i++) {
      $this->openMultiExistingForm();
      $title = 'Some reference ' . $i;
      $assert_session->elementExists('xpath', $node_field_xpath)->setValue($title . ' (' . $referenceNodes[$title] . ')');
      $page->pressButton('Add node');
      $this->waitForRowByTitle($title);
    }

    // Add all remaining nodes from all bundles.
    foreach ($bundle_nodes as $id => $title) {
      $all_bundles_fieldset = $assert_session
        ->elementExists('css', 'fieldset[data-drupal-selector="edit-all-bundles"]');
      $assert_session->buttonExists('Add existing node', $all_bundles_fieldset)->press();
      $this->assertNotEmpty($assert_session->waitForElement('xpath', $node_field_xpath));
      $assert_session->elementExists('xpath', $node_field_xpath)->setValue($title . ' (' . $id . ')');
      $page->pressButton('Add node');
      $this->waitForRowByTitle($title);
    }

    // Save the node.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');

    // Check if entities are referenced.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    $assert_session->elementsCount('css', 'fieldset[data-drupal-selector="edit-multi"] tr.ief-row-entity', 3);
    $this->assertRowByTitle('Some reference 1');
    $this->assertRowByTitle('Some reference 2');
    $this->assertRowByTitle('Some reference 3');

    // Check if all remaining nodes from all bundles are referenced.
    $assert_session->elementsCount('css', 'fieldset[data-drupal-selector="edit-all-bundles"] tr.ief-row-entity', 12);
    foreach ($bundle_nodes as $title) {
      $this->assertRowByTitle($title);
    }
  }

  /**
   * Tests if referencing an existing entity works without submitting the form.
   */
  public function testReferencingExistingEntitiesNoSubmit() {
    // Get the xpath selectors for the input fields in this test.
    $node_field_xpath = $this->getXpathForNthInputByLabelText('Node', 1);
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);

    // Allow addition of existing nodes.
    $this->updateSetting('allow_existing', TRUE);
    $title = $this->randomMachineName();

    $this->drupalCreateNode([
      'type' => 'ief_reference_type',
      'title' => $title,
      'first_name' => $this->randomMachineName(),
      'last_name' => $this->randomMachineName(),
    ]);
    $node = $this->drupalGetNodeByTitle($title);
    $this->assertNotEmpty($node, 'Created ief_reference_type node "' . $node->label() . '"');

    $this->drupalGet($this->formContentAddUrl);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->openMultiExistingForm();
    $assert_session->elementExists('xpath', $node_field_xpath)->setValue($node->getTitle() . ' (' . $node->id() . ')');
    $parent_title = $this->randomMachineName();
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue($parent_title);
    $page->pressButton('Save');
    $assert_session->pageTextContains("IEF test complex $parent_title has been created.");
    $assert_session->pageTextNotContains('This value should not be null.');
    $node = $this->drupalGetNodeByTitle($parent_title);
    $this->assertNotEmpty($node, 'Created ief_reference_type node.');
  }

  /**
   * Test if invalid values get correct validation messages.
   *
   * Tests validation in reference existing entity form.  It also checks if
   * existing entity reference form can be canceled.
   */
  public function testReferenceExistingValidation() {
    // Get the xpath selectors for the input fields in this test.
    $node_field_xpath = $this->getXpathForNthInputByLabelText('Node', 1);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->updateSetting('allow_existing', TRUE);

    $this->drupalGet('node/add/ief_test_complex');
    $this->checkExistingValidationExpectation('', 'Node field is required.');
    $this->checkExistingValidationExpectation('Fake Title', 'There are no entities matching "Fake Title"');

    // Check adding nodes that cannot be referenced by this field.
    foreach ($this->createNodeForEveryBundle() as $id => $title) {
      $node = Node::load($id);
      if ($node->bundle() !== 'ief_reference_type') {
        $this->checkExistingValidationExpectation("$title ($id)", "The referenced entity (node: $id) does not exist.");
      }
    }

    foreach ($this->createReferenceContent(2) as $title => $id) {
      $this->openMultiExistingForm();
      $current_title = "$title ($id)";
      $assert_session->elementExists('xpath', $node_field_xpath)->setValue($current_title);
      $page->pressButton('Add node');
      $this->waitForRowByTitle($title);
      $assert_session->elementNotExists('xpath', $node_field_xpath);

      // Try to add the same node again.
      $this->checkExistingValidationExpectation($current_title, 'The selected node has already been added.');
    }
  }

  /**
   * Tests if duplicating entities works.
   */
  public function testDuplicatingEntities() {
    // Get the xpath selectors for the input fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $first_name_field_xpath = $this->getXpathForNthInputByLabelText('First name', 1);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->updateSetting('allow_duplicate', TRUE);

    $referenceNodes = $this->createReferenceContent(2);
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'Some title',
      'multi' => array_values($referenceNodes),
    ]);

    $parent_node = $this->drupalGetNodeByTitle('Some title');

    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $this->assertNotEmpty($multi_fieldset = $assert_session->waitForElement('css', 'fieldset[data-drupal-selector="edit-multi"]'));
    $assert_session->buttonExists('Duplicate', $multi_fieldset)->press();
    $this->assertNotEmpty($create_node_button = $assert_session->waitForButton('Duplicate node'));
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Duplicate!');
    $assert_session->elementExists('xpath', $first_name_field_xpath)->setValue('Bojan');
    $create_node_button->press();

    $this->waitForRowByTitle('Duplicate!');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 3);
    $this->assertRowByTitle('Some reference 1');
    $this->assertRowByTitle('Some reference 2');
    $this->assertRowByTitle('Duplicate!');
    $page->pressButton('Save');
    $duplicate = $this->drupalGetNodeByTitle('Duplicate!');
    $this->assertNotEmpty($duplicate, 'Duplicate node created.');
    $this->assertSame('Bojan', $duplicate->first_name->value);
  }

  /**
   * Tests if a referenced content can be edited.
   *
   * When the referenced content is newer than the referencing parent node,
   * test if a referenced content can be edited.
   */
  public function testEditedInlineEntityValidation() {
    // Get the xpath selectors for the input fields in this test.
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->updateSetting('allow_existing', TRUE);

    // Create referenced content.
    $referenced_nodes = $this->createReferenceContent(1);

    // Create first referencing node.
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'First referencing node',
      'multi' => array_values($referenced_nodes),
    ]);
    $first_node = $this->drupalGetNodeByTitle('First referencing node');

    // Create second referencing node.
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'Second referencing node',
      'multi' => array_values($referenced_nodes),
    ]);
    $second_node = $this->drupalGetNodeByTitle('Second referencing node');

    // Edit referenced content in first node.
    $this->drupalGet('node/' . $first_node->id() . '/edit');
    $page->pressButton('Edit');
    $this->assertNotEmpty($nested_title = $assert_session->waitForElement('xpath', $nested_title_field_xpath));
    $nested_title->setValue('Some reference updated');
    $page->pressButton('Update node');
    $this->waitForRowByTitle('Some reference updated');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 1);

    // Save the first node after editing the reference.
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue('First node updated');
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex First node updated has been updated.');

    // The changed value of the referenced content is now newer than the
    // changed value of the second node.
    // Edit referenced content in second node.
    $this->drupalGet('node/' . $second_node->id() . '/edit');

    // Edit referenced node.
    $page->pressButton('Edit');
    $this->assertNotEmpty($nested_title = $assert_session->waitForElement('xpath', $nested_title_field_xpath));
    $nested_title->setValue('Some reference updated the second time');
    $page->pressButton('Update node');
    $this->waitForRowByTitle('Some reference updated the second time');
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 1);

    // Save the second node after editing the reference.
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Second node updated');
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Second node updated has been updated.');

    // Check if the referenced content could be edited.
    $assert_session->pageTextNotContains('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');
  }

  /**
   * Tests reference removal and entity deletion.
   */
  public function testRemoveReference() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // The default widget setting should be optional, we don't need to set it.
    // Create five ief_reference_type entities.
    $referenceNodes = $this->createReferenceContent(5);
    $this->drupalCreateNode([
      'type' => 'ief_test_complex',
      'title' => 'Some title',
      'multi' => array_values($referenceNodes),
    ]);
    /** @var \Drupal\node\Entity\NodeInterface $node */
    $parent_node = $this->drupalGetNodeByTitle('Some title');

    // Edit the parent node.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    $title1 = 'Some reference 1';
    $title2 = 'Some reference 2';
    $title3 = 'Some reference 3';
    $title4 = 'Some reference 4';
    $title5 = 'Some reference 5';

    // Revoke the delete any ief_reference_type entities permission from the
    // user.
    /** @var \Drupal\user\Entity\Role $user_role */
    $user_role = Role::load($this->user->getRoles()[1]);
    $user_role->revokePermission('delete any ief_reference_type content');
    $user_role->save();

    // Reloading page post updating permission to ensure session is updated.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    // Without delete permission, the checkbox to delete the entity when
    // removing the reference should not be visible.
    $delete_checkbox_xpath = $this->getXpathForNthInputByLabelText('Delete this node from the system.', 1);
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 5);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $assert_session->elementNotExists('xpath', $delete_checkbox_xpath);

    // Grant the delete any ief_reference_type entities permission to the
    // user and reload the page.
    $user_role->grantPermission('delete any ief_reference_type content');
    $user_role->save();

    // Reloading page post updating permission to ensure session is updated.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    // Delete the reference to the first entity, but keep it in the system.
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->assertNotEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove ' . $title1);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->waitForRowRemovedByTitle($title1);

    // Now unreference the second entity, also deleting it from the system.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 4);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->assertNotEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove ' . $title2);
    $confirm_checkbox->check();
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->waitForRowRemovedByTitle($title2);

    // Now save the parent node and check if both are still gone.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');

    // Change setting to 'keep always' and revoke permission again and
    // reload the page.
    $user_role->revokePermission('delete any ief_reference_type content');
    $user_role->save();

    // Reloading page post updating permission to ensure session is updated.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    $this->updateSetting('removed_reference', 'keep');
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->pageTextNotContains($title1);
    $assert_session->pageTextNotContains($title2);

    // Now unreference the third entity and check if it disappeared.
    // User has access to the Remove button because the widget is configured to
    // keep the referenced entity and only remove the reference from the node.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 3);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->assertEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove ' . $title3);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->waitForRowRemovedByTitle($title3);

    // Save the parent node again and check if the third one is still gone.
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->pageTextNotContains($title3);

    // Change setting to 'delete always' and reload page.
    $this->updateSetting('removed_reference', 'delete');
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->pageTextNotContains($title1);
    $assert_session->pageTextNotContains($title2);
    $assert_session->pageTextNotContains($title3);

    // Now assert the Remove button is not available, since the user has no
    // permission to delete and the widget is configured for deletion.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);
    $assert_session->elementNotExists('xpath', '(//input[@value="Remove"])[1]');

    // Grant the delete any ief_reference_type entities permission to the
    // user and reload the page.
    $user_role->grantPermission('delete any ief_reference_type content');
    $user_role->save();

    // Reloading page post updating permission to ensure session is updated.
    $this->drupalGet('node/' . $parent_node->id() . '/edit');

    // Now unreference the fourth entity and check if it disappeared.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 2);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->assertEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove ' . $title4);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->waitForRowRemovedByTitle($title4);

    // Save the parent node again and check if the fourth one is still gone.
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('field is required.');
    $assert_session->pageTextContains('IEF test complex Some title has been updated.');
    $this->drupalGet('node/' . $parent_node->id() . '/edit');
    $assert_session->pageTextNotContains($title4);

    // Now unreference the fifth entity and check if it disappeared.
    $assert_session->elementsCount('css', 'tr.ief-row-entity', 1);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->assertEmpty($confirm_checkbox = $assert_session->waitForElement('xpath', $delete_checkbox_xpath));
    $assert_session->pageTextContains('Are you sure you want to remove ' . $title5);
    $assert_session->elementExists('xpath', '(//input[@value="Remove"])[1]')->press();
    $this->waitForRowRemovedByTitle($title5);
    $assert_session->pageTextNotContains($title5);

    // Save the parent node again and check if deleting the fifth child entity
    // failed because the node couldn't be saved with empty required fields.
    $page->pressButton('Save');
    $assert_session->pageTextNotContains('IEF test complex Some title has been updated.');
    $assert_session->pageTextNotContains($title5);

    // Finally, check if the second and fourth child entity are completely gone
    // and the first and third one are still there as expected, while the fifth
    // one is still there as saving the parent node failed.
    $this->assertTrue($this->drupalGetNodeByTitle($title1) instanceof NodeInterface, 'First inline entity still exists.');
    $this->assertTrue(empty($this->drupalGetNodeByTitle($title2)), 'Second inline entity was deleted from the site.');
    $this->assertTrue($this->drupalGetNodeByTitle($title3) instanceof NodeInterface, 'Third inline entity still exists.');
    $this->assertTrue(empty($this->drupalGetNodeByTitle($title4)), 'Fourth inline entity was deleted from the site.');
    $this->assertTrue($this->drupalGetNodeByTitle($title5) instanceof NodeInterface, "Fifth inline entity still exists.");
  }

  /**
   * Checks that nested IEF entity references can be edited and saved.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Top level node of type ief_test_nested1 to check.
   */
  protected function checkNestedNodeEditing(NodeInterface $node) {
    // Get the xpath selectors for the input fields in this test.
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $nested_node = $node->test_ref_nested1->entity;
    $double_nested_node = $nested_node->test_ref_nested2->entity;

    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertRowByTitle($nested_node->label());
    $page->pressButton('Edit');
    $this->waitForRowByTitle($double_nested_node->label());
    $page->pressButton('Edit');
    $this->assertNotEmpty($assert_session->waitforButton('Update node 3'));
    $double_nested_node_update_title = $double_nested_node->getTitle() . ' - updated';
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)->setValue($double_nested_node_update_title);

    // Close the double nested IEF form.
    $page->pressButton('Update node 3');
    $this->waitForRowByTitle($double_nested_node_update_title);

    // Close the nested IEF form.
    $page->pressButton('Update node 2');
    $this->assertNotEmpty($assert_session->waitForElementRemoved('css', 'div[data-drupal-selector="edit-test-ref-nested1-form-inline-entity-form-entities-0-form"]'));
    $this->waitForRowByTitle($nested_node->label());

    // Save the top level node.
    $page->pressButton('Save');

    $assert_session->pageTextContains('IEF test nested 1 ' . $node->label() . ' has been updated.');

    // Verify the double nested node title change saved properly.
    $this->drupalGet("node/{$node->id()}/edit");
    $page->pressButton('Edit');
    $this->waitForRowByTitle($double_nested_node_update_title);
    $this->drupalGet("node/{$double_nested_node->id()}/edit");
    $this->assertSame($double_nested_node_update_title, $assert_session->elementExists('xpath', $title_field_xpath)->getValue());
  }

  /**
   * Creates ief_reference_type nodes which shall serve as reference nodes.
   *
   * @param int $numNodes
   *   The number of nodes to create.
   *
   * @return array
   *   Array of created node ids keyed by labels.
   */
  protected function createReferenceContent($numNodes = 3) {
    $retval = [];
    for ($i = 1; $i <= $numNodes; $i++) {
      $this->drupalCreateNode([
        'type' => 'ief_reference_type',
        'title' => 'Some reference ' . $i,
        'first_name' => 'First Name ' . $i,
        'last_name' => 'Last Name ' . $i,
      ]);
      $node = $this->drupalGetNodeByTitle('Some reference ' . $i);
      $this->assertNotEmpty($node, 'Created ief_reference_type node "' . $node->label() . '"');
      $retval[$node->label()] = $node->id();
    }
    return $retval;
  }

  /**
   * Updates an IEF setting and saves the underlying entity display.
   *
   * @param string $name
   *   The name of the setting.
   * @param mixed $value
   *   The value to set.
   */
  protected function updateSetting(string $name, $value) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $this->entityFormDisplayStorage->load('node.ief_test_complex.default');
    $component = $display->getComponent('multi');
    $component['settings'][$name] = $value;
    $display->setComponent('multi', $component)->save();
  }

  /**
   * Creates a node for every node bundle.
   *
   * @return array
   *   Array of node titles keyed by ids.
   */
  protected function createNodeForEveryBundle() {
    $retval = [];
    $bundles = $this->container->get('entity_type.bundle.info')->getBundleInfo('node');
    foreach ($bundles as $id => $value) {
      $this->drupalCreateNode(['type' => $id, 'title' => $value['label']]);
      $node = $this->drupalGetNodeByTitle($value['label']);
      $this->assertNotEmpty($node, 'Created node "' . $node->label() . '"');
      $retval[$node->id()] = $value['label'];
    }
    return $retval;
  }

  /**
   * Set up the ief_test_nested1 node add form.
   *
   * Sets the nested fields' required settings.
   * Gets the form.
   * Opens the inline entity forms if they are not required.
   *
   * @param bool $required
   *   Whether the fields are required.
   */
  protected function setupNestedComplexForm(bool $required) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    /** @var \Drupal\Core\Field\FieldConfigInterface $ief_test_nested1 */
    $this->fieldConfigStorage->load('node.ief_test_nested1.test_ref_nested1')
      ->setRequired($required)
      ->save();
    /** @var \Drupal\Core\Field\FieldConfigInterface $ief_test_nested2 */
    $this->fieldConfigStorage
      ->load('node.ief_test_nested2.test_ref_nested2')
      ->setRequired($required)
      ->save();

    $this->drupalGet('node/add/ief_test_nested1');

    if (!$required) {
      // Open inline forms if not required.
      $page->pressButton('Add new node 2');
      $this->assertNotEmpty($button = $assert_session->waitForButton('Add new node 3'));
      $button->press();
      $this->assertNotEmpty($assert_session->waitForButton('Create node 3'));
    }

  }

  /**
   * Opens the existing node form on the "multi" field.
   */
  protected function openMultiExistingForm() {
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($multi_fieldset = $assert_session->waitForElement('css', 'fieldset[data-drupal-selector="edit-multi"]'));
    $assert_session->buttonExists('Add existing node', $multi_fieldset)->press();
    $this->assertNotEmpty($assert_session->waitForElement('xpath', $this->getXpathForAutoCompleteInput()));
  }

  /**
   * Check existing node field validation.
   *
   * Checks that an invalid value for an existing node will be display the
   * expected error.
   *
   * @param string $existing_node_text
   *   The text to enter into the existing node text field.
   * @param string $expected_error
   *   The error message that is expected to be shown.
   */
  protected function checkExistingValidationExpectation(string $existing_node_text, string $expected_error) {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->openMultiExistingForm();
    $field = $assert_session->waitForElement('xpath', $this->getXpathForAutoCompleteInput());
    $field->setValue($existing_node_text);
    $page->pressButton('Add node');
    $this->assertNotNull($assert_session->waitForText($expected_error));
    $assert_session->buttonExists('Cancel')->press();
    $this->assertNotEmpty($assert_session->waitForElementRemoved('xpath', $this->getXpathForAutoCompleteInput()));
  }

  /**
   * Tests create access on IEF Complex content type.
   */
  public function testComplexEntityCreate() {
    // Get the xpath selectors for the input fields in this test.
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);

    $user = $this->createUser([
      'create ief_test_complex content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node/add/ief_test_complex');
    $assert_session = $this->assertSession();
    $assert_session->fieldNotExists('all_bundles[actions][bundle]');
    $assert_session->elementNotExists('xpath', $nested_title_field_xpath);

    $user = $this->createUser([
      'create ief_test_complex content',
      'create ief_reference_type content',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('node/add/ief_test_complex');
    $assert_session->fieldExists('all_bundles[actions][bundle]');
    $this->assertSession()->optionExists('edit-all-bundles-actions-bundle', 'ief_reference_type');
    $this->assertSession()->optionExists('edit-all-bundles-actions-bundle', 'ief_test_complex');
    $assert_session->elementExists('xpath', $nested_title_field_xpath);
  }

  /**
   * Checks if nested nodes for ief_test_nested1 content are created.
   *
   * @param \Drupal\node\NodeInterface $top_level_node
   *   The top level node.
   * @param \Drupal\node\NodeInterface $nested_node
   *   The second level node.
   * @param \Drupal\node\NodeInterface $double_nested_node
   *   The the third level node.
   */
  protected function checkNestedNodes(NodeInterface $top_level_node, NodeInterface $nested_node, NodeInterface $double_nested_node) {
    // Check the type and title of the second level node.
    if ($nested_node->id() == $top_level_node->test_ref_nested1->entity->id()) {
      $this->assertEquals(1, $top_level_node->test_ref_nested1->count(), 'Only one nested node created');
      $this->assertSame($top_level_node->test_ref_nested1->entity->label(), $nested_node->label(), "Nested node's title is correct.");
      $this->assertSame('ief_test_nested2', $nested_node->bundle(), "Nested node's type is correct.");

      // Check the type and title of the third level node.
      if ($double_nested_node->id() == $nested_node->test_ref_nested2->entity->id()) {
        $this->assertEquals(1, $nested_node->test_ref_nested2->count(), 'Only one node within a node within a node created.');
        $this->assertSame($nested_node->test_ref_nested2->entity->label(), $double_nested_node->label(), "Node within a node within a node's title is correct.");
        $this->assertSame('ief_test_nested3', $double_nested_node->bundle(), "Node within a node within a node's type is correct.");
        $this->checkNestedNodeEditing($top_level_node);
      }
    }
  }

  /**
   * Tests the separation of nested data.
   *
   * Using entity creation with different bundles nested in each other.
   * Ief_test_nested1 -> ief_test_nested2 -> ief_test_nested3
   *
   * When creating a second ief_test_nested2 it should be empty and not be
   * prefilled with the ief_test_nested3 of the first ief_test_nested2.
   */
  public function testSeparateNestedDataMultiValueFields() {
    // Get the xpath selectors for the input fields in this test.
    $top_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    foreach ([FALSE, TRUE] as $required) {
      $this->setupNestedComplexForm($required);
      $required_string = ($required) ? ' required' : ' unrequired';
      $double_nested_title = 'Dream within a dream' . $required_string;
      $nested_title = 'Dream' . $required_string;
      $top_level_title = 'Reality' . $required_string;
      $assert_session->elementExists('xpath', $top_title_field_xpath)
        ->setValue($top_level_title);
      $assert_session->elementExists('xpath', $nested_title_field_xpath)
        ->setValue($nested_title);
      $assert_session->elementExists('xpath', $double_nested_title_field_xpath)
        ->setValue($double_nested_title);
      $page->pressButton('Create node 3');
      $assert_session->waitForButton('Add new node 3');
      $page->pressButton('Create node 2');
      $assert_session->waitForButton('Add new node 2');
      $page->pressButton('Add new node 2');
      $assert_session->waitForButton('Add new node 3');

      // The new node 2 should be empty and not already have a
      // double_nested_title present.
      $this->assertNoRowByTitle($double_nested_title);
    }
  }

  /**
   * Tests that create and edit of nested data won#t clash.
   *
   * When creating, then editing a nested IEF, the internal widget state must
   * use the same IEF ID on create and edit. Otherwise on saving, the entity
   * will be saved twice, and cause a WSOD.
   *
   * @dataProvider simpleFalseTrueDataProvider
   */
  public function testNestedCreateAndEditWontClash(bool $required) {
    // Get the xpath selectors for the input fields in this test.
    $top_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $double_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);
    $second_edit_button_xpath = $this->getXpathForButtonWithValue('Edit', 2);

    $assert_session = $this->assertSession();

    $this->setupNestedComplexForm($required);
    $required_string = ($required) ? ' required' : ' unrequired';
    $title_1 = 'Rationality' . $required_string;
    $title_1_2 = 'Drain' . $required_string;
    $title_1_2_3 = 'Drain within a drain' . $required_string;
    $title_i_2_3a = "Drone within a drain" . $required_string;
    $title_1_2a = 'Drone' . $required_string;
    $title_1_2a_3 = 'Drain within a drone' . $required_string;
    $title_i_2a_3a = "Drone within a drain" . $required_string;

    $this->elementWithXpath($top_title_field_xpath)
      ->setValue($title_1);
    $this->elementWithXpath($nested_title_field_xpath)
      ->setValue($title_1_2);
    $this->elementWithXpath($double_nested_title_field_xpath)
      ->setValue($title_1_2_3);

    // Close all subforms.
    $this->elementWithXpath($this->buttonXpath('Create node 3'))->press();
    $this->waitForXpath($this->buttonXpath('Add new node 3'));
    $this->elementWithXpath($this->buttonXpath('Create node 2'))->press();
    $this->waitForXpath($this->buttonXpath('Add new node 2'));

    // Re-open all subforms and add a second node 3.
    $this->elementWithXpath($this->buttonXpath('Edit'))->press();
    $this->waitForXpath($this->buttonXpath('Update node 2'));
    $this->elementWithXpath($this->buttonXpath('Add new node 3'))->press();
    $this->assertNotNull($assert_session->waitForButton('Create node 3'));
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)
      ->setValue($title_i_2_3a);
    $this->elementWithXpath($this->buttonXpath('Create node 3'))->press();
    $this->waitForXpath($this->buttonXpath('Add new node 3'));
    $this->elementWithXpath($this->buttonXpath('Update node 2'))->press();
    $this->waitForXpathRemoved($this->buttonXpath('Update node 2'));

    // Repeat. Add node 2a and 2a_3.
    $this->elementWithXpath($this->buttonXpath('Add new node 2'))->press();
    $this->waitForXpath($this->buttonXpath('Create node 2'));
    if (!$required) {
      $this->elementWithXpath($this->buttonXpath('Add new node 3'))->press();
      $this->waitForXpath($this->buttonXpath('Create node 3'));
    }

    $assert_session->elementExists('xpath', $nested_title_field_xpath)
      ->setValue($title_1_2a);
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)
      ->setValue($title_1_2a_3);

    // Close all subforms.
    $this->elementWithXpath($this->buttonXpath('Create node 3'))->press();
    $this->waitForXpath($this->buttonXpath('Add new node 3'));
    $this->elementWithXpath($this->buttonXpath('Create node 2'))->press();
    $this->waitForXpathRemoved($this->buttonXpath('Create node 2'));

    // Re-open all subforms and add a second node 2a_3a.
    $this->waitForXpath($second_edit_button_xpath)->press();
    $this->waitForXpath($this->buttonXpath('Update node 2'));
    $this->elementWithXpath($this->buttonXpath('Add new node 3'))->press();
    $this->waitForXpath($this->buttonXpath('Create node 3'));
    $assert_session->elementExists('xpath', $double_nested_title_field_xpath)
      ->setValue($title_i_2a_3a);
    $this->elementWithXpath($this->buttonXpath('Create node 3'))->press();
    $this->waitForXpath($this->buttonXpath('Add new node 3'));

    // Save everything and assert message.
    $this->elementWithXpath($this->buttonXpath('Save'))->press();
    $this->htmlOutput();
    $assert_session->pageTextContains("IEF test nested 1 $title_1 has been created.");
  }

  /**
   * Tests the token replacement in the description field.
   */
  public function testTokenReplacementInDescriptionField() {
    $assert_session = $this->assertSession();
    $this->drupalGet($this->formContentAddUrl);

    $site_name = \Drupal::token()->replace('[site:name]');
    $assert_session->pageTextContains('Reference multiple nodes. A complex widget on ' . $site_name . '.');
  }

  /**
   * Data provider: FALSE, TRUE.
   */
  public static function simpleFalseTrueDataProvider() {
    return [
      [FALSE],
      [TRUE],
    ];
  }

  /**
   * Assert and return an element via XPath. On fail, save output and throw.
   *
   * @param string $xpath
   *   The XPath.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element.
   */
  public function elementWithXpath(string $xpath): NodeElement {
    return $this->waitForXpath($xpath, 0);
  }

  /**
   * Wait, assert, and return an element via XPath.
   *
   * On fail, save output and throw.
   *
   * @param string $xpath
   *   The XPath.
   * @param int $timeout
   *   The timeout in milliseconds.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element.
   */
  public function waitForXpath(string $xpath, int $timeout = 10000): NodeElement {
    $element = $this->assertSession()->waitForElement('xpath', $xpath, $timeout);
    if (!$element) {
      $this->htmlOutput();
      $this->assertNotNull($element);
    }
    return $element;
  }

  /**
   * Wait and assert removal of an element via XPath.
   *
   * On fail, save output and throw.
   *
   * @param string $xpath
   *   The XPath.
   * @param int $timeout
   *   The timeout in milliseconds.
   *
   * @return bool
   *   Returns always true (else throws).
   */
  public function waitForXpathRemoved(string $xpath, int $timeout = 10000): bool {
    $removed = $this->assertSession()->waitForElementRemoved('xpath', $xpath, $timeout);
    if (!$removed) {
      $this->htmlOutput();
      $this->assertTrue($removed);
    }
    return $removed;
  }

  /**
   * Get xpath for a button.
   *
   * @param string $label
   *   The button's label.
   * @param int $index
   *   The button's index, defaults to 1.
   *
   * @return string
   *   The XPath.
   */
  protected function buttonXpath(string $label, int $index = 1): string {
    return $this->getXpathForButtonWithValue($label, $index);
  }

}
