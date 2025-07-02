<?php

namespace Drupal\Tests\cas\Unit;

use Drupal\cas\CasRedirectData;
use Drupal\Tests\UnitTestCase;

/**
 * CasRedirectData unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\CasRedirectData
 */
class CasRedirectDataTest extends UnitTestCase {

  /**
   * Test the access methods.
   *
   * @covers ::setParameter
   * @covers ::getParameter
   * @covers ::getAllParameters
   */
  public function testParameters() {
    // Set the base login uri.
    $data = new CasRedirectData();

    // Test gateway set.
    $data->setParameter('gateway', 'true');
    $parms = $data->getAllParameters();
    $this->assertEquals('true', $parms['gateway']);

    // Test gateway removal.
    $data->setParameter('gateway', NULL);
    $parms = $data->getAllParameters();
    $this->assertArrayNotHasKey('gateway', $parms, 'Setvalues of null clear parmaters');
  }

  /**
   * Test Service parameter setters and getters.
   *
   * @covers ::setServiceParameter
   * @covers ::getServiceParameter
   * @covers ::getAllServiceParameters
   */
  public function testServiceParmaeters() {
    $data = new CasRedirectData();

    $data->setServiceParameter('destination', 'node/1');
    $parms = $data->getAllServiceParameters();
    $this->assertEquals('node/1', $parms['destination']);
    $this->assertSame('node/1', $data->getServiceParameter('destination'));

    $data->setServiceParameter('destination', NULL);
    $parms = $data->getAllServiceParameters();
    $this->assertArrayNotHasKey('destination', $parms);
  }

  /**
   * Test Force/allow redirectors.
   *
   * @covers ::willRedirect
   * @covers ::forceRedirection
   * @covers ::preventRedirection
   */
  public function testAllowRedirection() {
    $data = new CasRedirectData();

    $this->assertTrue($data->willRedirect(), 'Default Value');
    $data->forceRedirection();
    $this->assertTrue($data->willRedirect(), 'Forced');
    $data->preventRedirection();
    $this->assertFalse($data->willRedirect(), 'Prevented');
  }

  /**
   * Test Caceable getter and setter.
   */
  public function testCachable() {
    $data = new CasRedirectData();

    $this->assertFalse($data->getIsCacheable(), 'Default Value');

    $data->setIsCacheable(TRUE);
    $this->assertTrue($data->getIsCacheable(), 'Modified value');
  }

}
