<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;

/**
 * Tests the Flag inline links.
 *
 * @group flag
 */
class InlineLinksTest extends FlagTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'node',
    'user',
    'flag',
    'node',
    'field_ui',
    'text',
    'block',
    'contextual',
    'flag_event_test',
    'comment',
  ];

  /**
   * The flag under test.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The node to be flagged and unflagged.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The User used for the test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $user1;

  /**
   * The User used for the test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $user2;

  /**
   * The User used for the test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $user3;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node to flag.
    $this->node = $this->drupalCreateNode(['type' => $this->nodeType]);

    $this->user1 = $this->DrupalCreateUser([
      'access content',
      'skip comment approval',
      'post comments',
      'access comments',
    ]);

    $this->user2 = $this->DrupalCreateUser([
      'access content',
      'skip comment approval',
      'post comments',
      'access comments',
    ]);

    $this->user3 = $this->DrupalCreateUser([
      'access content',
      'skip comment approval',
      'post comments',
      'access comments',
    ]);
  }

  /**
   * Test node inline links.
   */
  public function testNodeInlineLinks() {
    // Login as the admin user.
    $this->drupalLogin($this->adminUser);

    $edit = [
      'flag_short' => 'Flag Inline links',
      'unflag_short' => 'Unflag Inline links',
      'show_as_field' => FALSE,
      'show_in_links[full]' => 'full',
      "bundles[$this->nodeType]" => $this->nodeType,
    ];

    // Create the flag with the AJAX link type using the form.
    $this->flag = $this->createFlagWithForm('node', $edit);

    // Grant flag permissions.
    $this->grantFlagPermissions($this->flag);

    // Log in as regular user.
    $this->drupalLogin($this->user1);

    $node_url = $this->node->toUrl();

    // Navigate to the node page.
    $this->drupalGet($node_url);

    // Confirm the flag link exists as inline link.
    $this->assertSession()->elementExists('xpath', "//ul[@class='links inline']");
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));

    // Click the flag link.
    $this->clickLink($this->flag->getShortText('flag'));
    // Needs only for tests. Links works fine on normal site.
    drupal_flush_all_caches();
    $this->drupalGet($node_url);
    $this->assertSession()->linkExists($this->flag->getShortText('unflag'));

    // Click the unflag link, repeat the check.
    $this->clickLink($this->flag->getShortText('unflag'));
    // Needs only for tests. Links works fine on normal site.
    drupal_flush_all_caches();
    $this->drupalGet($node_url);
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));
  }

  /**
   * Test comment inline links.
   */
  public function testCommentInlineLinks() {
    $this->addDefaultCommentField('node', $this->nodeType);

    $comment = Comment::create([
      'entity_type' => 'node',
      'subject' => 'User 1 comment',
      'entity_id' => $this->node->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->user1->id(),
      'status' => 1,
    ]);
    $comment->save();

    $comment = Comment::create([
      'entity_type' => 'node',
      'subject' => 'User 2 comment',
      'entity_id' => $this->node->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->user2->id(),
      'status' => 1,
    ]);
    $comment->save();

    $comment = Comment::create([
      'entity_type' => 'node',
      'subject' => 'User 3 comment',
      'entity_id' => $this->node->id(),
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->user3->id(),
      'status' => 1,
    ]);
    $comment->save();

    // Login as the admin user.
    $this->drupalLogin($this->adminUser);

    $edit = [
      'flag_short' => 'Flag Comment Inline links',
      'unflag_short' => 'Unflag Comment Inline links',
      'show_as_field' => FALSE,
      'show_in_links[full]' => 'full',
      'bundles[comment]' => 'comment',
    ];

    // Create the flag with the AJAX link type using the form.
    $this->flag = $this->createFlagWithForm('comment', $edit);

    // Grant flag permissions.
    $this->grantFlagPermissions($this->flag);

    // Log in as regular user.
    $this->drupalLogin($this->user1);

    $node_url = $this->node->toUrl();

    // Navigate to the node page.
    $this->drupalGet($node_url);
    $this->assertSession()->elementTextContains('xpath', "//article[@id='comment-1']//ul[@class='links inline']//li[2]//a/text()", 'Flag Comment Inline links');
    $this->assertSession()->elementTextContains('xpath', "//article[@id='comment-2']//ul[@class='links inline']//li[2]//a/text()", 'Flag Comment Inline links');
    $this->assertSession()->elementTextContains('xpath', "//article[@id='comment-3']//ul[@class='links inline']//li[2]//a/text()", 'Flag Comment Inline links');

    // Click the flag link.
    $this->clickLink($this->flag->getShortText('flag'), 1);
    $this->assertSession()->linkExists($this->flag->getShortText('unflag'));

    // Click the unflag link, repeat the check.
    $this->clickLink($this->flag->getShortText('unflag'));
    $this->assertSession()->linkExists($this->flag->getShortText('flag'));
  }

}
