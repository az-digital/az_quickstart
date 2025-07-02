<?php

namespace Drupal\Tests\google_tag\Kernel\Migrate;

use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;

/**
 * Tests hook update for migrating 1.x container entities to 2.x.
 *
 * @group google_tag
 */
class TagContainerUpdateTest extends GoogleTagTestCase {

  /**
   * Container entity id 1.
   */
  protected const CONTAINER_1 = 'test_gtm_1';

  /**
   * Container entity id 2.
   */
  protected const CONTAINER_2 = 'test_gtm_2';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'language',
  ];

  /**
   * Tests that entities are migrated and google tag settings are migrated.
   *
   * @covers ::google_tag_update_8201
   *
   * @throws \Exception
   */
  public function testEntitiesExist(): void {
    $this->loadFixture('container_migrate.php');
    $this->container->get('module_handler')->loadInclude('google_tag', 'install');
    $sandbox = [];
    google_tag_update_8201($sandbox);
    $gtag_settings = $this->container->get('config.factory')->get('google_tag.settings');
    self::assertEquals(self::CONTAINER_1, $gtag_settings->get('default_google_tag_entity'));
    self::assertTrue($gtag_settings->get('use_collection'));
    $google_tag_storage = $this->container->get('entity_type.manager')->getStorage('google_tag_container');
    $tag_container1 = $google_tag_storage->load(self::CONTAINER_1);
    $tag_container2 = $google_tag_storage->load(self::CONTAINER_2);
    foreach ([$tag_container1, $tag_container2] as $container) {
      self::assertNotEmpty($container->get('tag_container_ids'));
      self::assertNull($container->get('container_id'));
      self::assertArrayHasKey('gtm', $container->get('advanced_settings'));
      self::assertNotEmpty($container->get('events'));
      $conditions = $container->get('conditions');
      self::assertNotEmpty($conditions);
      self::assertArrayHasKey('response_code', $conditions, 'Custom response code logic is migrated into conditions.');
      self::assertArrayNotHasKey('gtag_language', $conditions, 'Old conditions from 1.x are removed.');
      self::assertArrayHasKey('user_role', $conditions, 'Custom user role logic is converted into conditions.');
      self::assertArrayHasKey('request_path', $conditions, 'Custom request path role is converted into conditions');
    }
  }

  /**
   * Tests that there are no changes to config.
   *
   * If there are no existing container entities.
   *
   * @throws \Exception
   */
  public function testNoEntitiesExist(): void {
    // Add the original google_tag 1.x config.
    $this->loadFixture('container_migrate_settings.php');
    $this->container->get('module_handler')->loadInclude('google_tag', 'install');
    $sandbox = [];
    google_tag_update_8201($sandbox);
    /** @var \Drupal\Core\Config\ImmutableConfig $gtag_settings */
    $gtag_settings = $this->container->get('config.factory')->get('google_tag.settings');
    self::assertFalse($gtag_settings->get('use_collection'));
    self::assertEquals('', $gtag_settings->get('default_google_tag_entity'));
    self::assertEmpty($this->container->get('entity_type.manager')->getStorage('google_tag_container')->loadMultiple());
  }

}
