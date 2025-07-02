<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

/**
 * Tests the confirm form link type.
 *
 * @group flag
 */
class LinkTypeConfirmFormTest extends FlagTestBase {

  /**
   * Flag Confirm Message.
   *
   * @var string
   */
  protected $flagConfirmMessage = 'Flag test label 123?';

  /**
   * Unflag Confirm Message.
   *
   * @var string
   */
  protected $unflagConfirmMessage = 'Unflag test label 123?';

  /**
   * Create Button Text.
   *
   * @var string
   */
  protected $createButtonText = 'Create flagging 123?';

  /**
   * Delete Button Text.
   *
   * @var string
   */
  protected $deleteButtonText = 'Delete flagging 123?';

  /**
   * The flag object.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Test the confirm form link type.
   */
  public function testCreateConfirmFlag() {
    $this->drupalLogin($this->adminUser);
    $this->doCreateFlag();
    $this->doFlagUnflagNode();
  }

  /**
   * Create a flag.
   */
  public function doCreateFlag() {
    $edit = [
      'bundles' => [$this->nodeType],
      'linkTypeConfig' => [
        'flag_confirmation' => $this->flagConfirmMessage,
        'unflag_confirmation' => $this->unflagConfirmMessage,
        'flag_create_button' => $this->createButtonText,
        'flag_delete_button' => $this->deleteButtonText,
      ],
      'link_type' => 'confirm',
    ];
    $this->flag = $this->createFlagFromArray($edit);
  }

  /**
   * Create a node, flag it and unflag it.
   */
  public function doFlagUnflagNode() {
    $node = $this->drupalCreateNode(['type' => $this->nodeType]);
    $node_id = $node->id();
    $flag_id = $this->flag->id();

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache.
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

    // Click the flag link.
    $this->drupalGet('node/' . $node_id);
    $this->clickLink($this->flag->getShortText('flag'));

    // Check if we have the confirm form message displayed.
    $this->assertSession()->pageTextContains($this->flagConfirmMessage);

    // Submit the confirm form.
    $this->drupalGet('flag/confirm/flag/' . $flag_id . '/' . $node_id);
    $this->submitForm([], $this->createButtonText);

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

    // Unflag the node.
    $this->clickLink($this->flag->getShortText('unflag'));

    // Check if we have the confirm form message displayed.
    $this->assertSession()->pageTextContains($this->unflagConfirmMessage);

    // Submit the confirm form.
    $this->submitForm([], $this->deleteButtonText);

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
