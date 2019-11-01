<?php

namespace Drupal\az_core\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\PageCache\ResponsePolicyInterface;

/**
 * Provides automated tests for the az_core module.
 */
class MonitoringPageControllerTest extends WebTestBase {

  /**
   * Drupal\Core\PageCache\ResponsePolicyInterface definition.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicyInterface
   */
  protected $pageCacheKillSwitch;


  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "az_core MonitoringPageController's controller functionality",
      'description' => 'Test Unit for module az_core and controller MonitoringPageController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests az_core functionality.
   */
  public function testMonitoringPageController() {
    // Check that the basic functions of module az_core.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
