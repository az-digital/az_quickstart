<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Url;
use Drupal\xmlsitemap\Entity\XmlSitemap;

/**
 * Tests the robots.txt file existence.
 *
 * @group xmlsitemap
 */
class XmlSitemapRobotsTxtIntegrationTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['robotstxt'];

  /**
   * Test if sitemap link is included in robots.txt file.
   */
  public function testRobotsTxt() {
    // Request the un-clean robots.txt path so this will work in case there is
    // still the robots.txt file in the root directory. In order to bypass the
    // local robots.txt file we need to rebuild the container and use a Request
    // with clean URLs disabled.
    $this->container = $this->kernel->rebuildContainer();
    $this->prepareRequestForGenerator(FALSE);

    $this->assertNotEmpty(XmlSitemap::loadByContext());
    $this->drupalGet(Url::fromRoute('robotstxt.content'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Sitemap: ' . Url::fromRoute('xmlsitemap.sitemap_xml', [], ['absolute' => TRUE])->toString());
  }

}
