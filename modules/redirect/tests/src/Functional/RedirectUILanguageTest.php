<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * UI tests for redirect module with language and content translation modules.
 *
 * This runs the exact same tests as RedirectUITest, but with both the language
 * and content translation modules enabled.
 *
 * @group redirect
 */
class RedirectUILanguageTest extends RedirectUITest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['redirect', 'node', 'path', 'dblog', 'views', 'taxonomy', 'language', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();
  }

  /**
   * Test multilingual scenarios.
   */
  public function testLanguageSpecificRedirects() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/search/redirect/add');
    $this->assertSession()->optionExists('edit-language-0-value', 'en');
    $this->assertSession()->optionExists('edit-language-0-value', 'de');
    $this->assertSession()->optionExists('edit-language-0-value', 'es');
    $this->assertSession()->optionExists('edit-language-0-value', 'und');
    $this->assertSession()->optionNotExists('edit-language-0-value', 'zxx');
    $this->assertSession()->optionExists('edit-language-0-value', 'English');
    $this->assertSession()->optionExists('edit-language-0-value', 'German');
    $this->assertSession()->optionExists('edit-language-0-value', 'Spanish');
    $this->assertSession()->optionExists('edit-language-0-value', '- All languages -');

    // Add a redirect for english.
    $this->drupalGet('admin/config/search/redirect/add');
    $this->submitForm([
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'en',
    ], 'Save');

    // Add a redirect for germany.
    $this->drupalGet('admin/config/search/redirect/add');
    $this->submitForm([
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '<front>',
      'language[0][value]' => 'de',
    ], 'Save');

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 301);

    // Check redirect for germany.
    $this->assertRedirect('de/langpath', '/de', 301);

    // Check no redirect for spanish.
    $this->assertRedirect('es/langpath', NULL, 404);
  }

  /**
   * Test non-language specific redirect.
   */
  public function testUndefinedLangugageRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalGet('admin/config/search/redirect/add');
    $this->submitForm([
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'und',
    ], 'Save');

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 301);

    // Check redirect for spanish.
    $this->assertRedirect('es/langpath', '/es/user', 301);
  }

  /**
   * Test editing the redirect language.
   */
  public function testEditRedirectLanguage() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalGet('admin/config/search/redirect/add');
    $this->submitForm([
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'en',
    ], 'Save');

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 301);

    // Check that redirect for Germany is not working.
    $this->assertRedirect('de/langpath', NULL, 404);

    // Edit the redirect and change the language.
    $this->drupalGet('admin/config/search/redirect');
    $this->clickLink('Edit');
    $this->submitForm(['language[0][value]' => 'de'], 'Save');

    // Check redirect for english is NOT working now.
    $this->assertRedirect('langpath', NULL, 404);

    // Check that redirect for Germany is now working.
    $this->assertRedirect('de/langpath', '/de/user', 301);
  }

}
