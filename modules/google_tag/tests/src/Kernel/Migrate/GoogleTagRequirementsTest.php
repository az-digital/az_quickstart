<?php

namespace Drupal\Tests\google_tag\Kernel\Migrate;

use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;

/**
 * Tests hook google_tag_requirements for updating the module.
 *
 * @group google_tag
 * @requires module google_analytics
 */
class GoogleTagRequirementsTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'google_analytics',
    'node',
    'taxonomy',
    'language',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture('google_analytics_migrate.php');
    $this->loadFixture('container_migrate.php');
  }

  /**
   * Tests that the module is not allowed to be updated.
   *
   * If both google analytics and google tag 1.x were installed.
   *
   * @throws \Exception
   */
  public function testGoogleTagRequirements(): void {
    $this->container->get('module_handler')->loadInclude('google_tag', 'install');
    $requirements = google_tag_requirements('update');
    self::assertArrayHasKey('google_tag', $requirements);
    $google_tag_requirements = $requirements['google_tag'];
    self::assertEquals(
      'In order to use Google Tag 2.x, you must decide the upgrade path between Google Tag 1.x and Google Analytics.',
      $google_tag_requirements['description']
    );
    self::assertEquals('Google Tag', $google_tag_requirements['title']);
    self::assertEquals(REQUIREMENT_ERROR, $google_tag_requirements['severity']);
    self::assertEquals(
      'Google Tag 2.x is incompatible with Google Analytics while upgrading from 1.x.',
      $google_tag_requirements['value']
    );
  }

}
