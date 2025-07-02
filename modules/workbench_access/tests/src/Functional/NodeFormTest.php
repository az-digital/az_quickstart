<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests for the node form.
 *
 * @group workbench_access
 */
class NodeFormTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'node',
    'taxonomy',
    'options',
    'user',
    'system',
  ];

  /**
   * Tests that the user can see all valid options on the node form.
   */
  public function testNodeForm() {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $this->assertEquals($field->getDefaultValueLiteral(), []);
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);
    $user_storage = \Drupal::service('workbench_access.user_section_storage');

    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and terms for this test.
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $super_staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $base_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();

    // Add the user to the base section.
    $user_storage->addUser($scheme, $editor, [$base_term->id()]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($scheme, $base_term->id());
    $this->assertEquals($expected, array_keys($existing_users));

    $staff_rid = $this->createRole([], 'staff');
    $super_staff_rid = $this->createRole([], 'super_staff');
    // Set the role -> term mapping.
    \Drupal::service('workbench_access.role_section_storage')->addRole($scheme, $staff_rid, [$staff_term->id()]);
    \Drupal::service('workbench_access.role_section_storage')->addRole($scheme, $super_staff_rid, [$super_staff_term->id()]);

    $web_assert = $this->assertSession();
    $this->drupalGet('node/add/page');

    // Assert we can't see the options yet.
    $web_assert->optionNotExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());
    $web_assert->optionNotExists(WorkbenchAccessManagerInterface::FIELD_NAME, $super_staff_term->getName());

    // Add the staff role and check the option exists.
    $editor->addRole($staff_rid);
    $editor->save();
    $this->drupalGet('node/add/page');
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());

    // Test that we cannot use autocomplete to save a term we cannot access.
    $this->setFieldType('node', 'page', 'entity_reference_autocomplete');

    $this->drupalGet('node/add/page');
    $field_name = WorkbenchAccessManagerInterface::FIELD_NAME . "[0][target_id]";

    // Try to save something that doesn't exist.
    $this->submitForm([$field_name => 'garbage', 'title[0][value]' => 'Foo'], 'Save');
    $web_assert->pageTextContains('There are no taxonomy terms matching "garbage".');

    // Try to force an invalid selection.
    $this->submitForm([
      $field_name => $super_staff_term->label() . ' (' . $super_staff_term->id() . ')',
      'title[0][value]' => 'Foo',
    ], 'Save');
    $web_assert->pageTextContains('The referenced entity (taxonomy_term: 2) does not exist.');

    // Add the super staff role and check both options exist.
    $editor->addRole($super_staff_rid);
    $editor->save();

    // Save a valid selection.
    $this->submitForm([
      $field_name => $super_staff_term->label() . ' (' . $super_staff_term->id() . ')',
      'title[0][value]' => 'Foo',
    ], 'Save');
    $web_assert->pageTextNotContains('The referenced entity (taxonomy_term: 2) does not exist.');

    // Reset the form.
    $this->setFieldType('node', 'page');

    // Test the select widget.
    $this->drupalGet('node/add/page');
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $super_staff_term->getName());

    // Add a default option to the form and test again.
    // See https://www.drupal.org/project/workbench_access/issues/3125798
    $field->setDefaultValue($staff_term->id())->save();

    // Test the select widget.
    $this->drupalGet('node/add/page');
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $staff_term->getName());
    $web_assert->optionExists(WorkbenchAccessManagerInterface::FIELD_NAME, $super_staff_term->getName());

  }

}
