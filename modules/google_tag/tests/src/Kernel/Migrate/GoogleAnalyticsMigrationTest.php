<?php

namespace Drupal\Tests\google_tag\Kernel\Migrate;

use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;

/**
 * Tests hook install for migrating google analytics configuration to 2.x.
 *
 * @group google_tag
 * @requires module google_analytics
 */
class GoogleAnalyticsMigrationTest extends GoogleTagTestCase {

  /**
   * Container entity id.
   */
  protected const CONTAINER = 'G-ABCD1A2B3C';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'language',
    'google_analytics',
  ];

  /**
   * {@inheritDoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->loadFixture('google_analytics_migrate.php');
  }

  /**
   * Tests that entities are migrated and google tag settings are migrated.
   *
   * @covers ::google_tag_install
   *
   * @throws \Exception
   */
  public function testEntitiesMigrated(): void {
    $this->container->get('module_handler')->loadInclude('google_tag', 'install');
    google_tag_install();
    $gtag_settings = $this->container->get('config.factory')->get('google_tag.settings');
    $default_entity = $gtag_settings->get('default_google_tag_entity');
    self::assertStringStartsWith(self::CONTAINER, $default_entity);
    self::assertNotTrue($gtag_settings->get('use_collection'));
    $google_tag_storage = $this->container->get('entity_type.manager')->getStorage('google_tag_container');
    $container = $google_tag_storage->load($default_entity);
    self::assertNotEmpty($container->get('tag_container_ids'));
    self::assertNotEmpty($container->get('events'));
    $conditions = $container->get('conditions');
    self::assertNotEmpty($conditions);
    self::assertArrayHasKey('user_role', $conditions, 'Custom user role logic is converted into conditions.');
    self::assertArrayHasKey('request_path', $conditions, 'Custom request path role is converted into conditions');
  }

}
