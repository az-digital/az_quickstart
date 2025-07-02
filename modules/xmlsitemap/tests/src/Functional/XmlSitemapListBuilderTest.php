<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/**
 * Tests the sitemaps list builder.
 *
 * @group xmlsitemap
 */
class XmlSitemapListBuilderTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'locale', 'content_translation'];

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'administer xmlsitemap',
      'access content',
    ]);
    $this->drupalLogin($this->admin_user);

    $this->languageManager = $this->container->get('language_manager');
    if (!$this->languageManager->getLanguage('fr')) {
      // Add a new language.
      ConfigurableLanguage::createFromLangcode('fr')->save();
    }

    if (!$this->languageManager->getLanguage('en')) {
      // Add a new language.
      ConfigurableLanguage::createFromLangcode('en')->save();
    }
    $edit = [
      'site_default_language' => 'en',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, t('Save configuration'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, t('Save settings'));
  }

  /**
   * Test if the default sitemap exists.
   */
  public function testDefaultSitemap() {
    $this->drupalLogin($this->admin_user);
    $context = [];
    $id = xmlsitemap_sitemap_get_context_hash($context);

    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertSession()->pageTextContains($id);
  }

  /**
   * Test if multiple sitemaps exist and have consistent information.
   */
  public function testMoreSitemaps() {
    $this->drupalLogin($this->admin_user);
    $edit = [
      'label' => 'English',
      'context[language]' => 'en',
    ];
    $this->drupalGet('admin/config/search/xmlsitemap/add');
    $this->submitForm($edit, t('Save'));
    $context = ['language' => 'en'];
    $id = xmlsitemap_sitemap_get_context_hash($context);
    $this->assertSession()->pageTextContains('Saved the English sitemap.');
    $this->assertSession()->pageTextContains($id);

    $edit = [
      'label' => 'French',
      'context[language]' => 'fr',
    ];
    $this->drupalGet('admin/config/search/xmlsitemap/add');
    $this->submitForm($edit, 'Save');
    $context = ['language' => 'fr'];
    $id = xmlsitemap_sitemap_get_context_hash($context);
    $this->assertSession()->pageTextContains('Saved the French sitemap.');
    $this->assertSession()->pageTextContains($id);
    $this->drupalGet('admin/config/search/xmlsitemap/add');

    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains('There is another sitemap saved with the same context.');

    $sitemaps = XmlSitemap::loadMultiple();
    foreach ($sitemaps as $sitemap) {
      $label = $sitemap->label();
      $this->drupalGet("admin/config/search/xmlsitemap/{$sitemap->id()}/delete");
      $this->submitForm([], t('Delete'));
      $this->assertSession()->responseContains((string) new FormattableMarkup('Sitemap %label has been deleted.', ['%label' => $label]));
    }

    $sitemaps = XmlSitemap::loadMultiple();
    $this->assertEquals(0, count($sitemaps), 'No more sitemaps.');
  }

}
