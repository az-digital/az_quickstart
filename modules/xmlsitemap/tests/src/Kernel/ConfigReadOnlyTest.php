<?php

namespace Drupal\Tests\xmlsitemap\Kernel;

use Drupal\config_readonly\Exception\ConfigReadonlyStorageException;
use Drupal\Core\Site\Settings;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/**
 * Tests integration with the Configuration Read-only mode module.
 *
 * @group xmlsitemap
 */
class ConfigReadOnlyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_readonly'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Turn on config_readonly via settings manually.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['config_readonly'] = TRUE;
    new Settings($settings);
  }

  /**
   * Test to make sure config_readonly is working as expected in the test.
   */
  public function testConfigReadOnly() {
    // Prove that saving the config entity results in an exception.
    $sitemap = XmlSitemap::loadByContext();
    $sitemap->setLinks(1);
    $this->expectException(ConfigReadonlyStorageException::class);
    $sitemap->save();
  }

  /**
   * Tests that generating the sitemaps will not throw a config exception.
   */
  public function testSitemapGeneration() {
    // Load the default sitemap.
    $sitemaps = XmlSitemap::loadMultiple();
    $sitemap = reset($sitemaps);

    $this->assertNull($sitemap->getLinks());
    $this->assertNull($sitemap->getChunks());
    $this->assertNull($sitemap->getMaxFileSize());
    $this->assertNull($sitemap->getUpdated());

    // Run sitemap generation.
    xmlsitemap_run_unprogressive_batch('xmlsitemap_regenerate_batch');

    // Test that the state was updated correctly after generation.
    $sitemap = Xmlsitemap::load($sitemap->id());
    $this->assertSame(1, $sitemap->getLinks());
    $this->assertSame(1, $sitemap->getChunks());
    $this->assertGreaterThan(0, $sitemap->getMaxFileSize());
    $this->assertGreaterThan(0, $sitemap->getUpdated());
  }

}
