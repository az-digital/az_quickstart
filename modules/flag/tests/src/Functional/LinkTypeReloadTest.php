<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

/**
 * Tests the reload link type.
 *
 * @group flag
 */
class LinkTypeReloadTest extends FlagTestBase {

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Test the confirm form link type.
   */
  public function testFlagReloadLink() {
    // Create and log in our user.
    $this->adminUser = $this->drupalCreateUser([
      'administer flags',
    ]);

    $this->drupalLogin($this->adminUser);

    $this->doCreateFlag();
    $this->doFlagNode();
  }

  /**
   * Create a node type and a flag.
   */
  public function doCreateFlag() {
    $this->flag = $this->createFlag('node', [$this->nodeType], 'reload');
  }

  /**
   * Flag a node.
   */
  public function doFlagNode() {
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $node_id = $node->id();
    $flag_id = $this->flag->id();

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache. ???? TODO.
    $this->grantFlagPermissions($this->flag);

    // Create and login a new user.
    $user_1 = $this->drupalCreateUser();
    $this->drupalLogin($user_1);

    // Get the flag count before the flagging, querying the database directly.
    $flag_count_pre = \Drupal::database()->query('SELECT count FROM {flag_counts}
      WHERE flag_id = :flag_id AND entity_type = :entity_type AND entity_id = :entity_id', [
        ':flag_id' => $flag_id,
        ':entity_type' => 'node',
        ':entity_id' => $node_id,
      ])->fetchField();

    // Attempt to load the reload link URL without the token.
    // We (probably) can't obtain the URL from the route rather than hardcoding
    // it, as that would probably give us the token too.
    $this->drupalGet("flag/flag/$flag_id/$node_id/full");
    $this->assertSession()->statusCodeEquals(403);

    // Click the flag link.
    $this->drupalGet('node/' . $node_id);
    $this->clickLink($this->flag->getShortText('flag'));

    // Check that the node is flagged.
    $this->drupalGet('node/' . $node_id);
    $this->assertSession()->linkExists($this->flag->getShortText('unflag'));

    // Check the flag count was incremented.
    $flag_count_flagged = \Drupal::database()->query('SELECT count FROM {flag_counts}
      WHERE flag_id = :flag_id AND entity_type = :entity_type AND entity_id = :entity_id', [
        ':flag_id' => $flag_id,
        ':entity_type' => 'node',
        ':entity_id' => $node_id,
      ])->fetchField();
    $this->assertEquals($flag_count_pre + 1, $flag_count_flagged, "The flag count was incremented.");

    // Attempt to load the reload link URL without the token.
    $this->drupalGet("flag/unflag/$flag_id/$node_id/full");
    $this->assertSession()->statusCodeEquals(403);

    // Unflag the node.
    $this->drupalGet('node/' . $node_id);
    $this->clickLink($this->flag->getShortText('unflag'));

    // Check that the node is no longer flagged.
    $this->drupalGet('node/' . $node_id);
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));

    // Check the flag count was decremented.
    $flag_count_unflagged = \Drupal::database()->query('SELECT count FROM {flag_counts}
      WHERE flag_id = :flag_id AND entity_type = :entity_type AND entity_id = :entity_id', [
        ':flag_id' => $flag_id,
        ':entity_type' => 'node',
        ':entity_id' => $node_id,
      ])->fetchField();
    $this->assertEquals($flag_count_flagged - 1, $flag_count_unflagged, "The flag count was decremented.");
  }

}
