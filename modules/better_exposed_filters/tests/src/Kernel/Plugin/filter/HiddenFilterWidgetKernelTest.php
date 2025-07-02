<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the options of a hidden filter widget.
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase
 */
class HiddenFilterWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests hiding element with single option.
   */
  public function testSingleExposedHiddenElement() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Change exposed filter "field_bef_boolean" to hidden (i.e. 'bef_hidden').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'bef_hidden',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "FIELD_BEF_BOOLEAN" filter is rendered as a hidden element.
    $actual = $this->xpath('//form//input[@type="hidden" and starts-with(@name, "field_bef_boolean_value")]');
    $this->assertCount(1, $actual, 'Exposed filter "FIELD_BEF_BOOLEAN" is hidden.');

    $view->destroy();
  }

  /**
   * Tests hiding element with multiple options.
   */
  public function testMultipleExposedHiddenElement() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Set filter to "multiple".
    $display['display_options']['filters']['field_bef_integer_value']['expose']['multiple'] = TRUE;

    // Change exposed filter "field_bef_integer" to hidden (i.e. 'bef_hidden').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'bef_hidden',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "FIELD_BEF_INTEGER" filter is rendered as a hidden element.
    $actual = $this->xpath('//form//label[@type="label" and starts-with(@for, "edit-field-bef-integer-value")]');
    $this->assertCount(0, $actual, 'Exposed filter "FIELD_BEF_INTEGER" is hidden.');

    $actual = $this->xpath('//form//input[@type="hidden" and starts-with(@name, "field_bef_integer_value")]');
    $this->assertCount(0, $actual, 'Exposed filter "FIELD_BEF_INTEGER" has no selected values.');
  }

}
