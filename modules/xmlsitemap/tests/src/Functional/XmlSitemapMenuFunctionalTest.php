<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of menu links.
 *
 * @group xmlsitemap
 */
class XmlSitemapMenuFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content', 'menu_ui'];

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeBundleInfo = $this->container->get('entity_type.bundle.info');
    // Allow anonymous user to administer menu links.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('administer menu');
    $user_role->grantPermission('access content');
    $user_role->save();

    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_enable('menu', $bundle_id);
    }

    $this->admin_user = $this->drupalCreateUser([
      'administer menu',
      'administer xmlsitemap',
      'access administration pages',
    ]);
    $this->normal_user = $this->drupalCreateUser(['access content']);
  }

  /**
   * Test xmlsitemap settings for menu entity.
   *
   * @todo In the D7 version of XML Sitemap, menus were acting as bundles for
   *   menu links. Should we bring back this behavior in D8?
   */
  public function testMenuSettings() {
    // @codingStandardsIgnoreStart
    // $this->drupalLogin($this->admin_user);
    //
    //    $edit = [
    //      'label' => $this->randomMachineName(),
    //      'id' => mb_strtolower($this->randomMachineName()),
    //      'xmlsitemap[status]' => '1',
    //      'xmlsitemap[priority]' => '1.0',
    //    ];
    //    $this->drupalPostForm('admin/structure/menu/add', $edit, 'Save');
    //
    //    xmlsitemap_link_bundle_settings_save('menu', $edit['id'], ['status' => 0, 'priority' => 0.5, 'changefreq' => 0]);
    //
    //    $this->drupalGet('admin/structure/menu/manage/' . $edit['id']);
    //
    //    $menu_id = $edit['id'];
    //    $this->clickLink('Add link');
    //    $edit = [
    //      'link[0][uri]' => 'node',
    //      'title[0][value]' => $this->randomMachineName(),
    //      'description[0][value]' => '',
    //      'enabled[value]' => 1,
    //      'expanded[value]' => FALSE,
    //      'menu_parent' => $menu_id . ':',
    //      'weight[0][value]' => 0,
    //    ];
    //    $this->drupalPostForm(NULL, $edit, 'Save');.
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    foreach ($bundles['menu_link_content'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu_link_content', $bundle_id);
    }
    foreach ($bundles['menu'] as $bundle_id => $bundle) {
      xmlsitemap_link_bundle_delete('menu', $bundle_id);
    }

    parent::tearDown();
  }

}
