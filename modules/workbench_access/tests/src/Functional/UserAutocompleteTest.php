<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the autocomplete results on the access by user form.
 *
 * @group workbench_access
 */
class UserAutocompleteTest extends BrowserTestBase {

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
   * Tests that the correct users are displayed on the access by user form.
   */
  public function testAccessByUserForm() {
    $node_type = $this->createContentType(['type' => 'page']);
    $vocab = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $vocab->id());
    $this->setUpTaxonomyScheme($node_type, $vocab);

    // Set up some roles and terms for this test.
    $staff_term = Term::create([
      'vid' => $vocab->id(),
      'name' => 'Staff',
    ]);
    $staff_term->save();
    $section_id = $staff_term->id();

    // Set up test users.
    $user1 = $this->createUser(['access content'], 'nothing');
    $user2 = $this->createUser(['access content', 'use workbench access'], 'foo');
    $user3 = $this->createUser(['access content', 'use workbench access'], 'bar');
    // The last user is not called directly, but matches user3 search.
    $this->createUser(['access content', 'use workbench access'], 'baz');

    $this->drupalLogin($this->setUpAdminUser());
    $path = sprintf('/admin/config/workflow/workbench_access/editorial_section/sections/%s/users', $section_id);
    $this->drupalGet($path);
    $field = $this->assertSession()->fieldExists('editors_add');
    $autocompleteUrl = $this->getAbsoluteUrl($field->getAttribute('data-autocomplete-path'));

    // Test that no matches found for nonsense query.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => 'zzz']]
    );
    $this->assertEmpty($data, 'Autocomplete returned no results');

    // Test that no matches found for non-privileged user.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => substr($user1->label(), 0, 2)]]
    );
    $this->assertEmpty($data, 'Autocomplete returned no results');

    // Test that only one match found when only one matches.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => substr($user2->label(), 0, 2)]]
    );
    $this->assertCount(1, $data, 'Autocomplete returned 1 result');

    // Test that two matches are found when expected.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => substr($user3->label(), 0, 2)]]
    );
    $this->assertCount(2, $data, 'Autocomplete returned 2 result');

    // Add a user from staff with autocomplete.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();
    $page->fillField('editors_add', $user2->label() . ' (' . $user2->id() . ')');
    $page->pressButton('add');

    // Now re-test.
    $this->drupalGet($path);

    // Test that no match found when user is already added.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => substr($user2->label(), 0, 2)]]
    );
    $this->assertEmpty($data, 'Autocomplete returned no results');

    // Test that two matches are found when expected.
    $data = $this->drupalGetJson(
      $autocompleteUrl,
      ['query' => ['q' => substr($user3->label(), 0, 2)]]
    );
    $this->assertCount(2, $data, 'Autocomplete returned 2 result');
  }

  /**
   * Helper function for JSON formatted requests.
   *
   * @param string $path
   *   Drupal path or URL to load into Mink controlled browser.
   * @param array $options
   *   (optional) Options to be forwarded to the url generator.
   * @param string[] $headers
   *   (optional) An array containing additional HTTP request headers.
   *
   * @return string[]
   *   Array representing decoded JSON response.
   */
  protected function drupalGetJson($path, array $options = [], array $headers = []) {
    $options = array_merge_recursive(['query' => ['_format' => 'json']], $options);
    return Json::decode($this->drupalGet($path, $options, $headers));
  }

}
