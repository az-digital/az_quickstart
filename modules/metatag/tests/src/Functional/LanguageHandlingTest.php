<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Testing the language handling.
 *
 * @group metatag
 */
class LanguageHandlingTest extends BrowserTestBase {

  use PathAliasTestTrait;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminAccount;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'locale',
    'path_alias',
    'path',
    'token',
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

    $this->drupalCreateContentType(['type' => 'article']);

    // Setup admin user.
    $this->adminAccount = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'administer languages',
      'administer url aliases',
      'create article content',
      'edit any article content',
      'edit own article content',
    ]);

    $this->drupalLogin($this->adminAccount);

    // Add the German language.
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'de'], 'Add language');
    $this->assertSession()->pageTextContains('The language German has been created and can now be used.');

    // Set admin user language to German.
    $this->adminAccount->set('preferred_langcode', 'de')->save();

    // Set up detection and selection to not use URL detection.
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm([
      'language_interface[enabled][language-url]' => 0,
      'language_interface[enabled][language-user]' => 1,
    ], 'Save settings');

    $this->assertSession()->pageTextContains('Language detection configuration saved.');

    $this->drupalLogout();
  }

  /**
   * Tests URL aliases work.
   */
  public function testPathAlias() {
    // Login as admin with German as site language.
    $this->drupalLogin($this->adminAccount);

    // Create article with alias in sites default language (English).
    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $alias = '/test-content';
    $edit = [
      'path[0][alias]' => $alias,
      'title[0][value]' => 'Test content',
    ];
    $this->submitForm($edit, 'Save');

    // Check that article was created and check address.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($alias);

    // Test that the canonical link is the same as address.
    $xpath = $this->assertSession()->buildXPathQuery("//link[@rel=:rel and contains(@href, :href)]", [
      ':href' => $alias,
      ':rel' => 'canonical',
    ]);
    $links = $this->getSession()->getPage()->findAll('xpath', $xpath);

    $this->assertNotEmpty($links);
  }

}
