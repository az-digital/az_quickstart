<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Kernel;

use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests for flagging storage.
 *
 * @group flag
 */
class FlaggingStorageTest extends FlagKernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * User to test with.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Test flag.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->account = $this->createUser();

    $this->flag = $this->createFlag('node', ['article']);

    // A node to test with.
    $this->createContentType(['type' => 'article']);
    $this->node = $this->createNode(['type' => 'article']);
  }

  /**
   * Test that cache reset is working.
   */
  public function testCacheReset() {
    // Flag the node on behalf of the user.
    $this->flagService->flag($this->flag, $this->node, $this->account);
    $this->assertTrue($this->flag->isFlagged($this->node, $this->account));

    // Unflag and verify that the internal caches have been reset.
    $this->flagService->unflag($this->flag, $this->node, $this->account);
    $this->assertFalse($this->flag->isFlagged($this->node, $this->account));
  }

  /**
   * Test that we can find flaggings based on a single entity.
   */
  public function testLoadIsFlagged() {
    // Flag the node on behalf of the user.
    $this->flagService->flag($this->flag, $this->node, $this->account);

    // Retrieve the flaggingStorage from the entity type manager.
    /** @var \Drupal\flag\Entity\Storage\FlaggingStorageInterface $flaggingStorage */
    $flaggingStorage = $this->entityTypeManager->getStorage('flagging');

    $flaggings = $flaggingStorage->loadIsFlagged($this->node, $this->account);

    $this->assertCount(1, $flaggings, 'Node should be flagged with one flag.');
    $this->assertContains($this->flag->id(), $flaggings, 'Node should be flagged with the test flag.');
  }

  /**
   * Test that we can find flaggings based on multiple entities.
   */
  public function testLoadIsFlaggedMultiple() {
    // Flag the node on behalf of the user.
    $this->flagService->flag($this->flag, $this->node, $this->account);

    // Create another node, flag it too.
    $anotherNode = $this->createNode(['type' => 'article']);
    $this->flagService->flag($this->flag, $anotherNode, $this->account);

    $nodes = [$this->node, $anotherNode];

    // Retrieve the flaggingStorage from the entity type manager.
    /** @var \Drupal\flag\Entity\Storage\FlaggingStorageInterface $flaggingStorage */
    $flaggingStorage = $this->entityTypeManager->getStorage('flagging');

    $flaggings = $flaggingStorage->loadIsFlaggedMultiple($nodes, $this->account);

    $this->assertCount(2, $flaggings, 'Result should contain flaggings for two nodes.');

    foreach ($nodes as $node) {
      $this->assertArrayHasKey($node->id(), $flaggings, 'Result should have flagging for node ' . $node->id() . '.');
      $this->assertCount(1, $flaggings[$node->id()], 'Node ' . $node->id() . ' should be flagged with one flag.');
      $this->assertContains($this->flag->id(), $flaggings[$node->id()], 'Node ' . $node->id() . ' should be flagged with the test flag.');
    }
  }

}
