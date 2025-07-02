<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the configured defaults load as intended.
 *
 * @group metatag
 */
class DefaultTagsTest extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Modules for core functionality.
    'node',
    'taxonomy',
    'user',

    // Need this so that the /node page exists.
    'views',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // Use the custom route to verify the site works.
    'metatag_test_custom_route',
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

    // Set the front page to the main /node page, so that the front page is not
    // just the login page.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node')
      ->save(TRUE);
  }

  /**
   * Test the default values for the front page.
   */
  public function testFrontpage() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
    // @todo Expand this selection to cover additional meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this_page_url = $this->buildUrl('<front>');
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a custom route.
   */
  public function testCustomRoute() {
    $this->drupalGet('metatag_test_custom_route');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello world!');

    // Check the meta tags.
    // @todo Expand this selection to cover additional meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this_page_url = $this->buildUrl('/metatag_test_custom_route');
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a Node entity.
   */
  public function testNode() {
    $node = $this->createContentTypeNode();
    $this_page_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Load the node's entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    // @todo Expand this selection to cover additional meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a Term entity.
   */
  public function testTerm() {
    $vocab = $this->createVocabulary();
    $term = $this->createTerm(['vid' => $vocab->id()]);
    $this_page_url = $term->toUrl('canonical', ['absolute' => TRUE])->toString();
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    // @todo Expand this selection to cover additional meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
  }

  /**
   * Test the default values for a User entity.
   */
  public function testUser() {
    // Log in as user 1.
    $account = $this->loginUser1();
    $this_page_url = $account->toUrl('canonical', ['absolute' => TRUE])->toString();

    // Load the user/1 entity page.
    $this->drupalGet($this_page_url);
    $this->assertSession()->statusCodeEquals(200);

    // Check the meta tags.
    // @todo Expand this selection to cover additional meta tags.
    $xpath = $this->xpath("//link[@rel='canonical']");
    self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
    $this->drupalLogout();
  }

  /**
   * Test the default values for the user login page, etc.
   */
  public function testUserLoginPages() {
    $front_url = $this->buildUrl('<front>', ['absolute' => TRUE]);

    // A list of paths to examine.
    $routes = [
      '/user/login',
      '/user/register',
      '/user/password',
    ];

    foreach ($routes as $route) {
      // Identify the path to load.
      $this_page_url = $this->buildUrl($route, ['absolute' => TRUE]);
      $this->assertNotEmpty($this_page_url);

      // Load the path.
      $this->drupalGet($this_page_url);
      $this->assertSession()->statusCodeEquals(200);

      // Check the meta tags.
      // @todo Expand this selection to cover additional meta tags.
      $xpath = $this->xpath("//link[@rel='canonical']");
      $this->assertNotEquals((string) $xpath[0]->getAttribute('href'), $front_url);
      self::assertEquals((string) $xpath[0]->getAttribute('href'), $this_page_url);
    }
  }

}
