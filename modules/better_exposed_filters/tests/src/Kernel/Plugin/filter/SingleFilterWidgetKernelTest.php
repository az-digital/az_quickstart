<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the options of a single on/off filter widget.
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase
 */
class SingleFilterWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests hiding element with single option.
   */
  public function testSingleExposedCheckbox() {
    $view = Views::getView('bef_test');

    // Change exposed filter "field_bef_boolean" to single on/off (i.e.
    // 'bef_single').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'bef_single',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "FIELD_BEF_BOOLEAN" filter is rendered as a single checkbox.
    $actual = $this->xpath('//form//input[@type="checkbox" and starts-with(@name, "field_bef_boolean_value")]');
    $this->assertCount(1, $actual, 'Exposed filter "FIELD_BEF_BOOLEAN" is rendered as a checkbox.');

    $view->destroy();
  }

}
