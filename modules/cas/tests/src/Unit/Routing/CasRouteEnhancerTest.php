<?php

namespace Drupal\Tests\cas\Unit\Routing;

use Drupal\cas\Routing\CasRouteEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * CasRouteEnhancer unit tests.
 *
 * @ingroup cas
 * @group cas
 *
 * @coversDefaultClass \Drupal\cas\Routing\CasRouteEnhancer
 */
class CasRouteEnhancerTest extends UnitTestCase {

  /**
   * The mocked CasHelper.
   *
   * @var \Drupal\cas\Service\CasHelper|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $casHelper;

  /**
   * The mocked Request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $request;

  /**
   * The mocked Route.
   *
   * @var \Symfony\Component\Routing\Route|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $route;

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->casHelper = $this->createMock('\Drupal\cas\Service\CasHelper');
    $this->request = $this->createMock('\Symfony\Component\HttpFoundation\Request');
    $this->route = $this->createMock('\Symfony\Component\Routing\Route');
  }

  /**
   * Test the constructor.
   *
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->assertInstanceOf('\Drupal\cas\Routing\CasRouteEnhancer', new CasRouteEnhancer($this->getConfigFactoryStub()));
  }

  /**
   * Tests the enhance() method.
   *
   * @covers ::enhance
   *
   * @dataProvider enhanceDataProvider
   */
  public function testEnhance($path, $cas_logout_enabled, $is_cas_user) {
    $session = $this->createMock(Session::class);
    $session->expects($this->any())
      ->method('get')
      ->with('is_cas_user')
      ->willReturn($is_cas_user);
    $this->request->expects($this->any())
      ->method('hasSession')
      ->willReturn(TRUE);
    $this->request->expects($this->any())
      ->method('getSession')
      ->willReturn($session);
    $this->route->expects($this->any())
      ->method('getPath')
      ->willReturn($path);

    $enhancer = new CasRouteEnhancer($this->getConfigFactoryStub([
      'cas.settings' => ['logout.cas_logout' => $cas_logout_enabled],
    ]));

    $originalDefaults = ['_route_object' => $this->route];
    $newDefaults = $enhancer->enhance($originalDefaults, $this->request);
    // The controller should only be changed to our custom logout controller
    // if CAS logout is enabled AND the currently logged in user logged in
    // via CAS AND we're on the correct path.
    if ($path == '/user/logout' && $cas_logout_enabled && $is_cas_user) {
      $this->assertArrayHasKey('_controller', $newDefaults, '$newDefaults array does not contain "_controller" key.');
      $this->assertNotEmpty($newDefaults['_controller']);
      $this->assertEquals($newDefaults['_controller'], '\Drupal\cas\Controller\LogoutController::logout');
    }
    else {
      $this->assertEquals($originalDefaults, $newDefaults);
    }
  }

  /**
   * Provides configuration values for testEnhance()
   *
   * @return array
   *   Parameters.
   *
   * @see \Drupal\Tests\cas\Unit\Routing\CasRouteEnhancerTest::testEnhance
   */
  public function enhanceDataProvider() {
    $params = [
      ['/user/logout', FALSE, FALSE],
      ['/user/logout', TRUE, FALSE],
      ['/user/logout', FALSE, TRUE],
      ['/user/logout', TRUE, TRUE],
      ['/foobar', TRUE, TRUE],
    ];
    return $params;
  }

}
