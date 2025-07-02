<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Unit\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Plugin\Action\FlagAction;
use Drupal\user\UserInterface;

/**
 * Unit tests for the flag action plugin.
 *
 * @group flag
 *
 * @coversDefaultClass \Drupal\flag\Plugin\Action\FlagAction
 */
class FlagActionTest extends UnitTestCase {

  /**
   * Mock flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $this->flag = $flag->reveal();
  }

  /**
   * Tests the execute method.
   *
   * @covers ::execute
   */
  public function testExecute() {
    // Test 'flag' op.
    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $flag_service->flag($this->flag, $entity)->shouldBeCalled();
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $plugin->execute($entity);

    // Test 'unflag' op.
    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'unflag',
    ];
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $flag_service->unflag($this->flag, $entity)->shouldBeCalled();
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $plugin->execute($entity);
  }

  /**
   * Tests the access method.
   *
   * @covers ::access
   */
  public function testAccess() {
    // Test access denied.
    $entity = $this->prophesize(EntityInterface::class)->reveal();
    $account = $this->prophesize(UserInterface::class)->reveal();
    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $denied = $this->prophesize(AccessResultForbidden::class);
    $denied->isAllowed()->willReturn(FALSE);
    $denied = $denied->reveal();
    $flag->actionAccess('flag', $account, $entity)->willReturn($denied);
    $this->flag = $flag->reveal();
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);

    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $this->assertFalse($plugin->access($entity, $account));
    $this->assertEquals($denied, $plugin->access($entity, $account, TRUE));

    // Test access allowed.
    $flag = $this->prophesize(FlagInterface::class);
    $flag->id()->willReturn(strtolower($this->randomMachineName()));
    $allowed = $this->prophesize(AccessResult::class);
    $allowed->isAllowed()->willReturn(TRUE);
    $allowed = $allowed->reveal();
    $flag->actionAccess('flag', $account, $entity)->willReturn($allowed);
    $this->flag = $flag->reveal();
    $flag_service = $this->prophesize(FlagServiceInterface::class);
    $flag_service->getFlagById($this->flag->id())->willReturn($this->flag);

    $config = [
      'flag_id' => $this->flag->id(),
      'flag_action' => 'flag',
    ];
    $plugin = new FlagAction($config, 'flag_action:' . $this->flag->id() . '_flag', [], $flag_service->reveal());
    $this->assertTrue($plugin->access($entity, $account));
    $this->assertEquals($allowed, $plugin->access($entity, $account, TRUE));
  }

}
