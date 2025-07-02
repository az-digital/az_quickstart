<?php

namespace Drupal\Tests\xmlsitemap\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests integration with the Metatag module.
 *
 * @group xmlsitemap
 *
 * @covers ::metatag_xmlsitemap_link_alter
 */
class MetatagNoIndexTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The account object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'user',
    'token',
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['system', 'user', 'field', 'metatag']);

    // Allow anonymous user to view user profiles.
    $role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $role->grantPermission('access user profiles');
    $role->save();

    // Enable XML Sitemap settings for users.
    xmlsitemap_link_bundle_enable('user', 'user');
    xmlsitemap_link_bundle_settings_save('user', 'user', [
      'status' => 1,
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
    ]);

    // Create a generic metatag field.
    FieldStorageConfig::create([
      'entity_type' => 'user',
      'field_name' => 'field_metatags',
      'type' => 'metatag',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'user',
      'field_name' => 'field_metatags',
      'bundle' => 'user',
    ])->save();

    $this->account = $this->createUser();

    // Test that the user is visible in the sitemap by default.
    $this->assertEntityVisibleInSitemap($this->account);
  }

  /**
   * Tests overriding an entity's robots meta tag.
   */
  public function testEntityNoIndex() {
    // Set the robots metatago on the user entity.
    $this->account->set('field_metatags', serialize(['robots' => 'noindex']));
    $this->account->save();

    // Test that the user is not visible in the sitemap now.
    $this->assertEntityNotVisibleInSitemap($this->account);

    // Disable the metatag noindex configuration.
    $this->config('xmlsitemap.settings')->set('metatag_exclude_noindex', FALSE)->save();
    drupal_static_reset('metatag_xmlsitemap_link_alter');

    // Test that the user is visible in the sitemap again.
    $this->account->save();
    $this->assertEntityVisibleInSitemap($this->account);
  }

  /**
   * Tests that default robots metatags are ignored.
   */
  public function testDefaultsNoIndex() {
    // Set the user entity default robots metatag to noindex.
    $config = $this->config('metatag.metatag_defaults.user');
    $config->set('tags.robots', 'noindex');
    $config->save();

    // Test that this hasn't changed the link availability.
    $this->account->save();
    $this->assertEntityVisibleInSitemap($this->account);
  }

}
