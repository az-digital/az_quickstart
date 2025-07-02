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
class NodeFormMultipleTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Simple array of terms.
   *
   * @var array
   */
  protected $terms = [];

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
   * Tests field handling for multiple select lists.
   */
  public function testNodeMultipleSelectForm() {
    $this->runFieldTest('options_select');
  }

  /**
   * Tests field handling for multiple checkboxes.
   */
  public function testNodeMultipleCheckboxesForm() {
    // $this->runFieldTest('options_buttons');
  }

  /**
   * Tests field handling for basic autocomplete.
   */
  public function testNodeMultipleAutocompleteForm() {
    // $this->runFieldTest('entity_reference_autocomplete');
  }

  /**
   * Runs tests against different field configurations.
   *
   * @param string $field_type
   *   The type of field widget to test: options_select|options_buttons.
   */
  private function runFieldTest($field_type = 'options_select') {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $field_name = WorkbenchAccessManagerInterface::FIELD_NAME;
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id(), $field_name, 'Section', 3, $field_type);
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and terms for this test.
    $this->terms = [];
    // Create terms and roles.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $this->terms[$staff_term->id()] = $staff_term->getName();
    $super_staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Super staff',
    ]);
    $super_staff_term->save();
    $this->terms[$super_staff_term->id()] = $super_staff_term->getName();
    $base_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Editor',
    ]);
    $base_term->save();
    $this->terms[$base_term->id()] = $base_term->getName();

    // Add the user to the base section and the staff section.
    $user_storage->addUser($scheme, $editor, [
      $base_term->id(),
      $staff_term->id(),
    ]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($scheme, $base_term->id());
    $this->assertEquals($expected, array_keys($existing_users));
    $existing_users = $user_storage->getEditors($scheme, $staff_term->id());
    $this->assertEquals($expected, array_keys($existing_users));

    // Create a page as super-admin.
    $admin = $this->setUpAdminUser([
      'bypass node access',
      'bypass workbench access',
    ]);
    $this->drupalLogin($admin);

    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/page');
    if ($field_type === 'options_select') {
      $web_assert->optionExists($field_name . '[]', $base_term->getName());
      $web_assert->optionExists($field_name . '[]', $staff_term->getName());
      $web_assert->optionExists($field_name . '[]', $super_staff_term->getName());
      $edit[$field_name . '[]'] = [
        $base_term->id(),
        $staff_term->id(),
        $super_staff_term->id(),
      ];
    }
    if ($field_type === 'options_buttons') {
      $page->findField($field_name . '[' . $base_term->id() . ']');
      $page->findField($field_name . '[' . $staff_term->id() . ']');
      $page->findField($field_name . '[' . $super_staff_term->id() . ']');
      $edit = [
        $field_name . '[' . $base_term->id() . ']' => $base_term->id(),
        $field_name . '[' . $staff_term->id() . ']' => $staff_term->id(),
        $field_name . '[' . $super_staff_term->id() . ']' => $super_staff_term->id(),
      ];
    }
    if ($field_type === 'entity_reference_autocomplete') {
      $page->findField($field_name . '[0][target_id]');
      $page->findField($field_name . '[1][target_id]');
      $page->findField($field_name . '[2][target_id]');
      $edit = [
        $field_name . '[0][target_id]' => $base_term->getName() . ' (' . $base_term->id() . ')',
        $field_name . '[1][target_id]' => $staff_term->getName() . ' (' . $staff_term->id() . ')',
        $field_name . '[2][target_id]' => $super_staff_term->getName() . ' (' . $super_staff_term->id() . ')',
      ];
    }

    // Save the node.
    $edit['title[0][value]'] = 'Test node';
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    // Get node data. Note that we create one new node for each test case.
    $nid = 1;
    $node = $node_storage->load($nid);

    // Check that three values are set.
    $values = $scheme->getAccessScheme()->getEntityValues($node);
    $this->assertCount(3, $values);

    // Login and save as the editor. Check that hidden values are retained.
    $this->drupalLogin($editor);
    $this->drupalGet('node/1/edit');

    if ($field_type === 'options_select') {
      $web_assert->optionExists($field_name . '[]', $base_term->getName());
      $web_assert->optionExists($field_name . '[]', $staff_term->getName());
      $web_assert->optionNotExists($field_name . '[]', $super_staff_term->getName());
      $edit[$field_name . '[]'] = [
        $base_term->id(),
      ];
    }
    if ($field_type === 'options_buttons') {
      $page->findField($field_name . '[' . $base_term->id() . ']');
      $page->findField($field_name . '[' . $staff_term->id() . ']');
      $web_assert->fieldNotExists($field_name . '[' . $super_staff_term->id() . ']');
      $edit = [
        $field_name . '[' . $base_term->id() . ']' => $base_term->id(),
        $field_name . '[' . $staff_term->id() . ']' => NULL,
      ];
    }
    if ($field_type === 'entity_reference_autocomplete') {
      $page->findField($field_name . '[0][target_id]');
      $page->findField($field_name . '[1][target_id]');
      $web_assert->fieldNotExists($field_name . '[2][target_id]');
      $edit = [
        $field_name . '[0][target_id]' => $base_term->getName() . ' (' . $base_term->id() . ')',
        $field_name . '[1][target_id]' => NULL,
      ];
    }

    // This should retain $base_term->id() and $super_staff_term->id().
    $edit['title[0][value]'] = 'Updated node';
    $this->drupalGet('node/1/edit');
    $this->submitForm($edit, 'Save');

    // Reload the node and test.
    $expected = [3, 2];
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);
    /** @var \Drupal\workbench_access\AccessControlHierarchyInterface $access_scheme */
    $access_scheme = $scheme->getAccessScheme();
    $values = $access_scheme->getEntityValues($node);
    $this->assertCount(2, $values);
    $this->assertEquals($values, $expected);

    // Add a default option to the form and test again.
    // See https://www.drupal.org/project/workbench_access/issues/3125798
    if ($field_type === 'options_select') {
      $field->setDefaultValue($staff_term->id())->save();

      // Test the select widget to ensure no errors thrown.
      $this->drupalGet('node/add/page');

      $web_assert->optionExists($field_name . '[]', $base_term->getName());
      $web_assert->optionExists($field_name . '[]', $staff_term->getName());
      $web_assert->optionNotExists($field_name . '[]', $super_staff_term->getName());
      $edit[$field_name . '[]'] = [
        $base_term->id(),
      ];
    }
  }

}
