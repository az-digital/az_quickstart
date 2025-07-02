<?php

namespace Drupal\Tests\views_bulk_operations\Kernel;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
 * @group views_bulk_operations
 */
class ActionMessagesTest extends ViewsBulkOperationsKernelTestBase {

  /**
   * Tests messages displayed by different actions.
   *
   * @covers ::getPageList
   * @covers ::populateQueue
   * @covers ::process
   *
   * @dataProvider actionDataProvider
   */
  public function testViewsbulkOperationsActionMessages(int $nodes_count, string $action_id, array $result_messages): void {
    $this->createTestNodes([
      'page' => [
        'count' => $nodes_count,
      ],
    ]);

    $vbo_data = [
      'view_id' => 'views_bulk_operations_test_advanced',
      'action_id' => $action_id,
    ];

    // Test executing all view results first.
    $results = $this->executeAction($vbo_data);

    foreach ($result_messages as $index => $message) {
      static::assertEquals($message['type'], $results['finished_output'][$index]['type']);
      static::assertEquals($message['message'], $results['finished_output'][$index]['message']);
    }
  }

  /**
   * Data provider.
   *
   * @return mixed[]
   *   The test data.
   */
  public function actionDataProvider(): array {
    return [
      [
        4,
        'views_bulk_operations_simple_test_action',
        [
          [
            'message' => 'Test (3)',
            'type' => 'status',
          ],
        ],
      ],
      [
        4,
        'views_bulk_operations_test_action_v2',
        [
          [
            'message' => 'A warning message. (1)',
            'type' => 'warning',
          ],
          [
            'message' => 'Standard output. (2)',
            'type' => 'status',
          ],
        ],
      ],
    ];
  }

}
