<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests the IEF simple widget.
 *
 * @group inline_entity_form
 */
class SimpleWidgetTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['inline_entity_form_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_simple_single content',
      'create ief_test_custom content',
      'edit any ief_simple_single content',
      'edit own ief_test_custom content',
      'view own unpublished content',
      'create ief_simple_entity_no_bundle content',
      'administer entity_test__without_bundle content',
    ]);
  }

  /**
   * Tests simple IEF widget with different cardinality options.
   */
  public function testSimpleCardinalityOptions() {
    // Get the xpath selectors for the fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $first_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $second_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 3);
    $third_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 4);
    $fourth_nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 5);
    $first_positive_int_field_xpath = $this->getXpathForNthInputByLabelText('Positive int', 1);
    $second_positive_int_field_xpath = $this->getXpathForNthInputByLabelText('Positive int', 2);
    $third_positive_int_field_xpath = $this->getXpathForNthInputByLabelText('Positive int', 3);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->user);
    $cardinality_options = [
      1 => 1,
      2 => 2,
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED => 3,
    ];
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $this->fieldStorageConfigStorage->load('node.single');

    foreach ($cardinality_options as $cardinality => $number_of_items) {
      $field_storage->setCardinality($cardinality);
      $field_storage->save();

      $this->drupalGet('node/add/ief_simple_single');

      $assert_session->elementTextContains('css', 'span.fieldset-legend', 'Single node');
      $assert_session->elementTextContains('css', 'div.description', 'Reference a single node.');

      if ($cardinality === 1) {
        // With cardinality 1, one item should already be on the page.
        $assert_session->buttonNotExists('Add another item');
        $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Host node');
        $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue('Nested single node');
        $assert_session->elementExists('xpath', $first_positive_int_field_xpath)->setValue('42');
        $page->pressButton('Save');
        $assert_session->pageTextContains('IEF simple single Host node has been created.');
        $host_node = $this->getNodeByTitle('Host node');
      }
      elseif ($cardinality === 2) {
        // With cardinality 2, two items should already be on the page.
        $assert_session->buttonNotExists('Add another item');
        $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Host node 2');
        $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue('Nested single node 2');
        $assert_session->elementExists('xpath', $first_positive_int_field_xpath)->setValue('42');
        $assert_session->elementExists('xpath', $second_nested_title_field_xpath)->setValue('Nested single node 3');
        $assert_session->elementExists('xpath', $second_positive_int_field_xpath)->setValue('42');
        $page->pressButton('Save');
        $assert_session->pageTextContains('IEF simple single Host node 2 has been created.');
        $host_node = $this->getNodeByTitle('Host node 2');
      }
      elseif ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
        // With unlimited cardinality, one item should already be on the page,
        // and an 'Add another item' button should appear.
        $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Host node 3');
        $assert_session->elementExists('xpath', $first_nested_title_field_xpath)->setValue('Nested single node 4');
        $assert_session->elementExists('xpath', $first_positive_int_field_xpath)->setValue('42');
        $assert_session->elementNotExists('xpath', $second_positive_int_field_xpath);

        // Press the 'add another item' button and add a second item.
        $assert_session->buttonExists('Add another item')->press();
        $this->assertNotEmpty($assert_session->waitForElement('xpath', $second_nested_title_field_xpath));

        // Assert an extra item isn't added at the same time.
        $assert_session->elementNotExists('xpath', $third_nested_title_field_xpath);
        $assert_session->elementExists('xpath', $second_nested_title_field_xpath)->setValue('Nested single node 5');
        $assert_session->elementExists('xpath', $second_positive_int_field_xpath)->setValue('42');

        // Press the 'add another item' button and add a third item.
        $assert_session->buttonExists('Add another item')->press();
        $this->assertNotEmpty($assert_session->waitForElement('xpath', $third_nested_title_field_xpath));

        // Assert an extra item isn't added at the same time.
        $assert_session->elementNotExists('xpath', $fourth_nested_title_field_xpath);
        $assert_session->elementExists('xpath', $third_nested_title_field_xpath)->setValue('Nested single node 6');
        $assert_session->elementExists('xpath', $third_positive_int_field_xpath)->setValue('42');
        $page->pressButton('Save');
        $assert_session->pageTextContains('IEF simple single Host node 3 has been created.');
        $host_node = $this->getNodeByTitle('Host node 3');
      }
      $this->checkEditAccess($host_node, $number_of_items, $cardinality);
    }
  }

  /**
   * Test Validation on Simple Widget.
   */
  public function testSimpleValidation() {
    // Get the xpath selectors for the fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);
    $positive_int_field_xpath = $this->getXpathForNthInputByLabelText('Positive int', 1);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->user);
    $host_node_title = 'Host Validation Node';
    $this->drupalGet('node/add/ief_simple_single');

    // Assert inline entity field widget title found.
    $assert_session->pageTextContains('Single node');

    // Assert inline entity field description found.
    $assert_session->pageTextContains('Reference a single node.');

    // Assert positive int field found.
    $assert_session->pageTextContains('Positive int');

    $assert_session->elementExists('xpath', $title_field_xpath)->setValue($host_node_title);
    $page->pressButton('Save');

    // Assert title validation fires on Inline Entity Form widget.
    $assert_session->pageTextNotContains('IEF simple single Host Validation Node has been created.');

    // Assert that we're still on form due to to validation error.
    $this->assertSession()->addressEquals('node/add/ief_simple_single');

    $child_title = 'Child node ' . $this->randomString();
    $assert_session->elementExists('xpath', $nested_title_field_xpath)->setValue($child_title);
    $assert_session->elementExists('xpath', $positive_int_field_xpath)->setValue('-1');
    $page->pressButton('Save');

    // Assert field validation fires on Inline Entity Form widget.
    $assert_session->pageTextNotContains('IEF simple single Host Validation Node has been created.');

    // Assert that we're still on form due to to validation error.
    $this->assertSession()->addressEquals('node/add/ief_simple_single');

    $assert_session->elementExists('xpath', $positive_int_field_xpath)->setValue('1');
    $page->pressButton('Save');

    // Assert title validation passes on Inline Entity Form widget.
    $assert_session->pageTextNotContains('Title field is required.');

    // Assert field validation fires on Inline Entity Form widget.
    $assert_session->pageTextNotContains('Positive int must be higher than or equal to 1');
    $assert_session->pageTextContains('IEF simple single Host Validation Node has been created.');

    // Check that nodes were created correctly.
    $host_node = $this->getNodeByTitle($host_node_title);
    $this->assertNotNull($host_node, 'Host node created.');
    if (isset($host_node)) {
      // Assert that address is the canonical page after node add.
      $this->assertSession()
        ->addressEquals($host_node->toUrl('canonical', ['absolute' => TRUE])
          ->toString());
      $child_node = $this->getNodeByTitle($child_title);
      $this->assertNotNull($child_node);
      if (isset($child_node)) {
        $this->assertSame($host_node->single[0]->target_id, $child_node->id(), 'Child node is referenced');
        $this->assertSame($child_node->positive_int[0]->value, '1', 'Child node int field correct.');
        $this->assertSame($child_node->bundle(), 'ief_test_custom', 'Child node is correct bundle.');
      }
    }
  }

  /**
   * Tests if the entity create access works in the simple widget.
   */
  public function testSimpleCreateAccess() {
    // Get the xpath selectors for the fields in this test.
    $nested_title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 2);

    $assert_session = $this->assertSession();

    // Create a user who does not have access to create ief_test_custom nodes.
    $this->drupalLogin($this->createUser([
      'create ief_simple_single content',
    ]));
    $this->drupalGet('node/add/ief_simple_single');
    $assert_session->elementNotExists('xpath', $nested_title_field_xpath);

    // Now test with a user has access to create ief_test_custom nodes.
    $this->drupalLogin($this->user);
    $this->drupalGet('node/add/ief_simple_single');
    $assert_session->elementExists('xpath', $nested_title_field_xpath);
  }

  /**
   * Ensures that an entity without bundles can be used with the simple widget.
   */
  public function testEntityWithoutBundle() {
    // Get the xpath selectors for the fields in this test.
    $title_field_xpath = $this->getXpathForNthInputByLabelText('Title', 1);
    $name_field_xpath = $this->getXpathForNthInputByLabelText('Name', 1);

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->user);
    $this->drupalGet('node/add/ief_simple_entity_no_bundle');
    $assert_session->elementExists('xpath', $title_field_xpath)->setValue('Node title');
    $assert_session->elementExists('xpath', $name_field_xpath)->setValue('Entity title');
    $page->pressButton('Save');
    $assert_session->pageTextContains('IEF simple entity no bundle Node title has been created.');
    $this->assertNodeByTitle('Node title', 'ief_simple_entity_no_bundle');
    $this->assertEntityByLabel('Entity title', 'entity_test__without_bundle');
  }

  /**
   * Tests that user only has access to the their own nodes.
   *
   * @param \Drupal\node\NodeInterface $host_node
   *   The node of the type of ief_simple_single.
   * @param int $number_of_items
   *   The number of entity reference values in the "single" field.
   * @param int $cardinality
   *   The field cardinality with which to check.
   */
  protected function checkEditAccess(NodeInterface $host_node, int $number_of_items, int $cardinality) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $other_user = $this->createUser([
      'edit own ief_test_custom content',
      'edit any ief_simple_single content',
    ]);
    $first_child_node = $host_node->single[0]->entity;
    $first_child_node->setOwner($other_user)->save();
    $this->drupalGet("node/{$host_node->id()}/edit");
    $assert_session->pageTextContains($first_child_node->label());

    // Assert the form of child node without edit access is not found.
    $assert_session->fieldNotExists('single[0][inline_entity_form][title][0][value]');

    // Check that the forms for other child nodes (if any) appear on the form.
    // If $number_of_items is greater than one, iterate through the other
    // fields that should appear on the page.
    $delta = 1;
    while ($delta < $number_of_items) {
      $child_node = $host_node->single[$delta]->entity;
      // Assert the form of child node with edit access is found.
      $delta_field = $assert_session->fieldExists("single[$delta][inline_entity_form][title][0][value]");
      $this->assertStringContainsString($child_node->label(), $delta_field->getValue());
      $delta++;
    }

    // Check that there is NOT an extra "add" form when editing.
    $unexpected_item_number = $number_of_items;

    // Assert no empty "add" entity form is found on edit.
    $assert_session->fieldNotExists("single[$unexpected_item_number][inline_entity_form][title][0][value]");
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $next_item_number = $number_of_items;
      $page->pressButton('Add another item');

      // Assert item $next_item_number does appear after 'Add More'
      // clicked.
      $this->assertNotEmpty($assert_session->waitForField("single[$next_item_number][inline_entity_form][title][0][value]"));

      // Make sure only 1 item is added.
      $unexpected_item_number = $next_item_number + 1;

      // Assert extra item $unexpected_item_number is not added after
      // 'Add More' clicked.
      $assert_session->fieldNotExists("single[$unexpected_item_number][inline_entity_form][title][0][value]");
    }

    // Now that we have confirmed the correct fields appear, let's update the
    // values and save them. We do not have access to the form for delta 0
    // because it is owned by another user.
    $delta = 1;
    $new_titles = [];
    $edit = [];

    // Loop through an update all child node titles.
    while ($delta < $number_of_items) {
      /** @var \Drupal\node\Entity\Node $child_node */
      $child_node = $host_node->single[$delta]->entity;
      $new_titles[$delta] = $child_node->label() . ' - updated';
      $edit["single[$delta][inline_entity_form][title][0][value]"] = $new_titles[$delta];
      $delta++;
    }

    // If cardinality equals CARDINALITY_UNLIMITED then we should have 1 extra
    // form open.
    if ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $new_titles[$delta] = 'Title for new child';
      $edit["single[$delta][inline_entity_form][title][0][value]"] = $new_titles[$delta];
    }
    $this->submitForm($edit, 'Save');
    $assert_session->pageTextContains("IEF simple single {$host_node->label()} has been updated.");

    // Reset cache for nodes.
    $node_ids = [$host_node->id()];
    foreach ($host_node->single as $item) {
      $node_ids[] = $item->entity->id();
    }
    $this->container
      ->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache($node_ids);
    $host_node = Node::load($host_node->id());

    // Check that titles were updated.
    foreach ($new_titles as $delta => $new_title) {
      $child_node = $host_node->single[$delta]->entity;
      $this->assertSame($new_title, $child_node->label(), "Child $delta node title has been updated.");
    }
  }

}
