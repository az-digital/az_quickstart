<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_404\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\redirect\Functional\AssertRedirectTrait;

/**
 * UI tests for redirect_404 module with language and content translation.
 *
 * This runs the exact same tests as Fix404RedirectUITest, but with both
 * language and content translation modules enabled.
 *
 * @group redirect_404
 */
class Fix404RedirectUILanguageTest extends Redirect404TestBase {

  use AssertRedirectTrait;

  /**
   * Additional modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language'];

  /**
   * Admin user's permissions for this test.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer redirects',
    'administer redirect settings',
    'access content',
    'bypass node access',
    'create url aliases',
    'administer url aliases',
    'administer languages',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Enable some languages for this test.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('fr');
    $language->save();
  }

  /**
   * Tests the fix 404 pages workflow with language and content translation.
   */
  public function testFix404RedirectList() {
    // Visit a non existing page to have the 404 redirect_error entry.
    $this->drupalGet('fr/testing');

    $redirect = \Drupal::database()->select('redirect_404')
      ->fields('redirect_404')
      ->condition('path', '/testing')
      ->execute()
      ->fetchAll();
    if (count($redirect) == 0) {
      $this->fail('No record was added');
    }

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('testing');
    $this->assertLanguageInTableBody('French');
    // Check the Language view filter uses the default language filter.
    $this->assertSession()->optionExists('edit-langcode', 'All');
    $this->assertSession()->optionExists('edit-langcode', 'en');
    $this->assertSession()->optionExists('edit-langcode', 'de');
    $this->assertSession()->optionExists('edit-langcode', 'es');
    $this->assertSession()->optionExists('edit-langcode', 'fr');
    $this->assertSession()->optionExists('edit-langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $this->clickLink('Add redirect');

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $destination = Url::fromRoute('redirect_404.fix_404')->getInternalPath();
    $expected_query = [
      'destination' => $destination,
      'language' => 'fr',
      'source' => 'testing',
    ];
    $parsed_url = UrlHelper::parse($this->getUrl());
    $this->assertEquals($parsed_url['path'], Url::fromRoute('redirect.add')->setAbsolute()->toString());
    $this->assertEquals($parsed_url['query'], $expected_query);
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'testing');
    $this->assertSession()->optionExists('edit-language-0-value', 'fr');
    // Save the redirect.
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('There are no 404 errors to fix.');
    // Check if the redirect works as expected.
    $this->assertRedirect('fr/testing', 'fr/node', 301);

    // Test removing a redirect assignment, visit again the non existing page.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertSession()->pageTextContains('testing');
    $this->assertLanguageInTableBody('French');
    $this->clickLink('Delete', 0);
    $this->submitForm([], 'Delete');
    $this->assertSession()->addressEquals('admin/config/search/redirect');
    $this->assertSession()->pageTextContains('There is no redirect yet.');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertSession()->pageTextContains('There are no 404 errors to fix.');
    // Should be listed again in the 404 overview.
    $this->drupalGet('fr/testing');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('French');
    // Check the error path visit count.
    $this->assertSession()->elementTextContains('xpath', '//table/tbody/tr/td[2]', '2');
    $this->clickLink('Add redirect');
    // Save the redirect with a different langcode.
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'testing');
    $this->assertSession()->optionExists('edit-language-0-value', 'fr');
    $edit['language[0][value]'] = 'es';
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    // Should still be listed, redirecting to another language does not resolve
    // the path.
    $this->assertLanguageInTableBody('French');
    $this->drupalGet('admin/config/search/redirect');
    $this->assertLanguageInTableBody('Spanish');
    // Check if the redirect works as expected.
    $this->assertRedirect('es/testing', 'es/node', 301);

    // Visit multiple non existing pages to test the Redirect 404 View.
    $this->drupalGet('testing1');
    $this->drupalGet('de/testing2');
    $this->drupalGet('de/testing2?test=1');
    $this->drupalGet('de/testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('French');
    $this->assertLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertSession()->pageTextContains('testing1');
    $this->assertSession()->pageTextContains('testing2');
    $this->assertSession()->pageTextContains('testing2?test=1');
    $this->assertSession()->pageTextContains('testing2?test=2');

    // Test the Language view filter.
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['langcode' => 'de']]);
    $this->assertSession()->pageTextContains('English');
    $this->assertNoLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertSession()->pageTextNotContains('testing1');
    $this->assertSession()->pageTextContains('testing2');
    $this->assertSession()->pageTextContains('testing2?test=1');
    $this->assertSession()->pageTextContains('testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->assertSession()->pageTextContains('testing1');
    $this->assertSession()->pageTextContains('testing2');
    $this->assertSession()->pageTextContains('testing2?test=1');
    $this->assertSession()->pageTextContains('testing2?test=2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['langcode' => 'en']]);
    $this->assertLanguageInTableBody('English');
    $this->assertNoLanguageInTableBody('German');
    $this->assertSession()->pageTextContains('testing1');
    $this->assertSession()->pageTextNotContains('testing2');
    $this->assertSession()->pageTextNotContains('testing2?test=1');
    $this->assertSession()->pageTextNotContains('testing2?test=2');

    // Assign a redirect to 'testing1'.
    $this->clickLink('Add redirect');
    $expected_query = [
      'destination' => $destination,
      'language' => 'en',
      'source' => 'testing1',
    ];
    $parsed_url = UrlHelper::parse($this->getUrl());
    $this->assertEquals($parsed_url['path'], Url::fromRoute('redirect.add')->setAbsolute()->toString());
    $this->assertEquals($parsed_url['query'], $expected_query);
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'testing1');
    $this->assertSession()->optionExists('edit-language-0-value', 'en');
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals('admin/config/search/redirect/404');
    $this->assertNoLanguageInTableBody('English');
    $this->assertLanguageInTableBody('German');
    $this->drupalGet('admin/config/search/redirect');
    $this->assertLanguageInTableBody('Spanish');
    $this->assertLanguageInTableBody('English');
    // Check if the redirect works as expected.
    $this->assertRedirect('/testing1', '/node', 301);
  }

}
