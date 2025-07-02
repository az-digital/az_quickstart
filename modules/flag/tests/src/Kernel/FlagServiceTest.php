<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\flag\Entity\Flag;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the FlagService.
 *
 * @group flag
 */
class FlagServiceTest extends FlagKernelTestBase {

  /**
   * Tests that flags once created can be retrieved.
   */
  public function testFlagServiceGetFlag() {
    // Create a flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    // Search for flag.
    $result = $this->flagService->getAllFlags('node', 'article');
    $this->assertSame(count($result), 1, 'Found flag type');
    $this->assertEquals([$flag->id()], array_keys($result));
  }

  /**
   * Test exceptions are thrown when flagging and unflagging.
   */
  public function testFlagServiceFlagExceptions() {
    $not_article = NodeType::create(['type' => 'not_article']);
    $not_article->save();

    // The service methods don't check access, so our user can be anybody.
    // However for identification purposes we must uniquely identify the user
    // associated with the flagging.
    // First user created has uid == 0, the anonymous user. For non-global flags
    // we need to fake a session_id.
    $account = $this->createUser();
    $session_id = 'anonymous user 1 session_id';

    // Create a flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    // Test flagging.
    // Try flagging an entity that's not a node: a user account.
    try {
      $this->flagService->flag($flag, $account, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Try flagging a node of the wrong bundle.
    $wrong_node = Node::create([
      'type' => 'not_article',
      'title' => $this->randomMachineName(8),
    ]);
    $wrong_node->save();

    try {
      $this->flagService->flag($flag, $wrong_node, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Flag the node, then try to flag it again.
    $flaggable_node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(8),
    ]);
    $flaggable_node->save();

    $this->flagService->flag($flag, $flaggable_node, $account, $session_id);

    try {
      $this->flagService->flag($flag, $flaggable_node, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    try {
      $this->flagService->flag($flag, $flaggable_node, $account);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Test unflagging.
    // Try unflagging an entity that's not a node: a user account.
    try {
      $this->flagService->unflag($flag, $account, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Try unflagging a node of the wrong bundle.
    try {
      $this->flagService->unflag($flag, $wrong_node, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Create a new node that's not flagged, and try to unflag it.
    $unflagged_node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(8),
    ]);
    $unflagged_node->save();

    try {
      $this->flagService->unflag($flag, $unflagged_node, $account, $session_id);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    try {
      $this->flagService->unflag($flag, $unflagged_node, $account);
      $this->fail("The exception was not thrown.");
    }
    catch (\LogicException $e) {
    }

    // Demonstrate a valid combination can be unflagged without throwing an
    // exception.
    try {
      $this->flagService->unflag($flag, $flaggable_node, $account, $session_id);
    }
    catch (\LogicException $e) {
      $this->fail('The unflag() method threw an exception where processing a valid unflag request.');
    }
  }

  /**
   * Tests that getFlaggingUsers method returns the expected result.
   */
  public function testFlagServiceGetFlaggingUsers() {
    // The service methods don't check access, so our user can be anybody.
    $accounts = [$this->createUser(), $this->createUser()];

    // Create a flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
    ]);
    $flag->save();

    // Flag the node.
    $flaggable_node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(8),
    ]);
    $flaggable_node->save();
    foreach ($accounts as $account) {
      $this->flagService->flag($flag, $flaggable_node, $account);
    }

    $flagging_users = $this->flagService->getFlaggingUsers($flaggable_node, $flag);
    $this->assertTrue(is_array($flagging_users), "The method getFlaggingUsers() returns an array.");

    foreach ($accounts as $account) {
      foreach ($flagging_users as $flagging_user) {
        if ($flagging_user->id() == $account->id()) {
          $this->assertTrue(
            $flagging_user->id() == $account->id(),
            "The returned array has the flagged account included."
          );
          break;
        }
      }
    }
  }

  /**
   * Tests global flags in combination with retrieval of all entity flaggings.
   */
  public function testGlobalFlaggingRetrieval() {
    // Create a global flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
      'global' => TRUE,
    ]);
    $flag->save();

    // Flag the node.
    $flaggable_node = Node::create([
      'type' => 'article',
      'title' => $this->randomMachineName(8),
    ]);
    $flaggable_node->save();

    $account_1 = $this->createUser();
    $account_2 = $this->createUser();

    // Flag the global flag as account 1.
    $this->flagService->flag($flag, $flaggable_node, $account_1);

    // Verify flagging is retrievable without an account.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node);
    $this->assertEquals(1, count($flaggings));

    // User that flagged should see the flagging.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node, $account_1);
    $this->assertEquals(1, count($flaggings));

    // Since this is a global flag, any user should see it returned.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node, $account_2);
    $this->assertEquals(1, count($flaggings));

    // For a non-global flag verify only the owner gets the flag.
    $flag = Flag::create([
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'entity_type' => 'node',
      'bundles' => ['article'],
      'flag_type' => 'entity:node',
      'link_type' => 'reload',
      'flagTypeConfig' => [],
      'linkTypeConfig' => [],
      'global' => FALSE,
    ]);
    $flag->save();
    $this->flagService->flag($flag, $flaggable_node, $account_2);

    // Verify both flaggings are returned.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node);
    $this->assertEquals(2, count($flaggings));

    // User that flagged should see both flaggings.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node, $account_2);
    $this->assertEquals(2, count($flaggings));

    // User that hasn't used the second flag will only see the global flag.
    $flaggings = $this->flagService->getAllEntityFlaggings($flaggable_node, $account_1);
    $this->assertEquals(1, count($flaggings));
  }

}
