<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of user links.
 *
 * @group xmlsitemap
 */
class XmlSitemapUserFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected $accounts = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow anonymous user to view user profiles.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('access user profiles');
    $user_role->save();

    xmlsitemap_link_bundle_enable('user', 'user');

    // Enable XML Sitemap settings for users.
    xmlsitemap_link_bundle_settings_save('user', 'user', [
      'status' => 1,
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
    ]);

    // Create the users.
    $this->admin_user = $this->drupalCreateUser([
      'administer users',
      'administer permissions',
      'administer xmlsitemap',
    ]);
    $this->normal_user = $this->drupalCreateUser(['access content']);
  }

  /**
   * Test sitemap link for a blocked user.
   */
  public function testBlockedUser() {
    $this->assertSitemapLinkVisible('user', $this->normal_user->id());

    // Reset the user entity access cache before updating the entity.
    $this->container->get('entity_type.manager')->getAccessControlHandler('user')->resetCache();

    // Block the user.
    $this->normal_user->block();
    $this->normal_user->save();

    $this->assertSitemapLinkNotVisible('user', $this->normal_user->id());
  }

  /**
   * Test sitemap fields on user forms.
   */
  public function testUserForm() {
    $this->drupalLogin($this->admin_user);

    $this->drupalGet('admin/people/create');
    $this->assertSession()->fieldExists('xmlsitemap[status]');
    $this->assertSession()->fieldExists('xmlsitemap[priority]');
    $this->assertSession()->fieldExists('xmlsitemap[changefreq]');

    $this->drupalGet('user/' . $this->normal_user->id() . '/edit');
    $this->assertSession()->fieldExists('xmlsitemap[status]');
    $this->assertSession()->fieldExists('xmlsitemap[priority]');
    $this->assertSession()->fieldExists('xmlsitemap[changefreq]');
  }

}
