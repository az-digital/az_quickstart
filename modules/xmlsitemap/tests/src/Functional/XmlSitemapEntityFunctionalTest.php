<?php

namespace Drupal\Tests\xmlsitemap\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTestMul;
use Drupal\user\Entity\Role;

/**
 * Tests the generation of a random content entity links.
 *
 * @group xmlsitemap
 */
class XmlSitemapEntityFunctionalTest extends XmlSitemapTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(['administer entity_test content', 'administer xmlsitemap']);

    // Allow anonymous users to view test entities.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->grantPermission('view test entity');
    $user_role->save();
  }

  /**
   * Test the form at admin/config/search/xmlsitemap/entities/settings.
   */
  public function testEntitiesSettingsForms() {
    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/config/search/xmlsitemap/entities/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('entity_types[entity_test_mul]');
    $this->assertSession()->fieldExists('settings[entity_test_mul][types][entity_test_mul]');
    $edit = [
      'entity_types[entity_test_mul]' => 1,
      'settings[entity_test_mul][types][entity_test_mul]' => 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), [
      'status' => 0,
      'priority' => 0.5,
      'changefreq' => 0,
      'access' => 1,
    ]);
  }

  /**
   * Test Entity Link Bundle Settings Form.
   *
   * Test the form at
   * admin/config/search/xmlsitemap/settings/{entity_type_id}/{bundle_id}.
   */
  public function testEntityLinkBundleSettingsForm() {
    xmlsitemap_link_bundle_enable('entity_test_mul', 'entity_test_mul');
    $this->drupalLogin($this->admin_user);
    // Set priority and inclusion for entity_test_mul - entity_test_mul.
    $this->drupalGet('admin/config/search/xmlsitemap/settings/entity_test_mul/entity_test_mul');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('xmlsitemap[status]');
    $this->assertSession()->fieldExists('xmlsitemap[priority]');
    $this->assertSession()->fieldExists('xmlsitemap[changefreq]');
    $edit = [
      'xmlsitemap[status]' => 0,
      'xmlsitemap[priority]' => 0.3,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_WEEKLY,
    ];
    $this->submitForm($edit, t('Save configuration'));
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), [
      'status' => 0,
      'priority' => 0.3,
      'changefreq' => XMLSITEMAP_FREQUENCY_WEEKLY,
      'access' => 1,
    ]);

    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($entity->toUrl()->getInternalPath());

    $entity->delete();
    $this->assertNoSitemapLink('entity_test_mul');

    $edit = [
      'xmlsitemap[status]' => 1,
      'xmlsitemap[priority]' => 0.6,
      'xmlsitemap[changefreq]' => XMLSITEMAP_FREQUENCY_YEARLY,
    ];
    $this->drupalGet('admin/config/search/xmlsitemap/settings/entity_test_mul/entity_test_mul');
    $this->submitForm($edit, t('Save configuration'));
    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), [
      'status' => 1,
      'priority' => 0.6,
      'changefreq' => XMLSITEMAP_FREQUENCY_YEARLY,
      'access' => 1,
    ]);

    $this->regenerateSitemap();
    $this->drupalGetSitemap();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($entity->toUrl()->getInternalPath());

    $id = $entity->id();
    $entity->delete();
    $this->assertNoSitemapLink('entity_test_mul', $id);
  }

  /**
   * Test User Cannot View Entity.
   */
  public function testUserCannotViewEntity() {
    // Disallow anonymous users to view test entities.
    $user_role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $user_role->revokePermission('view test entity');
    $user_role->save();

    xmlsitemap_link_bundle_enable('entity_test_mul', 'entity_test_mul');

    $entity = EntityTestMul::create();
    $entity->save();
    $this->assertSitemapLinkValues('entity_test_mul', $entity->id(), [
      'status' => 0,
      'priority' => 0.5,
      'changefreq' => 0,
      'access' => 0,
    ]);
  }

}
