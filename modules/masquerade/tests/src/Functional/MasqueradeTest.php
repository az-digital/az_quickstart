<?php

namespace Drupal\Tests\masquerade\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests form permissions and user switching functionality.
 *
 * @group masquerade
 */
class MasqueradeTest extends MasqueradeWebTestBase {

  use BlockCreationTrait;

  /**
   * Tests masquerade user links.
   */
  public function testMasquerade() {
    $original_last_access = $this->authUser->getLastAccessedTime();

    $this->drupalLogin($this->adminUser);

    // Verify that a token is required.
    $this->drupalGet('user/0/masquerade');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('user/' . $this->authUser->id() . '/masquerade');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the admin user is able to masquerade.
    $this->assertSessionByUid($this->adminUser->id());
    $this->masqueradeAs($this->authUser);
    $this->assertSessionByUid($this->authUser->id(), $this->adminUser->id());
    $this->assertNoSessionByUid($this->adminUser->id());

    // Verify that a token is required to unmasquerade.
    $this->drupalGet('unmasquerade');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the web user cannot masquerade.
    $this->drupalGet('user/' . $this->adminUser->id() . '/masquerade', [
      'query' => [
        'token' => $this->drupalGetToken('user/' . $this->adminUser->id() . '/masquerade'),
      ],
    ]);
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the user can unmasquerade.
    $this->unmasquerade($this->authUser);
    $this->assertNoSessionByUid($this->authUser->id());
    $this->assertSessionByUid($this->adminUser->id());

    // Verify that masquerading as $authUser did not change the last login
    // time.
    $authUser = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadUnchanged($this->authUser->id());
    $this->assertEquals($original_last_access, $authUser->getLastAccessedTime(), 'Last access timestamp for impersonated user was not changed.');
  }

  /**
   * Tests the unmasquerade block link.
   */
  public function testUnmasqueradeBlockLink(): void {
    $this->placeBlock('masquerade', [
      'region' => 'header',
      'id' => 'masquerade',
    ]);

    $this->drupalLogin($this->adminUser);

    // Check that the 'Switch back' link won't show in block if not enabled.
    $this->submitForm(['masquerade_as' => $this->authUser->getDisplayName()], 'Switch');
    $this->assertSessionByUid($this->authUser->id(), $this->adminUser->id());
    $this->assertSession()->linkNotExistsExact('Switch back');

    $this->clickLink('Unmasquerade');
    $this->assertSession()->pageTextContains("You are no longer masquerading as {$this->authUser->getDisplayName()}.");

    // Turn the link on in block settings.
    $block = Block::load('masquerade');
    $settings = $block->get('settings');
    $settings['show_unmasquerade_link'] = TRUE;
    $block->set('settings', $settings)->save();

    // Check that the 'Switch back' link shows in the block.
    $this->submitForm(['masquerade_as' => $this->authUser->getDisplayName()], 'Switch');
    $this->assertSessionByUid($this->authUser->id(), $this->adminUser->id());
    $this->assertSession()->linkExistsExact('Switch back');

    // Check that the link works.
    $this->clickLink('Switch back');
    $this->assertSession()->pageTextContains("You are no longer masquerading as {$this->authUser->getDisplayName()}.");
  }

}
