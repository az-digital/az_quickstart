<?php

namespace Drupal\azqs_core\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\PageCache\ResponsePolicyInterface;

/**
 * Provides automated tests for the azqs_core module.
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
      'name' => "azqs_core MonitoringPageController's controller functionality",
      'description' => 'Test Unit for module azqs_core and controller MonitoringPageController.',
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
   * Tests azqs_core functionality.
   */
  public function testMonitoringPageController() {
    // Check that the basic functions of module azqs_core.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via Drupal Console.');
  }

}
