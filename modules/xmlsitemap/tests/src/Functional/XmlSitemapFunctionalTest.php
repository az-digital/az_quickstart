<?php

namespace Drupal\Tests\xmlsitemap\Functional;

/**
 * Tests the generation of sitemaps.
 *
 * @group xmlsitemap
 */
class XmlSitemapFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['path', 'help'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser([
      'access content', 'administer site configuration', 'administer xmlsitemap', 'access administration pages', 'access site reports', 'administer permissions', 'view the administration theme',
    ]);
  }

  /**
   * Test the sitemap file caching.
   */
  public function testSitemapCaching() {
    $this->drupalLogin($this->admin_user);
    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertSession()->statusCodeEquals(200);
    $etag = $this->drupalGetHeader('etag');
    $last_modified = $this->drupalGetHeader('last-modified');
    $this->assertNotEmpty($etag, t('Etag header found.'));
    $this->assertNotEmpty($last_modified, t('Last-modified header found.'));

    $this->drupalGetSitemap([], [], [
      'If-Modified-Since' => $last_modified,
      'If-None-Match' => $etag,
    ]);
    $this->assertSession()->statusCodeEquals(304);
  }

  /**
   * Test base URL functionality.
   *
   * @codingStandardsIgnoreStart
   */
  public function testBaseURL() {
    // @codingStandardsIgnoreEnd
    $this->drupalLogin($this->admin_user);
    $edit = ['xmlsitemap_base_url' => ''];
    $this->drupalGet('admin/config/search/xmlsitemap/settings');
    $this->submitForm($edit, 'Save configuration');

    $edit = ['xmlsitemap_base_url' => 'invalid'];
    $this->drupalGet('admin/config/search/xmlsitemap/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Invalid base URL.');

    $edit = ['xmlsitemap_base_url' => 'http://example.com/ '];
    $this->drupalGet('admin/config/search/xmlsitemap/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('Invalid base URL.');

    $edit = ['xmlsitemap_base_url' => 'http://example.com/'];
    $this->drupalGet('admin/config/search/xmlsitemap/settings');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->regenerateSitemap();
    $this->drupalGetSitemap([], ['base_url' => NULL]);
    $this->assertSession()->responseContains('<loc>http://example.com/</loc>');
  }

  /**
   * Test Status Report.
   *
   * Test that configuration problems are reported properly in the status
   * report.
   */
  public function testStatusReport() {
    $cron_warning_threshold = $this->config('system.cron')->get('threshold.requirements_warning');

    // Test the rebuild flag.
    $this->drupalLogin($this->admin_user);
    $this->state->set('xmlsitemap_generated_last', $this->time->getRequestTime());
    $this->state->set('xmlsitemap_rebuild_needed', TRUE);
    $this->assertXMLSitemapProblems('The XML Sitemap data is out of sync and needs to be completely rebuilt.');
    $this->clickLink('completely rebuilt');
    $this->assertSession()->statusCodeEquals(200);
    $this->state->set('xmlsitemap_rebuild_needed', FALSE);
    $this->assertNoXMLSitemapProblems();

    // Test the regenerate flag (and cron has run recently).
    $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    $this->state->set('xmlsitemap_generated_last', $this->time->getRequestTime() - $cron_warning_threshold - 600);
    $this->state->set('system.cron_last', $this->time->getRequestTime() - $cron_warning_threshold + 600);
    $this->assertNoXMLSitemapProblems();

    // Test the regenerate flag (and cron hasn't run in a while).
    $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    $this->state->set('system.cron_last', 0);
    $this->state->set('install_time', 0);
    $this->state->set('xmlsitemap_generated_last', $this->time->getRequestTime() - $cron_warning_threshold - 600);
    $this->assertXMLSitemapProblems('The XML cached files are out of date and need to be regenerated. You can run cron manually to regenerate the sitemap files.');
    $this->clickLink('run cron manually');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoXMLSitemapProblems();
  }

}
