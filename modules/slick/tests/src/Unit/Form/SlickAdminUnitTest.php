<?php

namespace Drupal\Tests\slick\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\slick\Form\SlickAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Slick admin form.
 *
 * @coversDefaultClass \Drupal\slick\Form\SlickAdmin
 * @group slick
 */
class SlickAdminUnitTest extends UnitTestCase {

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The slick admin service.
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $slickAdmin;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $slickManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->blazyAdmin = $this->createMock('\Drupal\blazy\Form\BlazyAdminInterface');
    $this->slickManager = $this->createMock('\Drupal\slick\SlickManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::blazyAdmin
   * @covers ::manager
   */
  public function testBlazyAdminCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['blazy.admin.formatter', $exception, $this->blazyAdmin],
      ['slick.manager', $exception, $this->slickManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $slickAdmin = SlickAdmin::create($container);
    $this->assertInstanceOf(SlickAdmin::class, $slickAdmin);
    $this->assertInstanceOf('\Drupal\blazy\Form\BlazyAdminInterface', $slickAdmin->blazyAdmin());
    $this->assertInstanceOf('\Drupal\slick\SlickManagerInterface', $slickAdmin->manager());
  }

}
