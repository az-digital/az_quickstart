<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the Number widget.
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\Number
 */
class NumberWidgetTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests hiding element with single option.
   */
  public function testNumberWidgetMinAndMax() {
    $view = Views::getView('bef_test');

    // Change exposed filter "field_bef_integer" and "term_node_tid_depth" to
    // links (i.e. 'bef_links').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_price_value' => [
          'plugin_id' => 'bef_number',
          'max' => 100,
          'min' => 1,
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "field_bef_price_value" filter has correct attributes.
    $actual = $this->xpath('//form//input[@type="number" and @min="1" and @max="100" and starts-with(@name, "field_bef_price_value")]');
    $this->assertCount(1, $actual);

    $view->destroy();
  }

}
