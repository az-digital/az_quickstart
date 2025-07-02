<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\BlazyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Blazy manager base.
 *
 * @coversDefaultClass \Drupal\blazy\BlazyManagerBase
 * @group blazy
 */
class BlazyManagerBaseUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpUnitServices();
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testBlazyManagerCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['blazy.libraries', $exception, $this->libraries],
      ['entity.repository', $exception, $this->entityRepository],
      ['entity_type.manager', $exception, $this->entityTypeManager],
      ['renderer', $exception, $this->renderer],
      ['language_manager', $exception, $this->languageManager],
    ];
    // @phpstan-ignore-next-line
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $blazyManager = BlazyManager::create($container);
    $this->assertInstanceOf(BlazyManager::class, $blazyManager);
  }

}
