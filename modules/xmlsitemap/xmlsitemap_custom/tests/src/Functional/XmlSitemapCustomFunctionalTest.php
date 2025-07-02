<?php

namespace Drupal\Tests\xmlsitemap_custom\Functional;

use Drupal\Tests\xmlsitemap\Functional\XmlSitemapTestBase;

/**
 * Tests the functionality of xmlsitemap_custom module.
 *
 * @group xmlsitemap
 */
class XmlSitemapCustomFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['xmlsitemap_custom', 'path'];

  /**
   * The alias storage handler.
   *
   * @var \Drupal\path_alias\AliasRepositoryInterface
   */
  protected $aliasStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->aliasStorage = $this->entityTypeManager->getStorage('path_alias');
    $this->admin_user = $this->drupalCreateUser(['access content', 'administer xmlsitemap']);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test adding custom links with wrong/private/correct paths.
   */
  public function testCustomLinks() {
    $language = $this->languageManager->getCurrentLanguage();
    // Set a path alias for the node page.
    $this->aliasStorage
      ->create([
        'path' => '/system/files',
        'alias' => '/public-files',
        'langcode' => $language->getId(),
      ])
      ->save();

    $this->drupalGet('admin/config/search/xmlsitemap/custom');
    $this->clickLink(t('Add custom link'));

    // Test an invalid path.
    $edit['loc'] = '/invalid-testing-path';
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $edit['loc']]));
    $this->assertNoSitemapLink(['type' => 'custom', 'loc' => $edit['loc']]);

    // Test a path not accessible to anonymous user.
    $edit['loc'] = '/admin/people';
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $edit['loc']]));
    $this->assertNoSitemapLink(['type' => 'custom', 'loc' => $edit['loc']]);

    // Test that the current page, which should not give a false positive for
    // $menu_item['access'] since the result has been cached already.
    $edit['loc'] = '/admin/config/search/xmlsitemap/custom/add';
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $edit['loc']]));
    $this->assertNoSitemapLink(['type' => 'custom', 'loc' => $edit['loc']]);
  }

  /**
   * Test adding files as custom links.
   */
  public function testCustomFileLinks() {
    // Test an invalid file.
    $edit['loc'] = '/' . $this->randomMachineName();
    $this->drupalGet('admin/config/search/xmlsitemap/custom/add');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $edit['loc']]));
    $this->assertNoSitemapLink(['type' => 'custom', 'loc' => $edit['loc']]);

    // Test an inaccessible file.
    $edit['loc'] = '/.htaccess';
    $this->drupalGet('admin/config/search/xmlsitemap/custom/add');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $edit['loc']]));
    $this->assertNoSitemapLink(['type' => 'custom', 'loc' => $edit['loc']]);

    // Test a valid file.
    $edit['loc'] = '/core/misc/drupal.js';
    $this->drupalGet('admin/config/search/xmlsitemap/custom/add');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The custom link for @link was saved.', ['@link' => $edit['loc']]));
    $links = $this->linkStorage->loadMultiple(['type' => 'custom', 'loc' => $edit['loc']]);
    $this->assertEquals(1, count($links), t('Custom link saved in the database.'));

    // Test a duplicate url.
    $edit['loc'] = '/core/misc/drupal.js';
    $this->drupalGet('admin/config/search/xmlsitemap/custom/add');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('There is already an existing link in the sitemap with the path @link.', ['@link' => $edit['loc']]));
    $links = $this->linkStorage->loadMultiple(['type' => 'custom', 'loc' => $edit['loc']]);
    $this->assertEquals(1, count($links), t('Custom link saved in the database.'));
  }

}
