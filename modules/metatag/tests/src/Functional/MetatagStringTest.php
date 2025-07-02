<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Ensures that the Metatag field works correctly.
 *
 * @group metatag
 */
class MetatagStringTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'token',
    'node',
    'field_ui',
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'administer node fields',
    'administer content types',
    'access administration pages',
    'administer meta tags',
    'administer nodes',
    'bypass node access',
    'administer meta tags',
    'administer site configuration',
    'access content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType([
      'type' => 'page',
      'display_submitted' => FALSE,
    ]);

    // Add a Metatag field to the content type.
    $this->fieldUIAddNewField('admin/structure/types/manage/page', 'metatag_field', 'Metatag', 'metatag');
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Tests that a meta tag with single quote is not double escaped.
   */
  public function testSingleQuote() {
    $this->checkString("bla'bleblu");
  }

  /**
   * Tests that a meta tag with a double quote is not double escaped.
   */
  public function testDoubleQuote() {
    $this->checkString('bla"bleblu');
  }

  /**
   * Tests that a meta tag with an ampersand is not double escaped.
   */
  public function testAmpersand() {
    $this->checkString("blable&blu");
  }

  /**
   * Tests that specific strings are not double escaped.
   */
  public function checkString($string): void {
    $this->checkConfig($string);
    $this->checkNode($string);
    $this->checkEncodedField($string);
  }

  /**
   * Tests that a specific config string is not double encoded.
   */
  public function checkConfig($string): void {
    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $title_encoded = htmlentities($title_original, ENT_QUOTES);
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $title_encodeded = htmlentities($title_encoded, ENT_QUOTES);
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/front');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    $metatag_defaults = \Drupal::config('metatag.metatag_defaults.front');
    $default_title = $metatag_defaults->get('tags')['title'];
    $default_description = $metatag_defaults->get('tags')['description'];

    // Make sure the title tag is stored correctly.
    $this->assertEquals($title_original, $default_title, 'The title tag was stored in its original format.');
    $this->assertNotEquals($title_encoded, $default_title, 'The title tag was not stored in an encoded format.');
    $this->assertNotEquals($title_encodeded, $default_title, 'The title tag was not stored in a double-encoded format.');

    // Make sure the description tag is stored correctly.
    $this->assertEquals($desc_original, $default_description, 'The description tag was stored in its original format.');
    $this->assertNotEquals($desc_encoded, $default_description, 'The description tag was not stored in an encoded format.');
    $this->assertNotEquals($desc_encodeded, $default_description, 'The description tag was not stored in a double-encoded format.');

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.
    // Create a node.
    $this->drupalGet('node/add/page');
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');

    $this->config('system.site')->set('page.front', '/node/1')->save();

    // Load the front page.
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);

    // Again, with xpath the HTML entities will be parsed automatically.
    $xpath_title = current($this->xpath("//title"))->getText();
    $this->assertEquals($xpath_title, $title_original);
    $this->assertNotEquals($xpath_title, $title_encoded);
    $this->assertNotEquals($xpath_title, $title_encodeded);

    // The page title should be HTML encoded; have to do this check manually
    // because assertRaw() checks the raw HTML, not the parsed strings like
    // xpath does.
    $session->responseContains('<title>' . $title_encoded . '</title>');
    $session->responseNotContains('<title>' . $title_original . '</title>');
    $session->responseNotContains('<title>' . $title_encodeded . '</title>');

    // Again, with xpath the HTML entities will be parsed automatically.
    $xpath = $this->xpath("//meta[@name='description']");
    $this->assertEquals($xpath[0]->getAttribute('content'), $desc_original);
    $this->assertNotEquals($xpath[0]->getAttribute('content'), $desc_encoded);
    $this->assertNotEquals($xpath[0]->getAttribute('content'), $desc_encodeded);
  }

  /**
   * Tests that a specific node string is not double escaped.
   */
  public function checkNode($string): void {
    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $title_encoded = htmlentities($title_original, ENT_QUOTES);
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $title_encodeded = htmlentities($title_encoded, ENT_QUOTES);
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.
    // Create a node.
    $this->drupalGet('node/add/page');
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    // Load the node page.
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);

    // Again, with xpath the HTML entities will be parsed automatically.
    $xpath_title = current($this->xpath("//title"))->getText();
    $this->assertEquals($xpath_title, $title_original);
    $this->assertNotEquals($xpath_title, $title_encoded);
    $this->assertNotEquals($xpath_title, $title_encodeded);

    // The page title should be HTML encoded; have to do this check manually
    // because assertRaw() checks the raw HTML, not the parsed strings like
    // xpath does.
    $session->responseContains('<title>' . $title_encoded . '</title>');
    // Again, with xpath the HTML entities will be parsed automatically.
    $xpath = $this->xpath("//meta[@name='description']");
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, $desc_original);
    $this->assertNotEquals($value, $desc_encoded);
    $this->assertNotEquals($value, $desc_encodeded);

    // Normal meta tags should be encoded properly.
    $session->responseContains('"' . $desc_encoded . '"');
    // Normal meta tags with HTML entities should be displayed in their original
    // format.
    $session->responseNotContains('"' . $desc_original . '"');
    // Normal meta tags should not be double-encoded.
    $session->responseNotContains('"' . $desc_encodeded . '"');
  }

  /**
   * Tests that fields with encoded HTML entities will not be double-encoded.
   */
  public function checkEncodedField($string): void {
    // The original strings.
    $title_original = 'Title: ' . $string;
    $desc_original = 'Description: ' . $string;

    // The strings after they're encoded, but quotes will not be encoded.
    $desc_encoded = htmlentities($desc_original, ENT_QUOTES);

    // The strings double-encoded, to make sure the tags aren't broken.
    $desc_encodeded = htmlentities($desc_encoded, ENT_QUOTES);

    // Update the Global defaults and test them.
    $this->drupalGet('admin/config/search/metatag/global');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $edit = [
      'title' => $title_original,
      'description' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    // Set up a node without explicit metatag description. This causes the
    // global default to be used, which contains a token (node:summary). The
    // token value should be correctly translated.
    // Create a node.
    $this->drupalGet('node/add/page');
    $session->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $title_original,
      'body[0][value]' => $desc_original,
    ];
    $this->submitForm($edit, 'Save');
    $session->statusCodeEquals(200);

    // Load the node page.
    $this->drupalGet('node/1');
    $session->statusCodeEquals(200);

    // With xpath the HTML entities will be parsed automatically.
    $xpath = $this->xpath("//meta[@name='description']");
    $value = $xpath[0]->getAttribute('content');
    $this->assertEquals($value, $desc_original);
    $this->assertNotEquals($value, $desc_encoded);
    $this->assertNotEquals($value, $desc_encodeded);

    // Normal meta tags should be encoded properly.
    $session->responseContains('"' . $desc_encoded . '"');

    // Normal meta tags with HTML entities should be displayed in their original
    // format.
    $session->responseNotContains('"' . $desc_original . '"');

    // Normal meta tags should not be double-encoded.
    $session->responseNotContains('"' . $desc_encodeded . '"');
  }

}
