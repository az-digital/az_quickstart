<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\metatag\MetatagManager;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the Metatag administration.
 *
 * @group metatag
 */
class MetatagAdminTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    // @see testAvailableConfigEntities
    'block',
    'block_content',
    'contact',
    'field_ui',
    'menu_link_content',
    'menu_ui',
    'node',
    'shortcut',
    'taxonomy',

    // Core test modules.
    'entity_test',
    'test_page_test',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

  /**
   * Tests the interface to manage metatag defaults.
   */
  public function testDefaults() {
    // Save the default title to test the Revert operation at the end.
    $metatag_defaults = \Drupal::config('metatag.metatag_defaults.global');
    $default_title = $metatag_defaults->get('tags')['title'];

    // Initiate session with a user who can manage metatags.
    $permissions = ['administer site configuration', 'administer meta tags'];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Check that the user can see the list of metatag defaults.
    $this->drupalGet('admin/config/search/metatag');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Check that the Global defaults were created.
    $session->linkByHrefExists('admin/config/search/metatag/global');

    // Check that Global and entity defaults can't be deleted.
    $session->linkByHrefNotExists('admin/config/search/metatag/global/delete');
    $session->linkByHrefNotExists('admin/config/search/metatag/node/delete');

    // Check that the module defaults were injected into the Global config
    // entity.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $this->assertSession()->fieldExists('edit-title', $metatag_defaults->get('title'));
    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $values = [
      'title' => 'Test title',
      'description' => 'Test description',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    $this->drupalGet('hit-a-404');
    $session->statusCodeEquals(404);
    foreach ($values as $value) {
      $session->responseContains($value);
    }

    // Check that tokens are processed.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $values = [
      'title' => '[site:name] | Test title',
      'description' => '[site:name] | Test description',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();
    $this->drupalGet('hit-a-404');
    $session->statusCodeEquals(404);
    foreach ($values as $value) {
      $processed_value = \Drupal::token()->replace($value);
      $session->responseContains($processed_value);
    }

    // Test the Robots plugin.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $robots_values = ['index', 'follow'];
    $values = [];
    foreach ($robots_values as $value) {
      $values['robots[' . $value . ']'] = TRUE;
    }
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');
    drupal_flush_all_caches();

    // Trigger a 404 request.
    $this->drupalGet('hit-a-404');
    $session->statusCodeEquals(404);
    $robots_value = implode(', ', $robots_values);
    $session->responseContains($robots_value);

    // Test reverting global configuration to its defaults.
    $this->drupalGet('admin/config/search/metatag/global/revert');
    $session->statusCodeEquals(200);
    $this->submitForm([], 'Revert');
    $session->pageTextContains('Reverted Global defaults.');
    $session->pageTextContains($default_title);

    $this->drupalLogout();
  }

  /**
   * Confirm the available entity types show on the add-default page.
   */
  public function testAvailableConfigEntities() {
    // Initiate session with a user who can manage metatags.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Load the default-add page.
    $this->drupalGet('admin/config/search/metatag/add');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Confirm the 'type' field exists.
    $session->fieldExists('id');

    // Compile a list of entities from the list.
    $options = $this->cssSelect('select[name="id"] option');
    $types = [];
    foreach ($options as $option) {
      $types[$option->getAttribute('value')] = $option->getAttribute('value');
    }

    // Check through the values that are in the 'select' list, make sure that
    // unwanted items are not present.
    $this->assertArrayNotHasKey('block_content', $types, 'Custom block entities are not supported.');
    $this->assertArrayNotHasKey('menu_link_content', $types, 'Menu link entities are not supported.');
    $this->assertArrayNotHasKey('shortcut', $types, 'Shortcut entities are not supported.');
    $this->assertArrayHasKey('node__page', $types, 'Nodes are supported.');
    $this->assertArrayHasKey('user__user', $types, 'Users are supported.');
    $this->assertArrayHasKey('entity_test', $types, 'Test entities are supported.');
  }

  /**
   * Tests special pages.
   */
  public function testSpecialPages() {
    // Initiate session with a user who can manage metatags.
    $permissions = ['administer site configuration', 'administer meta tags'];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Adjust the front page and test it.
    $this->drupalGet('admin/config/search/metatag/front');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $values = [
      'description' => 'Front page description',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Front page Metatag defaults.');
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
    $session->responseContains($values['description']);

    // Adjust the 403 page and test it.
    $this->drupalGet('admin/config/search/metatag/403');
    $session->statusCodeEquals(200);
    $values = [
      'description' => '403 page description.',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the 403 access denied Metatag defaults.');
    $this->drupalLogout();
    $this->drupalGet('admin/config/search/metatag');
    $session->statusCodeEquals(403);
    $session->responseContains($values['description']);

    // Adjust the 404 page and test it.
    $this->drupalLogin($account);
    $this->drupalGet('admin/config/search/metatag/404');
    $session->statusCodeEquals(200);
    $values = [
      'description' => '404 page description.',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the 404 page not found Metatag defaults.');
    $this->drupalGet('foo');
    $session->statusCodeEquals(404);
    $session->responseContains($values['description']);
    $this->drupalLogout();
  }

  /**
   * Tests entity and bundle overrides.
   */
  public function testOverrides() {
    // Initiate session with a user who can manage metatags.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
      'create article content',
      'administer nodes',
      'create article content',
      'create page content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Update the Metatag Node defaults.
    $this->drupalGet('admin/config/search/metatag/node');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $values = [
      'title' => 'Test title for a node.',
      'description' => 'Test description for a node.',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Content Metatag defaults.');

    // Create a test node.
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
    ]);

    // Check that the new values are found in the response.
    $this->drupalGet('node/' . $node->id());
    $session->statusCodeEquals(200);
    foreach ($values as $value) {
      $session->responseContains($value);
    }

    // Check that when the node defaults don't define a metatag, the Global one
    // is used.
    // First unset node defaults.
    $this->drupalGet('admin/config/search/metatag/node');
    $session->statusCodeEquals(200);
    $values = [
      'title' => '',
      'description' => '',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Content Metatag defaults.');

    // Then, set global ones.
    $this->drupalGet('admin/config/search/metatag/global');
    $session->statusCodeEquals(200);
    $values = [
      'title' => 'Global title',
      'description' => 'Global description',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains('Saved the Global Metatag defaults.');

    // Next, test that global defaults are rendered since node ones are empty.
    // We are creating a new node as doing a get on the previous one would
    // return cached results.
    // @todo BookTest.php resets the cache of a single node, which is way more
    // performant than creating a node for every set of assertions.
    // @see BookTest::testDelete()
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
    ]);
    $this->drupalGet('node/' . $node->id());
    $session->statusCodeEquals(200);
    foreach ($values as $value) {
      $session->responseContains($value);
    }

    // Now create article overrides and then test them.
    $this->drupalGet('admin/config/search/metatag/add');
    $session->statusCodeEquals(200);
    $values = [
      'id' => 'node__article',
      'title' => 'Article title override',
      'description' => 'Article description override',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains(strip_tags('Created the Content: Article Metatag defaults.'));

    // Confirm the fields load properly on the node/add/article page.
    $node = $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'article',
    ]);
    $this->drupalGet('node/' . $node->id());
    $session->statusCodeEquals(200);
    unset($values['id']);
    foreach ($values as $value) {
      $session->responseContains($value);
    }

    // Test deleting the article defaults.
    $this->drupalGet('admin/config/search/metatag/node__article/delete');
    $session->statusCodeEquals(200);
    $this->submitForm([], 'Delete');
    $session->pageTextContains('Deleted Content: Article defaults.');
  }

  /**
   * Test that the entity default values load on the entity form.
   *
   * And that they can then be overridden correctly.
   */
  public function testEntityDefaultInheritence() {
    // Initiate session with a user who can manage meta tags and content type
    // fields.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
      'administer node fields',
      'create article content',
      'administer nodes',
      'create article content',
      'create page content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Add a Metatag field to the Article content type.
    $session = $this->assertSession();
    $this->fieldUIAddNewField('admin/structure/types/manage/article', 'meta_tags', 'Metatag', 'metatag');

    // Try creating an article, confirm the fields are present. This should be
    // the node default values that are shown.
    $this->drupalGet('node/add/article');
    $session->statusCodeEquals(200);
    $session->fieldValueEquals('field_meta_tags[0][basic][title]', '[node:title] | [site:name]');
    $session->fieldValueEquals('field_meta_tags[0][basic][description]', '[node:summary]');

    // Customize the Article content type defaults.
    $this->drupalGet('admin/config/search/metatag/add');
    $session->statusCodeEquals(200);
    $values = [
      'id' => 'node__article',
      'title' => 'Article title override',
      'description' => 'Article description override',
    ];
    $this->submitForm($values, 'Save');
    $session->pageTextContains(strip_tags('Created the Content: Article Metatag defaults.'));

    // Try creating an article, this time with the overridden defaults.
    $this->drupalGet('node/add/article');
    $session->statusCodeEquals(200);
    $session->fieldValueEquals('field_meta_tags[0][basic][title]', 'Article title override');
    $session->fieldValueEquals('field_meta_tags[0][basic][description]', 'Article description override');
  }

  /**
   * Test that protected Metatag defaults cannot be deleted.
   */
  public function testDefaultProtected() {
    // Initiate session with a user who can manage metatags.
    $permissions = ['administer site configuration', 'administer meta tags'];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
    $session = $this->assertSession();

    // Add default metatag for Articles.
    $edit = [
      'id' => 'node__article',
    ];
    $this->drupalGet('/admin/config/search/metatag/add');
    $this->submitForm($edit, 'Save');

    // Check that protected defaults contains "Revert" link instead of "Delete".
    foreach (MetatagManager::protectedDefaults() as $protected) {
      $session->linkByHrefExists('/admin/config/search/metatag/' . $protected);
      $session->linkByHrefExists('/admin/config/search/metatag/' . $protected . '/revert');
      $session->linkByHrefNotExists('/admin/config/search/metatag/' . $protected . '/delete');
    }

    // Confirm that non protected defaults can be deleted.
    $session->linkByHrefExists('/admin/config/search/metatag/node__article');
    $session->linkByHrefNotExists('/admin/config/search/metatag/node__article/revert');
    $session->linkByHrefExists('/admin/config/search/metatag/node__article/delete');

    // Visit each protected default page to confirm "Delete" button is hidden.
    foreach (MetatagManager::protectedDefaults() as $protected) {
      $this->drupalGet('/admin/config/search/metatag/' . $protected);
      $session->linkNotExists('Delete');
    }

    // Confirm that non protected defaults can be deleted.
    $this->drupalGet('/admin/config/search/metatag/node__article');
    $session->linkExists('Delete');
  }

  /**
   * Test that metatag list page pager works as expected.
   */
  public function testListPager() {
    $this->loginUser1();
    $session = $this->assertSession();

    $this->drupalGet('admin/config/search/metatag');
    $session->statusCodeEquals(200);
    $session->linkByHrefExists('/admin/config/search/metatag/global');
    $session->linkByHrefExists('/admin/config/search/metatag/front');
    $session->linkByHrefExists('/admin/config/search/metatag/403');
    $session->linkByHrefExists('/admin/config/search/metatag/404');
    $session->linkByHrefExists('/admin/config/search/metatag/node');
    $session->linkByHrefExists('/admin/config/search/metatag/taxonomy_term');
    $session->linkByHrefExists('/admin/config/search/metatag/user');

    // Create 50 vocabularies and generate metatag defaults for all of them.
    for ($i = 0; $i < 50; $i++) {
      $vocabulary = $this->createVocabulary();
      MetatagDefaults::create([
        'id' => 'taxonomy_term__' . $vocabulary->id(),
        'label' => 'Taxonomy term: ' . $vocabulary->label(),
      ])->save();
    }

    // Reload the page.
    $this->drupalGet('admin/config/search/metatag');
    $session->linkByHrefExists('/admin/config/search/metatag/global');
    $session->linkByHrefExists('/admin/config/search/metatag/front');
    $session->linkByHrefExists('/admin/config/search/metatag/403');
    $session->linkByHrefExists('/admin/config/search/metatag/404');
    $session->linkByHrefExists('/admin/config/search/metatag/node');
    $session->linkByHrefExists('/admin/config/search/metatag/taxonomy_term');
    // User entity not visible because it has been pushed to the next page.
    $session->linkByHrefNotExists('/admin/config/search/metatag/user');
    $this->clickLink('Next');

    // Go to next page and confirm that parents are loaded and user us present.
    $session->linkByHrefExists('/admin/config/search/metatag/global');
    $session->linkByHrefExists('/admin/config/search/metatag/taxonomy_term');
    // Main links not visible in the 2nd page.
    $session->linkByHrefNotExists('/admin/config/search/metatag/front');
    $session->linkByHrefNotExists('/admin/config/search/metatag/403');
    $session->linkByHrefNotExists('/admin/config/search/metatag/404');
    $session->linkByHrefNotExists('/admin/config/search/metatag/node');
    // User is present because was pushed to page 2.
    $session->linkByHrefExists('/admin/config/search/metatag/user');
  }

  /**
   * Tests for the trim config.
   */
  public function testTrimSettings() {
    $this->loginUser1();
    $this->drupalGet('/admin/config/search/metatag/settings');
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $session->statusCodeEquals(200);
    // Test if option for a metatag that shouldn't be trimmable exists:
    $session->pageTextNotContains('Meta Tags: robots length');
    // Test if option for a trimmable metatag exists:
    $session->pageTextContains('Meta Tags: description length');
    // Test if the title,abstract and description header gets trimmed:
    // Change description abstract and title on the front page:
    $this->drupalGet('/admin/config/search/metatag/front');
    $page->fillField('edit-title', 'my wonderful drupal test site');
    $page->fillField('edit-description', '[site:name] [site:slogan] [random:number]');
    $page->fillField('edit-abstract', 'my wonderful drupal test site abstract');
    $page->pressButton('edit-submit');
    // Set the new trim settings:
    $this->drupalGet('/admin/config/search/metatag/settings');
    $page->fillField('edit-tag-trim-maxlength-metatag-maxlength-description', '5');
    $page->fillField('edit-tag-trim-maxlength-metatag-maxlength-title', '5');
    $page->fillField('edit-tag-trim-maxlength-metatag-maxlength-abstract', '5');
    $page->fillField('edit-tag-trim-tag-trim-method', 'afterValue');
    $page->pressButton('edit-submit');
    // See if on the front page the metatags are correctly trimmed:
    $this->drupalGet('');
    $session->statusCodeEquals(200);
    $session->titleEquals('my wonderful');
    $session->elementAttributeContains('css', 'meta[name=description]', 'content', 'Drupal');
    $session->elementAttributeContains('css', 'meta[name=abstract]', 'content', 'my wonderful');
  }

}
