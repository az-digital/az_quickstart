<?php

namespace Drupal\Tests\views_bulk_operations\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Commands\ViewsBulkOperationsCommands
 * @group views_bulk_operations
 */
class DrushCommandsTest extends BrowserTestBase {
  use DrushTestTrait;

  private const TEST_NODE_COUNT = 15;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

  /**
   * Array of node objects used for testing.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $testNodes = [];

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'views',
    'views_bulk_operations',
    'views_bulk_operations_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create some nodes for testing.
    $this->drupalCreateContentType(['type' => 'page']);

    $this->testNodes = [];
    $time = $this->container->get('datetime.time')->getRequestTime();
    for ($i = 0; $i < self::TEST_NODE_COUNT; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $time -= $i;
      $this->testNodes[] = $this->drupalCreateNode([
        'type' => 'page',
        'title' => 'Title ' . $i,
        'sticky' => $i % 2,
        'created' => $time,
        'changed' => $time,
      ]);
    }

  }

  /**
   * Tests the VBO Drush command.
   */
  public function testDrushCommand(): void {
    $arguments = [
      'views_bulk_operations_test',
      'views_bulk_operations_simple_test_action',
    ];

    // Basic test.
    $this->drush('vbo-exec', $arguments);
    for ($i = 0; $i < self::TEST_NODE_COUNT; $i++) {
      $this->assertStringContainsString("Test action (label: Title $i)", $this->getErrorOutput());
    }

    // Exposed filters test.
    $this->drush('vbo-exec', $arguments, ['exposed' => 'sticky=1']);
    for ($i = 0; $i < self::TEST_NODE_COUNT; $i++) {
      $test_string = "Test action (label: Title $i)";
      if ($i % 2) {
        $this->assertStringContainsString($test_string, $this->getErrorOutput());
      }
      else {
        $this->assertStringNotContainsString($test_string, $this->getErrorOutput());
      }
    }
  }

}
