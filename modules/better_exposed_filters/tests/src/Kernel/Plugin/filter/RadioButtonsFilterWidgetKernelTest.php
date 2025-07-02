<?php

namespace Drupal\Tests\better_exposed_filters\Kernel\Plugin\filter;

use Drupal\Tests\better_exposed_filters\Kernel\BetterExposedFiltersKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the radio buttons/checkboxes filter widget (i.e. "bef").
 *
 * @group better_exposed_filters
 *
 * @see \Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\RadioButtons
 */
class RadioButtonsFilterWidgetKernelTest extends BetterExposedFiltersKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['bef_test'];

  /**
   * Tests the exposed checkboxes filter widget.
   */
  public function testExposedCheckboxes() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Ensure our filter "field_bef_integer" allows multiple values.
    $display['display_options']['filters']['field_bef_integer_value']['expose']['multiple'] = TRUE;
    // Ensure our filter "term_node_tid_depth" has show hierarchy enabled.
    $display['display_options']['filters']['term_node_tid_depth']['expose']['multiple'] = TRUE;
    $display['display_options']['filters']['term_node_tid_depth']['hierarchy'] = TRUE;

    // Change exposed filter "field_bef_integer" and "term_node_tid_depth" to
    // checkboxes (i.e. 'bef').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_integer_value' => [
          'plugin_id' => 'bef',
        ],
        'term_node_tid_depth' => [
          'plugin_id' => 'bef',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our "FIELD_BEF_INTEGER" filter is rendered as checkboxes.
    $actual = $this->xpath('//form//input[@type="checkbox" and starts-with(@name, "field_bef_integer_value")]');
    $this->assertCount(5, $actual, 'Exposed filter "FIELD_BEF_INTEGER" has correct number of exposed checkboxes.');

    // Check our "TERM_NODE_TID_DEPTH" filter is rendered as nested checkboxes.
    $actual = $this->xpath("//form//div[contains(concat(' ',normalize-space(@class),' '),' bef-nested ')]");
    $this->assertCount(1, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has bef-nested class');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/div/input[@type="checkbox" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(3, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed top-level checkboxes.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/ul/li/div/input[@type="checkbox" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(5, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed second-level checkboxes.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/ul/li/ul/li/div/input[@type="checkbox" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(14, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed third-level checkboxes.');

    $view->destroy();
  }

  /**
   * Tests the exposed radio buttons filter widget.
   */
  public function testExposedRadioButtons() {
    $view = Views::getView('bef_test');
    $display = &$view->storage->getDisplay('default');

    // Ensure our filter "term_node_tid_depth" has show hierarchy enabled.
    $display['display_options']['filters']['term_node_tid_depth']['hierarchy'] = TRUE;

    // Change exposed filter "field_bef_integer" and "term_node_tid_depth" to
    // radio buttons (i.e. 'bef').
    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'bef',
        ],
        'term_node_tid_depth' => [
          'plugin_id' => 'bef',
        ],
      ],
    ]);

    // Render the exposed form.
    $this->renderExposedForm($view);

    // Check our filter is rendered as radio buttons (i.e. Any, true, false).
    $actual = $this->xpath('//form//input[@type="radio" and @name="field_bef_boolean_value"]');
    $this->assertCount(3, $actual, 'Exposed filter "FIELD_BEF_BOOLEAN" renders as radio buttons.');

    // Check our "TERM_NODE_TID_DEPTH" filter is rendered as nested radio
    // buttons.
    $actual = $this->xpath("//form//div[contains(concat(' ',normalize-space(@class),' '),' bef-nested ')]");
    $this->assertCount(1, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has bef-nested class');

    // The difference with checkboxes is that radio buttons render an additional
    // top level option (i.e. any).
    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/div/input[@type="radio" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(4, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed top-level radio buttons.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/ul/li/div/input[@type="radio" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(5, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed second-level radio buttons.');

    $actual = $this->xpath('//form//div[@id="edit-term-node-tid-depth--2"]/div/ul/li/ul/li/ul/li/div/input[@type="radio" and starts-with(@name, "term_node_tid_depth")]');
    $this->assertCount(14, $actual, 'Exposed filter "TERM_NODE_TID_DEPTH" has correct number of exposed third-level radio buttons.');

    $view->destroy();
  }

}
