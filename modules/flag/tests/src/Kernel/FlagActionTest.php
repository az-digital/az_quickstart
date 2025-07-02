<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\flag\Entity\Flag;
use Drupal\flag\Plugin\Action\DeleteFlaggingAction;

/**
 * Test flag actions are added/removed when flags are added/deleted.
 *
 * @group flag
 */
class FlagActionTest extends FlagKernelTestBase {

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Test admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Fake a logged in user (non-admin).
    $this->admin = $this->createUser();
    $this->account = $this->createUser();
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $switcher */
    $switcher = $this->container->get('account_switcher');
    $switcher->switchTo($this->account);
  }

  /**
   * Tests that flag actions are added and removed properly.
   */
  public function testFlagActionsCreation() {
    $selfies_flag = Flag::create([
      'id' => 'selfies',
      'label' => $this->randomString(),
      'entity_type' => 'user',
      'flag_type' => 'entity:user',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $selfies_flag->save();

    $flag_action = $this->entityTypeManager->getStorage('action')->load('flag_action.selfies_flag');
    $this->assertEquals('flag_action.selfies_flag', $flag_action->id());
    $unflag_action = $this->entityTypeManager->getStorage('action')->load('flag_action.selfies_unflag');
    $this->assertEquals('flag_action.selfies_unflag', $unflag_action->id());

    $selfies_flag->delete();
    $this->entityTypeManager->getStorage('action')->resetCache();
    $this->assertNull($this->entityTypeManager->getStorage('action')->load('flag_action.selfies_flag'));
    $this->assertNull($this->entityTypeManager->getStorage('action')->load('flag_action.selfies_unflag'));
  }

  /**
   * Tests direct use of the action plugins.
   */
  public function testFlagActions() {
    /** @var \Drupal\flag\FlagInterface $entity_flag */
    $entity_flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'entity_test',
      'flag_type' => 'entity:entity_test',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $entity_flag->save();

    $test_entity = EntityTest::create();
    $test_entity->save();
    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    $action = $this->container->get('entity_type.manager')->getStorage('action')->load('flag_action.' . $entity_flag->id() . '_flag');
    /** @var \Drupal\flag\Plugin\Action\FlagAction $plugin */
    $plugin = $action->getPlugin();
    $plugin->execute($test_entity);
    $this->assertTrue($entity_flag->isFlagged($test_entity, $this->account));

    // Access should be false for this user.
    $this->assertFalse($plugin->access($test_entity, $this->account));

    // Admin should have access.
    $this->assertTrue($plugin->access($test_entity, $this->admin));

    // Unflag.
    $this->entityTypeManager->getStorage('flagging')->resetCache();
    $action = $this->entityTypeManager->getStorage('action')->load('flag_action.' . $entity_flag->id() . '_unflag');
    /** @var \Drupal\flag\Plugin\Action\FlagAction $plugin */
    $plugin = $action->getPlugin();
    $plugin->execute($test_entity);

    // @todo Flagging cache cannot be cleared, so this check cannot happen.
    // @see https://www.drupal.org/node/2801423
    // phpcs:ignore
    // $this->assertFalse($entity_flag->isFlagged($test_entity, $this->account));

    // Access should be false for this user.
    $this->assertFalse($plugin->access($test_entity, $this->account));

    // Admin should have access.
    $this->assertTrue($plugin->access($test_entity, $this->admin));
  }

  /**
   * Tests the flagging delete action.
   */
  public function testFlaggingDeleteAction() {
    // Action should be available upon install.
    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    $action = $this->container->get('entity_type.manager')->getStorage('action')->load('flag_delete_flagging');
    $plugin = $action->getPlugin();
    $this->assertInstanceOf(DeleteFlaggingAction::class, $plugin);

    /** @var \Drupal\flag\FlagInterface $entity_flag */
    $entity_flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'entity_test',
      'flag_type' => 'entity:entity_test',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $entity_flag->save();

    // Flag the entity.
    $test_entity = EntityTest::create();
    $test_entity->save();
    $this->flagService->flag($entity_flag, $test_entity);
    $flaggings = $this->flagService->getEntityFlaggings($entity_flag, $test_entity);
    $flagging = reset($flaggings);

    // Verify plugin access for other user is false.
    $other_user = $this->createUser();
    $this->assertFalse($plugin->access($flagging, $other_user));
    $access = $plugin->access($flagging, $other_user, TRUE);
    $this->assertFalse($access->isAllowed());

    // Access for flag owner should be true.
    $this->assertFalse($plugin->access($flagging));
    $access = $plugin->access($flagging, NULL, TRUE);
    $this->assertFalse($access->isAllowed());

    // Execute and verify the flagging is gone.
    $plugin->execute($flagging);
    $this->assertEmpty($this->flagService->getEntityFlaggings($entity_flag, $test_entity));
  }

}
