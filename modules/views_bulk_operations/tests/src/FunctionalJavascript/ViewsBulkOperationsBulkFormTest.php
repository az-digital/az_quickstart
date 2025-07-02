<?php

namespace Drupal\Tests\views_bulk_operations\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\WebAssert;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBulkFormTest extends WebDriverTestBase {

  use ViewsBulkOperationsFormTrait;

  private const TEST_NODE_COUNT = 15;

  private const TEST_VIEW_ID = 'views_bulk_operations_test_advanced';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';


  /**
   * The assert session.
   */
  protected WebAssert $assertSession;

  /**
   * The page element.
   */
  protected DocumentElement $page;


  /**
   * The selected indexes of rows.
   *
   * @var array
   */
  protected array $selectedIndexes = [];

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $testNodes = [];

  /**
   * Test view parameters as in the config.
   *
   * @var array
   */
  protected array $testViewParams;

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
    for ($i = 0; $i <= self::TEST_NODE_COUNT; $i++) {
      $this->testNodes[] = $this->drupalCreateNode([
        'type' => 'page',
        'title' => 'Title ' . $i,
        'created' => 1000 + self::TEST_NODE_COUNT - $i,
        'sticky' => $i % 2,
      ]);
    }

    // Sort nodes as in the view: sticky -> created.
    $this->sortNodes('ASC', $this->testNodes);

    $admin_user = $this->drupalCreateUser(
      [
        'edit any page content',
        'create page content',
        'delete any page content',
      ]);
    $this->drupalLogin($admin_user);

    $this->assertSession = $this->assertSession();
    $this->page = $this->getSession()->getPage();

    $testViewConfig = \Drupal::service('config.factory')->getEditable('views.view.' . self::TEST_VIEW_ID);

    // Get useful config data from the test view.
    $config_data = $testViewConfig->getRawData();
    $this->testViewParams = [
      'items_per_page' => $config_data['display']['default']['display_options']['pager']['options']['items_per_page'],
      'path' => $config_data['display']['page_1']['display_options']['path'],
    ];

    // Enable AJAX on the view.
    // Disable VBO batching and pager offset as that's tested elsewhere.
    $config_data['display']['default']['display_options']['use_ajax'] = TRUE;
    $config_data['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['batch'] = FALSE;
    $config_data['display']['default']['display_options']['pager']['options']['offset'] = 0;
    $testViewConfig->setData($config_data);
    $testViewConfig->save();

    $this->drupalGet('/' . $this->testViewParams['path']);
  }

  /**
   * Helper function to sort nodes depending on exposed filter value.
   */
  private function sortNodes(string $sticky_order, array &$nodes): void {
    usort($nodes, function ($node1, $node2) use ($sticky_order) {
      if ($node1->get('sticky')->value === $node2->get('sticky')->value) {
        return $node2->get('created')->value - $node1->get('created')->value;
      }
      if ($sticky_order === 'DESC') {
        return $node2->get('sticky')->value - $node1->get('sticky')->value;
      }
      elseif ($sticky_order === 'ASC') {
        return $node1->get('sticky')->value - $node2->get('sticky')->value;
      }
    });
  }

  /**
   * Tests the VBO bulk form without dynamic insertion.
   */
  public function testViewsBulkOperationsAjaxUi(): void {
    // Make sure a checkbox appears on all rows.
    for ($i = 0; $i < $this->testViewParams['items_per_page']; $i++) {
      $this->assertSession->fieldExists('edit-views-bulk-operations-bulk-form-' . $i);
    }

    // For better performance, line up tests that don't modify nodes but
    // just check if selection and processing works correctly.
    $this->testSelectionPersists();

  }

  /**
   * Test if selection persists on view pages.
   */
  private function testSelectionPersists() {
    $selected_ids = [];

    $page_selections = [
      [0, 1, 3],
      [1, 2],
    ];

    foreach ($page_selections as $page => $selection) {
      foreach ($selection as $page_index) {
        $selected_index = $page * $this->testViewParams['items_per_page'] + $page_index;
        $selected_indexes[$selected_index] = $selected_index;
        $node_id = $this->testNodes[$selected_index]->id();
        $selected_ids[$node_id] = $node_id;
        $this->page->checkField('views_bulk_operations_bulk_form[' . $page_index . ']');
      }

      $this->page->clickLink('Go to next page');
      $this->assertSession->assertWaitOnAjaxRequest();
    }

    // Change sort order, check if checkboxes are still checked.
    $this->page->selectFieldOption('sort_order', 'DESC');
    $this->page->pressButton('Apply');
    $this->assertSession->assertWaitOnAjaxRequest();

    // We should be back to page 0 now with different sorting applied
    // and selection still persisting.
    $this->sortNodes('DESC', $this->testNodes);
    $npages = (int) \ceil(self::TEST_NODE_COUNT / $this->testViewParams['items_per_page']);
    for ($page = 0;; $page++) {
      for ($i = 0; $i < $this->testViewParams['items_per_page']; $i++) {
        $view_node_index = $page * $this->testViewParams['items_per_page'] + $i;
        $node = $this->testNodes[$view_node_index];
        if (\array_key_exists($node->id(), $selected_ids)) {
          $this->assertTrue($this->page->hasCheckedField($node->label()), sprintf('%s should be still checked.', $node->label()));
        }
        else {
          $this->assertTrue($this->page->hasUncheckedField($node->label()), sprintf('%s should be still unchecked.', $node->label()));
        }
      }

      if ($page + 1 === $npages) {
        break;
      }
      $this->page->clickLink('Go to next page');
      $this->assertSession->assertWaitOnAjaxRequest();
    }

    // Execute test operation.
    $this->page->selectFieldOption('action', 'Simple test action');
    $this->page->pressButton('Apply to selected items');

    // Assert if only the selected nodes were processed.
    foreach ($this->testNodes as $node) {
      if (\array_key_exists($node->id(), $selected_ids)) {
        $this->assertSession->pageTextContains(\sprintf('Test action (label: %s)', $node->label()));
      }
      else {
        $this->assertSession->pageTextNotContains(\sprintf('Test action (label: %s)', $node->label()));
      }
    }
    $this->assertSession->pageTextContains(\sprintf('Test (%s)', \count($selected_ids)));
  }

  /**
   * Tests the VBO bulk form with dynamic insertion.
   *
   * Nodes inserted right after selecting targeted row(s) of the view.
   */
  public function testViewsBulkOperationsWithDynamicInsertion(): void {

    $selected_indexes = [0, 1, 3];

    foreach ($selected_indexes as $selected_index) {
      $this->page->checkField('views_bulk_operations_bulk_form[' . $selected_index . ']');
    }

    // Insert nodes that will be displayed before the previous ones.
    for ($i = 0; $i < self::TEST_NODE_COUNT; $i++) {
      $this->drupalCreateNode([
        'type' => 'page',
        'title' => 'Title added ' . $i,
        'created' => 2000 + self::TEST_NODE_COUNT - $i,
      ]);
    }

    $this->page->selectFieldOption('action', 'Simple test action');
    $this->page->pressButton('Apply to selected items');

    foreach ($selected_indexes as $index) {
      $node = $this->testNodes[$index];
      $this->assertSession->pageTextContains(\sprintf('Test action (label: %s)', $node->label()));
    }
    $this->assertSession->pageTextContains(\sprintf('Test (%s)', \count($selected_indexes)));

    // Check that the view now actually contains the new nodes in place of the
    // previously displayed ones.
    for ($i = 0; $i < $this->testViewParams['items_per_page']; $i++) {
      $this->assertSession->pageTextContains(\sprintf('Title added %s', $i));
    }
  }

}
