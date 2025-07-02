<?php

namespace Drupal\Tests\views_bulk_operations\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for VBO browser tests.
 */
abstract class ViewsBulkOperationsFunctionalTestBase extends BrowserTestBase {

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
        'sticky' => FALSE,
        'created' => $time,
        'changed' => $time,
      ]);
    }

  }

  /**
   * Helper function that executes en operation.
   *
   * @param string|null $path
   *   The path of the View page that includes VBO.
   * @param string $button_text
   *   The form submit button text.
   * @param int[] $selection
   *   The selected items' indexes.
   * @param array $data
   *   Additional parameters for the submitted form.
   */
  protected function executeAction($path, string $button_text, array $selection = [], array $data = []): void {
    foreach ($selection as $index) {
      $data["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }
    if ($path !== NULL) {
      $this->drupalGet($path);
    }
    $this->submitForm($data, $button_text);
  }

}
