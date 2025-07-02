<?php

namespace Drupal\Tests\masquerade\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class ServiceDecoratorsTest.
 *
 * @group masquerade
 */
class ServiceDecoratorsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'masquerade',
    'user',
  ];

  /**
   * Tests the MetadataBag methods.
   *
   * @covers \Drupal\masquerade\Session\MetadataBag::setMasquerade
   * @covers \Drupal\masquerade\Session\MetadataBag::getMasquerade
   * @covers \Drupal\masquerade\Session\MetadataBag::clearMasquerade
   */
  public function testSetMasquerade() {
    /** @var \Drupal\masquerade\Session\MetadataBag $bag */
    $bag = \Drupal::service('session')->getMetadataBag();
    $this->assertTrue(method_exists($bag, 'setMasquerade'));
    $this->assertTrue(method_exists($bag, 'getMasquerade'));
    $this->assertTrue(method_exists($bag, 'clearMasquerade'));

    $this->assertNull($bag->getMasquerade());
    $uid = '1000';
    $bag->setMasquerade($uid);
    $this->assertSame($bag->getMasquerade(), $uid);
    $uid = '1';
    $bag->setMasquerade($uid);
    $this->assertSame($bag->getMasquerade(), $uid);
    $bag->clearMasquerade();
    $this->assertNull($bag->getMasquerade());
  }

  /**
   * Tests the MasqueradeUserRequestSubscriber methods.
   *
   * @covers \Drupal\masquerade\EventSubscriber\MasqueradeUserRequestSubscriber::setMasquerade
   */
  public function testServiceSetMasquerade() {
    /** @var \Drupal\masquerade\EventSubscriber\MasqueradeUserRequestSubscriber $service */
    $service = \Drupal::service('user_last_access_subscriber');
    $this->assertTrue(method_exists($service, 'setMasquerade'));
  }

}
