<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests the clearing of access control cache.
 *
 * This is an explicit test for the issue described at
 * https://www.drupal.org/project/workbench_access/issues/3118596
 *
 * @group workbench_access
 */
class AccessCacheTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

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
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the user can edit the node when allowed.
   */
  public function testNodeEdit() {
    // Set up a content type, taxonomy field, and taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $field_name = WorkbenchAccessManagerInterface::FIELD_NAME;
    $title = 'Section';
    $cardinality = -1;
    $field = $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id(), $field_name, $title, $cardinality);
    $this->assertEquals($field->getDefaultValueLiteral(), []);
    $scheme = $this->setUpTaxonomyScheme($node_type, $vocab);
    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $role_storage = \Drupal::service('workbench_access.role_section_storage');

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

    // Create a node that the user cannot edit.
    $node_values = [
      'type' => 'page',
      'title' => 'foo',
      $field_name => [$super_staff_term->id(), $staff_term->id()],
    ];
    $node = $this->createNode($node_values);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Add the user to the super staff section.
    $user_storage->addUser($scheme, $editor, [
      $super_staff_term->id(),
      $base_term->id(),
    ]);
    $this->assertEquals($expected, array_keys($existing_users));

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Now remove the user.
    $user_storage->removeUser($scheme, $editor, [$super_staff_term->id()]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Now add the user by role.
    $role_storage->addRole($scheme, 'editor', [$super_staff_term->id()]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Now remove the user role.
    $role_storage->removeRole($scheme, 'editor', [$super_staff_term->id()]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Now, add the user again.
    $user_storage->addUser($scheme, $editor, [$super_staff_term->id()]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Now delete the term.
    $super_staff_term->delete();
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Create another node assigned to a user section. This check tests that
    // we only deleted the section associations we wanted to.
    $node_values = [
      'type' => 'page',
      'title' => 'foo2',
      $field_name => [$base_term->id(), $staff_term->id()],
    ];
    $node2 = $this->createNode($node_values);

    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Now remove the user.
    $user_storage->removeUser($scheme, $editor, [$base_term->id()]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);

    // Now delete the scheme, which should restore access to both nodes.
    $scheme->delete();
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/' . $node2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

  }

}
