<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the advanced options of a filter widget.
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase
 */
class FilterWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests grouping filter options.
   *
   * There is a bug in views where changing the identifier of an exposed
   * grouped filter will cause an undefined index notice.
   *
   * @todo Enable test once https://www.drupal.org/project/drupal/issues/2884296
   *   is fixed
   */
  /*public function testGroupedFilters() {
  $view = Views::getView('bef_test');
  $display = &$view->storage->getDisplay('default');

  // Ensure our filter "field_bef_boolean_value" is grouped.
  $display['display_options']['filters']['field_bef_boolean_value']
  ['is_grouped'] = TRUE;
  $display['display_options']['filters']['field_bef_boolean_value']
  ['group_info'] = [
  'plugin_id' => 'boolean',
  'label' => 'bef_boolean (field_bef_boolean)',
  'description' => '',
  'identifier' => 'field_bef_boolean_value2',
  'optional' => TRUE,
  'widget' => 'select',
  'multiple' => FALSE,
  'remember' => FALSE,
  'default_group' => 'All',
  'default_group_multiple' => [],
  'group_items' => [
  1 => [
  'title' => 'YES',
  'operator' => '=',
  'value' => '1',
  ],
  2 => [
  'title' => 'NO',
  'operator' => '=',
  'value' => '0',
  ],
  ],
  ];

  // Render the exposed form.
  $output = $this->getExposedFormRenderArray($view);

  // Check our "FIELD_BEF_BOOLEAN" filter is rendered with id
  // "field_bef_boolean_value2".
  $this->assertTrue(isset($output['field_bef_boolean_value2']),
  'Exposed filter "FIELD_BEF_BOOLEAN" is exposed with id
  "field_bef_boolean_value2".');

  $view->destroy();
  }*/

  /**
   * Tests sorting filter options alphabetically.
   */
  public function testSortFilterOptions() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Get the exposed form render array.
    $output = $this->getExposedFormRenderArray($view);

    // Assert our "field_bef_integer" filter options are not sorted
    // alphabetically, but by key.
    $sorted_options = $options = $output['field_bef_integer_value']['#options'];
    asort($sorted_options);

    $this->assertNotEquals(array_keys($options), array_keys($sorted_options), '"Field BEF integer" options are not sorted alphabetically.');

    $view->destroy();

    // Enable sort for filter options.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'sort_options' => TRUE,
          ],
        ],
      ],
    ]);

    // Get the exposed form render array.
    $output = $this->getExposedFormRenderArray($view);

    // Assert our "field_bef_integer" filter options are sorted alphabetically.
    $sorted_options = $options = $output['field_bef_integer_value']['#options'];
    asort($sorted_options);

    // Assert our "collapsible" options detail is visible.
    $this->assertEquals(array_keys($options), array_keys($sorted_options), '"Field BEF integer" options are sorted alphabetically.');

    $view->destroy();
  }

  /**
   * Tests moving filter option into collapsible fieldset.
   */
  public function testCollapsibleOption() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Enable collapsible options.
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_email_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'collapsible' => TRUE,
          ],
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Assert our "collapsible" options detail is visible.
    $actual = $this->xpath("//form//details[@data-drupal-selector='edit-field-bef-email-value-collapsible']");
    $this->assertCount(1, $actual, '"Field BEF Email" option is displayed as collapsible fieldset.');

    $view->destroy();
  }

}
