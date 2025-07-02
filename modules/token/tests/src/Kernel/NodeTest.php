<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Test the node and content type tokens.
 *
 * @group token
 */
class NodeTest extends TokenKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'text'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'description' => "Use <em>basic pages</em> for your static content, such as an 'About us' page.",
    ]);
    $node_type->save();
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
    ]);
    $node_type->save();
  }

  /**
   *
   */
  public function testNodeTokens() {
    $page = Node::create([
      'type' => 'page',
      'title' => 'Source Title',
      'revision_log' => $this->randomMachineName(),
      'path' => ['alias' => '/content/source-node'],
    ]);
    $page->save();
    $tokens = [
      'log' => $page->revision_log->value,
      'url:path' => '/content/source-node',
      'url:absolute' => Url::fromRoute('entity.node.canonical', ['node' => $page->id()], ['absolute' => TRUE])->toString(),
      'url:relative' => Url::fromRoute('entity.node.canonical', ['node' => $page->id()], ['absolute' => FALSE])->toString(),
      'url:unaliased:path' => "/node/{$page->id()}",
      'content-type' => 'Basic page',
      'content-type:name' => 'Basic page',
      'content-type:machine-name' => 'page',
      'content-type:description' => "Use <em>basic pages</em> for your static content, such as an 'About us' page.",
      'content-type:node-count' => 1,
      'content-type:edit-url' => Url::fromRoute('entity.node_type.edit_form', ['node_type' => 'page'], ['absolute' => TRUE])->toString(),
      'source:title' => 'Source Title',
      // Deprecated tokens.
      'type' => 'page',
      'type-name' => 'Basic page',
      'url:alias' => '/content/source-node',
      'language:name' => 'English',
    ];
    $this->assertTokens('node', ['node' => $page], $tokens);

    $article = Node::create([
      'type' => 'article',
      'title' => 'Source Title',
    ]);
    $article->save();
    $tokens = [
      'log' => '',
      'url:path' => "/node/{$article->id()}",
      'url:absolute' => Url::fromRoute('entity.node.canonical', ['node' => $article->id()], ['absolute' => TRUE])->toString(),
      'url:relative' => Url::fromRoute('entity.node.canonical', ['node' => $article->id()], ['absolute' => FALSE])->toString(),
      'url:unaliased:path' => "/node/{$article->id()}",
      'content-type' => 'Article',
      'content-type:name' => 'Article',
      'content-type:machine-name' => 'article',
      'content-type:description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
      'content-type:node-count' => 1,
      'content-type:edit-url' => Url::fromRoute('entity.node_type.edit_form', ['node_type' => 'article'], ['absolute' => TRUE])->toString(),
      'source:title' => 'Source Title',
      // Deprecated tokens.
      'type' => 'article',
      'type-name' => 'Article',
      'url:alias' => "/node/{$article->id()}",
      'language:name' => 'English',
    ];
    $this->assertTokens('node', ['node' => $article], $tokens);
  }

}
