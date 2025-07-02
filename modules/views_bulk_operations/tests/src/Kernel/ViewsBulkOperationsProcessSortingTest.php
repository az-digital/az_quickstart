<?php

namespace Drupal\Tests\views_bulk_operations\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\views\Views;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
 * @group views_bulk_operations
 */
class ViewsBulkOperationsProcessSortingTest extends ViewsBulkOperationsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $type_name = 'page';
    $type = NodeType::create([
      'type' => $type_name,
      'name' => $type_name,
    ]);
    $type->save();

    $timestamps = [
      100,
      300,
      200,
      400,
      600,
      500,
      700,
      900,
      800,
    ];
    $now = time();
    foreach ($timestamps as $ts) {
      $timestamp = $now + $ts;
      $this->drupalCreateNode([
        'type' => $type_name,
        'title' => "Node changed at $timestamp seconds.",
        'sticky' => FALSE,
        'created' => $now,
        'changed' => $timestamp,
      ]);
    }
  }

  /**
   * Tests ViewsBulkOperationsActionProcessor ordering by id.
   *
   * The view used in the test uses a table display plugin.
   * The display plugin is configured to use the "Changed" field as default
   * sorting (desc).
   * Default view execution should follow that ordering but
   * ViewsBulkOperationsActionProcessor::getPageList should force ordering
   * based on id.
   *
   * @covers ::getPageList
   */
  public function testViewsbulkOperationsIdOrderIsForcedOnTableStylePlugin(): void {
    $view_name = 'batch_with_date_default_tablesort';
    $display_id = 'page_1';
    $view = Views::getView($view_name);
    $this->assertNotNull($view, 'View should exist');
    $this->assertTrue($view->setDisplay($display_id), 'Display should exist');
    $view->execute();
    $this->assertSame(
      [8, 9, 7, 5, 6, 4, 2, 3, 1],
      array_map(fn ($row) => (int) $row->nid, $view->result),
      'View executed normally must sort by table display default sorting which is "changed desc".',
    );
    $view2 = Views::getView($view_name);
    $view2->setDisplay($display_id);
    $actionProcessor = \Drupal::service('views_bulk_operations.processor');
    $actionProcessor->initialize([
      'action_id' => 'node_make_sticky_action',
      'configuration' => [],
      'display_id' => $display_id,
      'batch_size' => 3,
      'relationship_id' => 'none',
    ], $view2);
    $page_and_expected = [
      0 => [1, 2, 3],
      1 => [4, 5, 6],
      2 => [7, 8, 9],
    ];
    foreach ($page_and_expected as $page => $expected) {
      $result = $actionProcessor->getPageList($page);
      $this->assertSame(
        $expected,
        array_map(fn($res) => (int) $res[0], $result),
        'VBO processor when called should force ordering by id.',
      );
    }
  }

}
