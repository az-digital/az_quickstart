<?php

declare(strict_types=1);

namespace Drupal\Tests\embed\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Embed's preview functionality.
 *
 * @group embed
 */
class EmbedPreviewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['embed_test', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that out-of-band assets are included with previews.
   */
  public function testPreview() {
    NodeType::create([
      'type' => 'baz',
      'label' => 'Bazzz',
    ])->save();

    $filter_format = FilterFormat::create([
      'format' => 'foo',
      'name' => 'Foo',
    ]);
    $filter_format->filters()->addInstanceId('embed_test_node', [
      'id' => 'embed_test_node',
      'provider' => 'embed_test',
      'status' => TRUE,
      'settings' => [],
    ]);
    $filter_format->save();

    $node = Node::create([
      'title' => 'Foobaz',
      'type' => 'baz',
    ]);
    $node->save();

    $account = $this->drupalCreateUser(['use text format foo']);
    $this->drupalLogin($account);

    $response = $this->drupalGet('/embed/preview/foo', [
      'query' => [
        'value' => 'node:' . $node->id(),
        '_wrapper_format' => 'drupal_ajax',
      ],
    ]);

    $this->assertSession()->statusCodeEquals(403);

    // Now test with a CSRF token.
    $this->drupalGet('embed-test/get_csrf_token');
    $token = json_decode($this->getSession()->getPage()->getContent());
    $headers = ['X-Drupal-EmbedPreview-CSRF-Token' => $token];

    $response = $this->drupalGet('/embed/preview/foo', [
      'query' => [
        'value' => 'node:' . $node->id(),
        '_wrapper_format' => 'drupal_ajax',
      ],
    ], $headers);

    $this->assertSession()->statusCodeEquals(200);

    // Assert the presence of commands to add out-of-band assets to the page, as
    // done by embed_test_node_view_alter().
    $commands = Json::decode($response);
    // There should be more than one command.
    $this->assertGreaterThan(1, count($commands));

    if (!class_exists('Drupal\Core\Ajax\AddJsCommand')) {
      $this->assertMatch($commands, function (array $command) {
        return $command['command'] == 'insert' && $command['method'] == 'append' && $command['selector'] == 'body' && strpos($command['data'], 'jquery.min.js') > 0;
      });
    }
    else {
      $this->assertMatch($commands, function (array $command) {
        return $command['command'] == 'add_js'  && $command['selector'] == 'body' && strpos($command['data'][0]['src'], 'jquery.min.js') > 0;
      });
    }
  }

  /**
   * Asserts that at least one item in an array matches a predicate.
   *
   * @param array $items
   *   The items to test.
   * @param callable $predicate
   *   The predicate against which to test the items.
   */
  protected function assertMatch(array $items, callable $predicate) {
    $items = array_filter($items, $predicate);
    $this->assertNotEmpty($items);
  }

}
