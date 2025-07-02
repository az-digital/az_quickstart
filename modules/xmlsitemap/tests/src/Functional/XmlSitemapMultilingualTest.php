<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;

/**
 * Tests the generation of multilingual sitemaps.
 *
 * @group xmlsitemap
 */
class XmlSitemapMultilingualTest extends XmlSitemapMultilingualTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->admin_user);
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
   * Tests the frontpage link for multiple languages.
   */
  public function testFrontpageLink() {
    $this->regenerateSitemap();

    // Check that the frontpage link is correct for default and non-default
    // languages. The link ends with a slash.
    $frontpage_link = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $this->drupalGet('sitemap.xml');
    $this->assertSession()->responseContains($frontpage_link, "English frontpage link found in the sitemap.");

    $this->drupalGet('fr/sitemap.xml');
    $this->assertSession()->responseContains($frontpage_link . 'fr', "French frontpage link found in the sitemap.");
  }

  /**
   * Test Language Selection.
   *
   * Test how links are included in a sitemap depending on the
   * i18n_selection_mode config variable.
   */
  public function testLanguageSelection() {
    $this->drupalLogin($this->admin_user);
    // Create our three different language nodes.
    $node = $this->addSitemapLink(['type' => 'node', 'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    $node_en = $this->addSitemapLink(['type' => 'node', 'language' => 'en']);
    $node_fr = $this->addSitemapLink(['type' => 'node', 'language' => 'fr']);

    // Create three non-node language nodes.
    $link = $this->addSitemapLink(['language' => LanguageInterface::LANGCODE_NOT_SPECIFIED]);
    $link_en = $this->addSitemapLink(['language' => 'en']);
    $link_fr = $this->addSitemapLink(['language' => 'fr']);

    $this->config->set('i18n_selection_mode', 'off')->save();
    $this->regenerateSitemap();
    $this->drupalGetSitemap(['language' => 'en']);
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);
    $this->drupalGet('fr/sitemap.xml');
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);

    $this->config->set('i18n_selection_mode', 'simple')->save();
    $this->regenerateSitemap();
    $this->drupalGetSitemap(['language' => 'en']);
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGet('fr/sitemap.xml');
    $this->assertRawSitemapLinks($node, $node_fr, $link, $link_fr);
    $this->assertNoRawSitemapLinks($node_en, $link_en);

    $this->config->set('i18n_selection_mode', 'mixed')->save();
    $this->regenerateSitemap();
    $this->drupalGetSitemap(['language' => 'en']);
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGet('fr/sitemap.xml');
    $this->assertRawSitemapLinks($node, $node_en, $node_fr, $link, $link_en, $link_fr);

    $this->config->set('i18n_selection_mode', 'default')->save();
    $this->regenerateSitemap();
    $this->drupalGetSitemap(['language' => 'en']);
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);
    $this->drupalGet('fr/sitemap.xml');
    $this->assertRawSitemapLinks($node, $node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node_fr, $link_fr);

    // With strict mode, the language neutral node should not be found, but the
    // language neutral non-node should be.
    $this->config->set('i18n_selection_mode', 'strict')->save();
    $this->regenerateSitemap();
    $this->drupalGetSitemap(['language' => 'en']);
    $this->assertRawSitemapLinks($node_en, $link, $link_en);
    $this->assertNoRawSitemapLinks($node, $node_fr, $link_fr);
    $this->drupalGet('fr/sitemap.xml');
    $this->assertRawSitemapLinks($node_fr, $link, $link_fr);
    $this->assertNoRawSitemapLinks($node, $node_en, $link_en);
  }

}
