<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests pathauto taxonomy UI integration.
 *
 * @group pathauto
 */
class PathautoTaxonomyWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['taxonomy', 'pathauto', 'views'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'administer taxonomy',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('taxonomy_term', '/[term:vocabulary]/[term:name]');
  }

  /**
   * Basic functional testing of Pathauto with taxonomy terms.
   */
  public function testTermEditing() {
    $this->drupalGet('admin/structure');
    $this->drupalGet('admin/structure/taxonomy');

    // Add vocabulary "tags".
    $this->addVocabulary(['name' => 'tags', 'vid' => 'tags']);

    // Create term for testing.
    $name = 'Testing: term name [';
    $automatic_alias = '/tags/testing-term-name';
    $this->drupalGet('admin/structure/taxonomy/manage/tags/add');
    $this->submitForm(['name[0][value]' => $name], 'Save');
    $name = trim($name);
    $this->assertSession()->pageTextContains("Created new term $name.");
    $term = $this->drupalGetTermByName($name);

    // Look for alias generated in the form.
    $this->drupalGet("taxonomy/term/{$term->id()}/edit");
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // Check whether the alias actually works.
    $this->drupalGet($automatic_alias);
    $this->assertSession()->pageTextContains($name);

    // Manually set the term's alias.
    $manual_alias = '/tags/' . $term->id();
    $edit = [
      'path[0][pathauto]' => FALSE,
      'path[0][alias]' => $manual_alias,
    ];
    $this->drupalGet("taxonomy/term/{$term->id()}/edit");
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Updated term $name.");

    // Check that the automatic alias checkbox is now unchecked by default.
    $this->drupalGet("taxonomy/term/{$term->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    $this->assertSession()->fieldValueEquals('path[0][alias]', $manual_alias);

    // Submit the term form with the default values.
    $this->submitForm(['path[0][pathauto]' => FALSE], 'Save');
    $this->assertSession()->pageTextContains("Updated term $name.");

    // Test that the old (automatic) alias has been deleted and only accessible
    // through the new (manual) alias.
    $this->drupalGet($automatic_alias);
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet($manual_alias);
    $this->assertSession()->pageTextContains($name);
  }

}
