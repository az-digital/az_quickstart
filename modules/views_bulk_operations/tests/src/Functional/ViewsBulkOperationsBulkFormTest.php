<?php

namespace Drupal\Tests\views_bulk_operations\Functional;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBulkFormTest extends ViewsBulkOperationsFunctionalTestBase {

  private const TEST_NODE_COUNT = 15;

  /**
   * Tests the VBO bulk form with simple test action.
   */
  public function testViewsBulkOperationsBulkFormSimple(): void {

    $assertSession = $this->assertSession();

    $this->drupalGet('views-bulk-operations-test');

    // Test that the views edit header appears first.
    $first_form_element = $this->xpath('//form/div[1][@id = :id]', [':id' => 'edit-header']);
    $this->assertNotEmpty($first_form_element, 'The views form edit header appears first.');

    // Make sure a checkbox appears on all rows and every checkbox has
    // the correct label.
    for ($i = 0; $i < 4; $i++) {
      $checkbox_selector = 'edit-views-bulk-operations-bulk-form-' . $i;
      $assertSession->fieldExists($checkbox_selector);
    }

    // The advanced action should not be shown on the form - no permission.
    $this->assertEmpty($this->cssSelect('input[value=views_bulk_operations_advanced_test_action]'), 'Advanced action is not selectable.');

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['edit any page content']);
    $this->drupalLogin($admin_user);

    // Execute the simple test action.
    $selected = [0, 2, 3];
    $this->executeAction('views-bulk-operations-test', 'Simple test action', $selected);

    foreach ($selected as $index) {
      $assertSession->pageTextContains(\sprintf('Test action (label: %s)',
        $this->testNodes[$index]->label()
      ));
    }

    // Test the select all functionality.
    // With the exclude mode, we also have to select all rows of the
    // view, otherwise those will be treated as excluded. In the UI
    // this is handled by JS.
    $selected = [0, 1, 2, 3];
    $data = ['select_all' => 1];
    $this->executeAction(NULL, 'Simple test action', $selected, $data);

    $assertSession->pageTextContains(\sprintf('Test (%d)', self::TEST_NODE_COUNT));

  }

  /**
   * More advanced test.
   *
   * Uses the ViewsBulkOperationsAdvancedTestAction.
   */
  public function testViewsBulkOperationsBulkFormAdvanced(): void {

    $assertSession = $this->assertSession();

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser([
      'edit any page content',
    ]);
    $this->drupalLogin($admin_user);

    // First execute the simple action to test
    // the ViewsBulkOperationsController class.
    $selected = [0, 2];
    $data = ['action' => 0];
    $this->executeAction('views-bulk-operations-test-advanced', 'Apply to selected items', $selected, $data);

    $assertSession->pageTextContains(\sprintf('Test (%d)', \count($selected)));

    // Execute the advanced test action.
    $selected = [0, 1, 3];
    $data = ['action' => 1];
    $this->executeAction('views-bulk-operations-test-advanced', 'Apply to selected items', $selected, $data);

    // Check if the configuration form is open and contains the
    // test_config field.
    $assertSession->fieldExists('edit-test-config');

    // Check if the configuration form contains selected entity labels.
    // NOTE: The view pager has an offset set on this view, so checkbox
    // indexes are not equal to test nodes array keys. Hence the $index + 1.
    foreach ($selected as $index) {
      $assertSession->pageTextContains($this->testNodes[$index + 1]->label());
    }

    $config_value = 'test value';
    $edit = [
      'test_config' => $config_value,
    ];
    $this->submitForm($edit, 'Apply');

    // Execute action by posting the confirmation form
    // (also tests if the submit button exists on the page).
    $this->submitForm([], 'Execute action');

    // If all went well and Batch API did its job,
    // the next page should display results.
    $testViewConfig = \Drupal::service('config.factory')->get('views.view.views_bulk_operations_test_advanced');
    $configData = $testViewConfig->getRawData();
    $preconfig_setting = $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'][1]['preconfiguration']['test_preconfig'];

    // NOTE: The view pager has an offset set on this view, so checkbox
    // indexes are not equal to test nodes array keys. Hence the $index + 1.
    foreach ($selected as $index) {
      $assertSession->pageTextContains(\sprintf('Test action (preconfig: %s, config: %s, label: %s)',
        $preconfig_setting,
        $config_value,
        $this->testNodes[$index + 1]->label()
      ));
    }

    // Test the exclude functionality with batching and entity
    // property changes affecting view query results.
    $edit = [
      'action' => 1,
      'select_all' => 1,
    ];
    // Let's leave two checkboxes unchecked to test the exclude mode.
    foreach ([0, 2] as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }
    $this->submitForm($edit, 'Apply to selected items');
    $this->submitForm(['test_config' => 'unpublish'], 'Apply');
    $this->submitForm([], 'Execute action');
    // Again, take offset into account (-1), also take 2 excluded
    // rows into account (-2).
    // Also, check if the custom completed message appears.
    $assertSession->pageTextContains(\sprintf('Overridden message (%s)', \count($this->testNodes) - 3));

    $this->assertNotEmpty((\count($this->cssSelect('table.vbo-table tbody tr')) === 2), "The view shows only excluded results.");
  }

  /**
   * View and context passing test.
   *
   * Uses the ViewsBulkOperationsPassTestAction.
   */
  public function testViewsBulkOperationsBulkFormPassing(): void {

    $assertSession = $this->assertSession();

    // Log in as a user with 'bypass node access' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($admin_user);

    // Test with all selected and specific selection, with batch
    // size greater than items per page and lower than items per page,
    // using Batch API process and without it.
    $cases = [
      ['batch' => FALSE, 'selection' => TRUE, 'page' => 1],
      ['batch' => FALSE, 'selection' => FALSE],
      ['batch' => TRUE, 'batch_size' => 3, 'selection' => TRUE, 'page' => 1],
      ['batch' => TRUE, 'batch_size' => 7, 'selection' => TRUE],
      ['batch' => TRUE, 'batch_size' => 3, 'selection' => FALSE],
      ['batch' => TRUE, 'batch_size' => 7, 'selection' => FALSE],
    ];

    // Custom selection.
    $selected = [0, 1, 3, 4];

    $testViewConfig = \Drupal::service('config.factory')->getEditable('views.view.views_bulk_operations_test_advanced');
    $configData = $testViewConfig->getRawData();
    $items_per_page = 5;

    foreach ($cases as $case) {
      $items_per_page++;

      // Populate form values.
      $edit = [
        'action' => 2,
      ];
      if ($case['selection']) {
        foreach ($selected as $index) {
          $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
        }
      }
      else {
        $edit['select_all'] = 1;
        // So we don't cause exclude mode.
        for ($i = 0; $i < $items_per_page; $i++) {
          $edit["views_bulk_operations_bulk_form[$i]"] = TRUE;
        }
      }

      // Update test view configuration.
      $configData['display']['default']['display_options']['pager']['options']['items_per_page'] = $items_per_page;
      $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['batch'] = $case['batch'];
      if (isset($case['batch_size'])) {
        $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['batch_size'] = $case['batch_size'];
      }
      $testViewConfig->setData($configData);
      $testViewConfig->save();

      $options = [];
      if (!empty($case['page'])) {
        $options['query'] = ['page' => $case['page']];
      }

      $this->drupalGet('views-bulk-operations-test-advanced', $options);
      $this->submitForm($edit, 'Apply to selected items');

      // On batch-enabled processes check if provided context data is correct.
      if ($case['batch']) {
        if ($case['selection']) {
          $total = \count($selected);
        }
        else {
          // Again, include offset.
          $total = \count($this->testNodes) - 1;
        }
        $n_batches = \ceil($total / $case['batch_size']);

        for ($i = 0; $i < $n_batches; $i++) {
          $processed = $i * $case['batch_size'];
          $assertSession->pageTextContains(\sprintf(
            'Processed %s of %s.',
            $processed,
            $total
          ));
        }
      }

      // Passed view integrity check.
      $assertSession->pageTextContains('Passed view results match the entity queue.');
    }

  }

  /**
   * Test core action - specific configuration.
   */
  public function testActionCorePreconfig(): void {
    $assertSession = $this->assertSession();

    $testViewConfig = \Drupal::service('config.factory')->getEditable('views.view.views_bulk_operations_test');
    $configData = $testViewConfig->getRawData();
    $preconfig = &$configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'][0]['preconfiguration'];
    $preconfig['add_confirmation'] = TRUE;
    $testViewConfig->setData($configData);
    $testViewConfig->save();

    $this->drupalGet('views-bulk-operations-test');

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['edit any page content']);
    $this->drupalLogin($admin_user);

    // Check if we're on the confirmation form and if the overridden label
    // is displayed.
    $selection = [0, 2, 3];
    $label = $preconfig['label_override'];
    $this->executeAction('views-bulk-operations-test', 'Simple test action', $selection);
    $assertSession->pageTextContains(\sprintf('Are you sure you wish to perform "%s" action on %d entities?', $label, \count($selection)));
  }

}
