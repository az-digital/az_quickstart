<?php

namespace Drupal\Tests\workbench_access\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the user add form input toggle.
 *
 * From what I can tell, autocomplete options are not click testable.
 *
 * @group workbench_access
 */
class WorkbenchAccessByUserFormTest extends WebDriverTestBase {

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
    'link',
  ];

  /**
   * Tests that the AssignUserForm autocomplete works correctly.
   */
  public function testAssignUserAutocomplete() {
    // Set up test taxonomy scheme.
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $this->setUpTaxonomyScheme($node_type, $vocab, 'taxonomy_section');

    // Create a term for the test.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();

    // Set some users with permissions.
    $role = $this->createRole([
      'access administration pages',
      'assign workbench access',
      'bypass workbench access',
      'create page content',
      'edit any page content',
      'delete any page content',
      'use workbench access',
      'access user profiles',
    ], 'admin');

    // The test admin.
    $admin = $this->createUserWithRole($role);

    $this->drupalLogin($admin);

    $path = "/admin/config/workflow/workbench_access/taxonomy_section/sections/{$staff_term->id()}/users";

    $this->drupalGet($path);

    $session = $this->getSession();
    $page = $session->getPage();

    // Check the form toggle.
    $autocomplete = $page->findField('edit-editors-add');
    $batch = $page->findField('edit-editors-add-mass');
    $toggle = $page->find('css', '.switch');
    $this->assertTrue($autocomplete->isVisible());
    $this->assertFalse($batch->isVisible());

    // Test that we can switch input formats.
    $toggle->click();
    $this->assertFalse($autocomplete->isVisible());
    $this->assertTrue($batch->isVisible());
  }

}
