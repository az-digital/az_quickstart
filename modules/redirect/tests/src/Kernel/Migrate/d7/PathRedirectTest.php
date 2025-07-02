<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Kernel\Migrate\d7;

/**
 * Tests the d7_path_redirect source plugin.
 *
 * @group redirect
 */
class PathRedirectTest extends PathRedirectTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('redirect');
    $this->loadFixture(__DIR__ . '/../../../../fixtures/drupal7.php');
    $this->executeMigration('d7_path_redirect');
  }

  /**
   * Tests the Drupal 7 path redirect to Drupal 8 migration.
   */
  public function testPathRedirect() {
    $this->assertEntity(5, '/test/source/url', 'base:test/redirect/url', '301');
    $this->assertEntity(7, '/test/source/url2', 'http://test/external/redirect/url?foo=bar&biz=buz#fragment-1', '307');
  }

}
