<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests comment tokens.
 *
 * @group token
 */
class CommentTest extends TokenKernelTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'field',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);

    $node_type = NodeType::create(['type' => 'page', 'name' => 'Page']);
    $node_type->save();

    $this->installConfig(['comment']);

    $this->addDefaultCommentField('node', 'page');
  }

  /**
   *
   */
  public function testCommentTokens() {
    $node = Node::create([
      'type' => 'page',
      'title' => $this->randomMachineName(),
    ]);
    $node->save();

    $parent_comment = Comment::create([
      'entity_id' => $node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'name' => 'anonymous user',
      'mail' => 'anonymous@example.com',
      'subject' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
    ]);
    $parent_comment->save();

    // Fix http://example.com/index.php/comment/1 fails 'url:path' test.
    $parent_comment_path = $parent_comment->toUrl()->toString();

    $tokens = [
      'url' => $parent_comment->toUrl('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
      'url:absolute' => $parent_comment->toUrl('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
      'url:relative' => $parent_comment->toUrl('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->toString(),
      'url:path' => $parent_comment_path,
      'parent:url:absolute' => NULL,
    ];
    $this->assertTokens('comment', ['comment' => $parent_comment], $tokens);

    $comment = Comment::create([
      'entity_id' => $node->id(),
      'pid' => $parent_comment->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'name' => 'anonymous user',
      'mail' => 'anonymous@example.com',
      'subject' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
    ]);
    $comment->save();

    // Fix http://example.com/index.php/comment/1 fails 'url:path' test.
    $comment_path = Url::fromRoute('entity.comment.canonical', ['comment' => $comment->id()])->toString();

    $tokens = [
      'url' => $comment->toUrl('canonical', ['fragment' => "comment-{$comment->id()}"])->setAbsolute()->toString(),
      'url:absolute' => $comment->toUrl('canonical', ['fragment' => "comment-{$comment->id()}"])->setAbsolute()->toString(),
      'url:relative' => $comment->toUrl('canonical', ['fragment' => "comment-{$comment->id()}"])->toString(),
      'url:path' => $comment_path,
      'parent:url:absolute' => $parent_comment->toUrl('canonical', ['fragment' => "comment-{$parent_comment->id()}"])->setAbsolute()->toString(),
    ];
    $this->assertTokens('comment', ['comment' => $comment], $tokens);
  }

}
